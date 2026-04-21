<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class ExerciceController extends Controller
{
// ==========================================
    // 1. CHARGER LA LISTE DES EXERCICES
    // ==========================================
    public function liste(Request $request)
    {
        Log::info('--- DÉBUT : Récupération de la liste des exercices ---');
        $request->validate(['sp_identifier' => 'required|string']);

        $exercices = DB::table('exercices')
            ->where('sp_identifier', $request->sp_identifier)
            ->orderBy('start_date', 'desc')
            ->get();

        // 🟢 FIX HNA: Kan-zidou l-flouss w les clés l-kol exercice bach ybano f "Modifier" f Angular
        foreach ($exercices as $ex) {
            // 1. Jbed l-Budget Prévisionnel
            $prev = DB::table('charges_previsionnelles')->where('se_identifier', $ex->se_identifier)->first();
            $ex->budget_previsionnel_total = $prev ? $prev->budget : 0;
            $ex->cles_previsionnel = $prev ? DB::table('bp_to_key')->where('scp_identifier', $prev->scp_identifier)->get() : [];

            // 2. Jbed l-Budget Travaux
            $trav = DB::table('charges_travaux')->where('se_identifier', $ex->se_identifier)->first();
            $ex->budget_travaux_total = $trav ? $trav->budget : 0;
            $ex->cles_travaux = $trav ? DB::table('bt_to_key')->where('sct_identifier', $trav->sct_identifier)->get() : [];
        }

        Log::info('Nombre d\'exercices trouvés : ' . $exercices->count());

        return response()->json(['success' => true, 'data' => $exercices]);
    }

    // ==========================================
    // 2. AJOUTER UN EXERCICE
    // ==========================================
    public function ajouter(Request $request)
    {
        Log::info('--- DÉBUT : Ajout d\'un exercice ---', $request->all());

        $request->validate([
            'sp_identifier' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'period' => 'required|in:trimestre,quadrimestre,mensuel',
            'budget_previsionnel_total' => 'required|numeric',
            'list_cles_previsionnel' => 'required|array',
            'budget_travaux_total' => 'required|numeric',
            'list_cles_travaux' => 'required|array',
        ]);

        $sp_id = $request->sp_identifier;

        // Vérification exercice actif
        $exerciceActif = DB::table('exercices')
            ->where('sp_identifier', $sp_id)
            ->whereIn('status', ['en cours', 'en attente'])
            ->exists();

        if ($exerciceActif) {
            return response()->json(['success' => false, 'message' => 'Un exercice actif ou en attente existe déjà.'], 400);
        }

        DB::beginTransaction();
        try {
            $se_identifier = 'EX-' . time() . '-' . rand(100, 999);
            $scp_identifier = 'CP-' . time() . '-' . rand(100, 999);
            $sct_identifier = 'CT-' . time() . '-' . rand(100, 999);

            // 1. Table exercices
            DB::table('exercices')->insert([
                'se_identifier' => $se_identifier,
                'sp_identifier' => $sp_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'period' => $request->period,
                'status' => 'en attente',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // 2. Budget Prévisionnel
            DB::table('charges_previsionnelles')->insert([
                'scp_identifier' => $scp_identifier,
                'se_identifier' => $se_identifier,
                'budget' => $request->budget_previsionnel_total,
                'total_encaissements' => 0,
                'total_depenses' => 0
            ]);

            $bpLinks = [];
            foreach ($request->list_cles_previsionnel as $cle) {
                $bpLinks[] = [
                    'cle_repartition_id' => $cle['cle_id'], // 🟢 FIX : Beddelna 'sdk_identifier' b 'cle_id'
                    'scp_identifier' => $scp_identifier,
                    'budget' => $cle['montant'],
                    'depenses' => 0
                ];
            }
            DB::table('bp_to_key')->insert($bpLinks);

            // 3. Budget Travaux
            DB::table('charges_travaux')->insert([
                'sct_identifier' => $sct_identifier,
                'se_identifier' => $se_identifier,
                'budget' => $request->budget_travaux_total,
                'total_encaissements' => 0,
                'total_depenses' => 0
            ]);

            $btLinks = [];
            foreach ($request->list_cles_travaux as $cle) {
                $btLinks[] = [
                    'cle_repartition_id' => $cle['cle_id'], // 🟢 FIX : Beddelna 'sdk_identifier' b 'cle_id'
                    'sct_identifier' => $sct_identifier,
                    'budget' => $cle['montant'],
                    'depenses' => 0
                ];
            }
            DB::table('bt_to_key')->insert($btLinks);

            $dossierPath = "proprietes/{$sp_id}/exercices/{$se_identifier}";
            Storage::disk('local')->makeDirectory($dossierPath);

            DB::commit();
            Log::info('✅ Exercice ajouté avec succès');

            return response()->json(['success' => true, 'message' => 'Exercice créé avec succès.']);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('❌ Erreur Ajouter : ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // 3. MODIFIER UN EXERCICE
    // ==========================================
    public function modifier(Request $request)
    {
        Log::info('--- DÉBUT : Modification ---', $request->all());

        $request->validate([
            'sp_identifier' => 'required|string',
            'se_identifier' => 'required|string',
        ]);

        $exercice = DB::table('exercices')->where('se_identifier', $request->se_identifier)->first();

        if (!$exercice || $exercice->status !== 'en attente') {
            return response()->json(['success' => false, 'message' => 'Modification impossible.'], 403);
        }

        DB::beginTransaction();
        try {
            DB::table('exercices')->where('se_identifier', $request->se_identifier)->update([
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'period' => $request->period,
                'updated_at' => now()
            ]);

            // Update Prévisionnel
            $cp = DB::table('charges_previsionnelles')->where('se_identifier', $request->se_identifier)->first();
            if($cp) {
                DB::table('charges_previsionnelles')->where('scp_identifier', $cp->scp_identifier)->update(['budget' => $request->budget_previsionnel_total]);
                DB::table('bp_to_key')->where('scp_identifier', $cp->scp_identifier)->delete();
                
                $bpLinks = [];
                foreach ($request->list_cles_previsionnel as $cle) {
                    $bpLinks[] = ['cle_id' => $cle['cle_id'], 'scp_identifier' => $cp->scp_identifier, 'budget' => $cle['montant'], 'depenses' => 0];
                }
                DB::table('bp_to_key')->insert($bpLinks);
            }

            // Update Travaux
            $ct = DB::table('charges_travaux')->where('se_identifier', $request->se_identifier)->first();
            if($ct) {
                DB::table('charges_travaux')->where('sct_identifier', $ct->sct_identifier)->update(['budget' => $request->budget_travaux_total]);
                DB::table('bt_to_key')->where('sct_identifier', $ct->sct_identifier)->delete();
                
                $btLinks = [];
                foreach ($request->list_cles_travaux as $cle) {
                    $btLinks[] = ['cle_id' => $cle['cle_id'], 'sct_identifier' => $ct->sct_identifier, 'budget' => $cle['montant'], 'depenses' => 0];
                }
                DB::table('bt_to_key')->insert($btLinks);
            }

            DB::commit();
            Log::info('✅ Exercice modifié avec succès');
            return response()->json(['success' => true, 'message' => 'Exercice mis à jour.']);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('❌ Erreur Modifier : ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // 4. SUPPRIMER UN EXERCICE
    // ==========================================
    public function supprimer(Request $request)
    {
        Log::info('🗑️ Tentative de suppression : ' . $request->se_identifier);
        $request->validate(['sp_identifier' => 'required|string', 'se_identifier' => 'required|string']);

        DB::beginTransaction();
        try {
            $dossierPath = "proprietes/{$request->sp_identifier}/exercices/{$request->se_identifier}";
            if (Storage::disk('local')->exists($dossierPath)) {
                Storage::disk('local')->deleteDirectory($dossierPath);
            }

            DB::table('exercices')->where('se_identifier', $request->se_identifier)->delete();

            DB::commit();
            Log::info('✅ Exercice supprimé');
            return response()->json(['success' => true, 'message' => 'Exercice supprimé.']);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('❌ Erreur Supprimer : ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
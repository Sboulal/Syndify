<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class ExerciceController extends Controller
{
// ========================================================
    // 🟢 FONCTION SÉCURISÉE AVEC AUTHENTIFICATION RÉELLE
    // ========================================================
    private function getProprieteId(Request $request)
    {
        // 1. Priorité l-ID li mssift mn l-Frontend (Angular Payload)
        if ($request->has('propriete_id') && !empty($request->propriete_id)) {
            return $request->propriete_id;
        }

        // 2. Ila Angular masift walo, njbdouh mn l-User li m-connecté (Auth)
        $userId = auth()->id(); 
        
        // Ila makanch m-connecté aslan, maymknch y-accéder
        if (!$userId) {
            return null; 
        }

        $propOwnerCol = \Illuminate\Support\Facades\Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';
        $link = \Illuminate\Support\Facades\DB::table('user_as_owner')->where('user_id', $userId)->first();
        
        return $link ? $link->$propOwnerCol : null;
    }
 // ==========================================
    // 1. CHARGER LA LISTE DES EXERCICES
    // ==========================================
    public function liste(Request $request)
    {
        $sp_id = $this->getProprieteId($request);
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        // 🟢 1. Jbed l-m3loumat dyal l-Résidence (Source Unique)
        $propIdCol_propriete = Schema::hasColumn('proprietes', 'sp_identifier') ? 'sp_identifier' : 'id';
        $residence = DB::table('proprietes')->where($propIdCol_propriete, $sp_id)->first();

        // 🟢 2. Jbed les exercices AVEC leur statut de clôture (kima matloub f l-Cahier des charges)
        $exercices = DB::table('exercices')
            ->leftJoin('clotures', 'exercices.se_identifier', '=', 'clotures.se_identifier')
            ->where('exercices.propriete_id', $sp_id)
            ->select('exercices.*', 'clotures.status as cloture_status') // 0: Brouillon, 1: Finalisé
            ->orderBy('exercices.start_date', 'desc') // Du plus récent au plus ancien
            ->get();

        foreach ($exercices as $ex) {
            $prev = DB::table('charges_previsionnelles')->where('se_identifier', $ex->se_identifier)->first();
            $ex->budget_previsionnel_total = $prev ? $prev->budget : 0;
            $ex->cles_previsionnel = $prev ? DB::table('bp_to_key')->where('scp_identifier', $prev->scp_identifier)->get() : [];

            $trav = DB::table('charges_travaux')->where('se_identifier', $ex->se_identifier)->first();
            $ex->budget_travaux_total = $trav ? $trav->budget : 0;
            $ex->cles_travaux = $trav ? DB::table('bt_to_key')->where('sct_identifier', $trav->sct_identifier)->get() : [];
        }

        // 🟢 3. Rjja3 kolchi m-jmo3
        return response()->json([
            'success' => true, 
            'residence' => [
                'nom' => $residence->nom ?? 'Résidence',
                'adresse' => $residence->address ?? 'Adresse non définie'
            ],
            'data' => $exercices
        ]);
    }
public function ajouter(Request $request)
    {
        Log::info('--- DÉBUT : Ajout d\'un exercice ---');

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'period' => 'required|in:trimestre,quadrimestre,mensuel',
            'budget_previsionnel_total' => 'required|numeric',
            'list_cles_previsionnel' => 'present|array', 
            'budget_travaux_total' => 'required|numeric',
            'list_cles_travaux' => 'present|array',      
        ]);

        $sp_id = $this->getProprieteId($request);
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $exerciceActif = DB::table('exercices')
            ->where('propriete_id', $sp_id) // 🟢 FIX HNA
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

            DB::table('exercices')->insert([
                'se_identifier' => $se_identifier,
                'propriete_id' => $sp_id, // 🟢 FIX HNA (Kant sp_identifier)
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'period' => $request->period,
                'status' => 'en attente',
                'created_at' => now(), 
                'updated_at' => now()
            ]);

            // Budget Prévisionnel
            DB::table('charges_previsionnelles')->insert([
                'scp_identifier' => $scp_identifier, 
                'se_identifier' => $se_identifier,
                'budget' => $request->budget_previsionnel_total, 
                'total_encaissements' => 0, 
                'total_depenses' => 0
            ]);

           foreach ($request->list_cles_previsionnel as $cle) {
                DB::table('bp_to_key')->insert([
                    'cle_repartition_id' => $cle['cle_id'], // 👈 Hna l-Fix
                    'scp_identifier' => $scp_identifier, 
                    'budget' => $cle['montant'], 
                    'depenses' => 0
                ]);
            }

            // Budget Travaux
            DB::table('charges_travaux')->insert([
                'sct_identifier' => $sct_identifier, 
                'se_identifier' => $se_identifier,
                'budget' => $request->budget_travaux_total, 
                'total_encaissements' => 0, 
                'total_depenses' => 0
            ]);

            // 🟢 FIX 2: Budget Travaux (Beddelna cle_id b cle_repartition_id)
            foreach ($request->list_cles_travaux as $cle) {
                DB::table('bt_to_key')->insert([
                    'cle_repartition_id' => $cle['cle_id'], // 👈 Hna l-Fix
                    'sct_identifier' => $sct_identifier, 
                    'budget' => $cle['montant'], 
                    'depenses' => 0
                ]);
            }

            // AUTOMATISATION APPELS DE FONDS
            if ($request->budget_previsionnel_total > 0) {
                $periode = strtolower($request->period);
                $nbPeriodes = ($periode === 'mensuel') ? 12 : (($periode === 'quadrimestre') ? 3 : 4);
                $montantParPeriode = $request->budget_previsionnel_total / $nbPeriodes;
                $startDate = \Carbon\Carbon::parse($request->start_date);
                $moisAajouter = 12 / $nbPeriodes;

                for ($i = 0; $i < $nbPeriodes; $i++) {
                    $dateExigibilite = $startDate->copy()->addMonths($i * $moisAajouter);
                    DB::table('appels_fonds')->insert([
                        'af_identifier' => 'AF-PL-' . time() . "-$i",
                        'se_identifier' => $se_identifier,
                        'type_charge' => 'previsionnel',
                        'sub_type' => 'planifie',
                        'title' => "Appel planifié (" . ucfirst($periode) . " " . ($i + 1) . ")",
                        'amount' => $montantParPeriode,
                        'due_date' => $dateExigibilite->format('Y-m-d'),
                        'is_generated' => false,
                        'created_at' => now(), 'updated_at' => now()
                    ]);
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Exercice et appels de fonds créés.']);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

// ==========================================
    // 3. MODIFIER UN EXERCICE
    // ==========================================
    public function modifier(Request $request)
    {
        $request->validate(['se_identifier' => 'required|string']);

        $sp_id = $this->getProprieteId($request);
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $exercice = DB::table('exercices')
            ->where('se_identifier', $request->se_identifier)
            ->where('propriete_id', $sp_id)
            ->first();

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
                    // 🟢 FIX HNA: Rddinaha 'cle_repartition_id' blast 'cle_id'
                    $bpLinks[] = [
                        'cle_repartition_id' => $cle['cle_id'], 
                        'scp_identifier' => $cp->scp_identifier, 
                        'budget' => $cle['montant'], 
                        'depenses' => 0
                    ];
                }
                if(!empty($bpLinks)) DB::table('bp_to_key')->insert($bpLinks);
            }

            // Update Travaux
            $ct = DB::table('charges_travaux')->where('se_identifier', $request->se_identifier)->first();
            if($ct) {
                DB::table('charges_travaux')->where('sct_identifier', $ct->sct_identifier)->update(['budget' => $request->budget_travaux_total]);
                DB::table('bt_to_key')->where('sct_identifier', $ct->sct_identifier)->delete();
                $btLinks = [];
                foreach ($request->list_cles_travaux as $cle) {
                    // 🟢 FIX HNA: Rddinaha 'cle_repartition_id' blast 'cle_id'
                    $btLinks[] = [
                        'cle_repartition_id' => $cle['cle_id'], 
                        'sct_identifier' => $ct->sct_identifier, 
                        'budget' => $cle['montant'], 
                        'depenses' => 0
                    ];
                }
                if(!empty($btLinks)) DB::table('bt_to_key')->insert($btLinks);
            }
            
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Exercice mis à jour avec succès.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    // ==========================================
    // 4. SUPPRIMER UN EXERCICE
    // ==========================================
    public function supprimer(Request $request)
    {
        // 🔴 7iydna propriete_id mn l-Validation
        $request->validate(['se_identifier' => 'required|string']);

        $sp_id = $this->getProprieteId($request);
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        DB::beginTransaction();
        try {
            $exercice = DB::table('exercices')
                ->where('se_identifier', $request->se_identifier)
                ->where('propriete_id', $sp_id)
                ->first();

            if(!$exercice) return response()->json(['success' => false, 'message' => 'Introuvable.'], 404);

            DB::table('exercices')->where('se_identifier', $request->se_identifier)->delete();
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Exercice supprimé.']);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
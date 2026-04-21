<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Exception;

class BudgetOperationController extends Controller
{
    // ==========================================
    // 1. CHARGEMENT DES DONNÉES
    // ==========================================
    public function chargerDonnees(Request $request)
    {
        $request->validate([
            'sp_identifier' => 'required|string',
            'type' => 'required|in:previsionnel,travaux'
        ]);

        $sp_id = $request->sp_identifier;
        $se_id = $request->exercise;

        if (!$se_id) {
            $latestEx = DB::table('exercices')->where('sp_identifier', $sp_id)->orderBy('start_date', 'desc')->first();
            if (!$latestEx) return response()->json(['success' => false, 'message' => 'Aucun exercice trouvé.'], 404);
            $se_id = $latestEx->se_identifier;
        }

        $totaux = $request->type === 'previsionnel' 
            ? DB::table('charges_previsionnelles')->where('se_identifier', $se_id)->first()
            : DB::table('charges_travaux')->where('se_identifier', $se_id)->first();

        $last_enc_id = $request->last_enc_id ?? 0;
        $last_dep_id = $request->last_dep_id ?? 0;
        $limit = 20;

        $encaissements = DB::table('encaissements')->where('se_identifier', $se_id)->where('id', '>', $last_enc_id)
            ->select('id as origin_id', 'date', 'title as libelle', 'amount as montant', DB::raw("'Encaissement' as type"), 'sub_type_charges')
            ->limit($limit)->get();

        $depenses = DB::table('depenses')->where('se_identifier', $se_id)->where('id', '>', $last_dep_id)
            ->select('id as origin_id', 'date', 'title as libelle', DB::raw("amount * -1 as montant"), DB::raw("'Dépense' as type"), 'sub_type_charges')
            ->limit($limit)->get();

        $operations = $encaissements->merge($depenses)->sortByDesc('date')->take($limit)->values();

        $new_last_enc_id = $operations->where('type', 'Encaissement')->max('origin_id') ?? $last_enc_id;
        $new_last_dep_id = $operations->where('type', 'Dépense')->max('origin_id') ?? $last_dep_id;

        $more_enc = DB::table('encaissements')->where('se_identifier', $se_id)->where('id', '>', $new_last_enc_id)->exists();
        $more_dep = DB::table('depenses')->where('se_identifier', $se_id)->where('id', '>', $new_last_dep_id)->exists();

        return response()->json([
            'success' => true,
            'data' => [
                'se_identifier' => $se_id,
                'totaux' => $totaux,
                'operations' => $operations,
                'pagination' => [
                    'last_enc_id' => $new_last_enc_id,
                    'last_dep_id' => $new_last_dep_id,
                    'is_there_more' => ($more_enc || $more_dep)
                ]
            ]
        ]);
    }

    public function telechargerReleve(Request $request) { /* Logique PDF ici */ }

    // ==========================================
    // 3. AJOUTER UN ENCAISSEMENT
    // ==========================================
    public function ajouterEncaissement(Request $request)
    {
        $request->validate([
            'sp_identifier' => 'required', 'se_identifier' => 'required',
            'title' => 'required', 'owner_id' => 'required',
            'type_charges' => 'required|in:previsionnel,travaux',
            'sub_type_charges' => 'required|in:planifié,exceptionnel',
            'amount' => 'required|numeric|min:0', 'date' => 'required|date'
        ]);

        $check = $this->checkExercice($request->se_identifier);
        if ($check !== true) {
            return response()->json(['success' => false, 'message' => $check], 403);
        }

        DB::beginTransaction();
        try {
            $sen_id = 'ENC-' . time() . rand(10, 99);

            $tableCharges = $request->type_charges === 'previsionnel' ? 'charges_previsionnelles' : 'charges_travaux';
            DB::table($tableCharges)->where('se_identifier', $request->se_identifier)->increment('total_encaissements', $request->amount);

            $balanceCol = $request->type_charges === 'previsionnel' ? 'balance_prev' : 'balance_trav';
            
            // 🟢 FIX HNA: 'user_id' f blast 'su_identifier'
           DB::table('user_as_owner')
    ->where('user_id', $request->owner_id)
    ->where('propriete_id', $request->sp_identifier) // 🟢 Bedli hadi
    ->increment($balanceCol, $request->amount);
            $path = "proprietes/{$request->sp_identifier}/encaissements/{$sen_id}.pdf";

            DB::table('encaissements')->insert([
                'sen_identifier' => $sen_id, 'se_identifier' => $request->se_identifier, 'owner_id' => $request->owner_id,
                'title' => $request->title, 'amount' => $request->amount, 'date' => $request->date,
                'type_charges' => $request->type_charges, 'sub_type_charges' => $request->sub_type_charges,
                'document_url' => $path, 'created_at' => now(), 'updated_at' => now() 
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Encaissement ajouté avec succès.']);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function supprimerEncaissement(Request $request) { /* Même logique */ }
    // ==========================================
    // 5. AJOUTER UNE DÉPENSE
    // ==========================================
    public function ajouterDepense(Request $request)
    {
        $request->validate([
            'sp_identifier' => 'required', 'se_identifier' => 'required', 'cle_repartition_id' => 'required',
            'title' => 'required', 'type_charges' => 'required', 'sub_type_charges' => 'required',
            'amount' => 'required|numeric|min:0', 'date' => 'required|date'
        ]);

        $check = $this->checkExercice($request->se_identifier);
        if ($check !== true) return response()->json(['success' => false, 'message' => $check], 403);

        DB::beginTransaction();
        try {
            $sdep_id = 'DEP-' . time() . rand(10, 99);
            $depenseId = DB::table('depenses')->insertGetId([
                'sdep_identifier' => $sdep_id, 'se_identifier' => $request->se_identifier, 'cle_repartition_id' => $request->cle_repartition_id,
                'title' => $request->title, 'amount' => $request->amount, 'date' => $request->date,
                'type_charges' => $request->type_charges, 'sub_type_charges' => $request->sub_type_charges,
                'created_at' => now(), 'updated_at' => now() 
            ]);

            $cle = DB::table('cle_repartitions')->where('id', $request->cle_repartition_id)->first();
            $total_tantiemes = $cle->tantiemes_total ?? 1000;

            // 🟢 L-FIX HWA HADA: Beddelna 'key_id' b 'sdk_identifier'
          // 🟢 L-FIX S-SAFI: Sta3melna 'cle_repartition_id' lli katsifti f l-payload
            $lots = DB::table('unit_to_key')
                ->join('user_owner_unit', 'unit_to_key.unit_id', '=', 'user_owner_unit.unit_id')
                ->where('unit_to_key.cle_repartition_id', $request->cle_repartition_id) // <--- HNA!
                ->where('user_owner_unit.status', true)
                ->select('user_owner_unit.user_id as final_user_id', 'unit_to_key.tantieme')
                ->get();

            $ownerAmounts = [];
            foreach ($lots as $lot) {
                $part = $request->amount * (($lot->tantieme ?? 0) / $total_tantiemes);
                $u_id = $lot->final_user_id;
                $ownerAmounts[$u_id] = ($ownerAmounts[$u_id] ?? 0) + $part;
            }

            $balanceCol = $request->type_charges === 'previsionnel' ? 'balance_prev' : 'balance_trav';

            foreach ($ownerAmounts as $su_id => $montant_calcule) {
                DB::table('depense_for_owner')->insert([
                    'depense_id' => $depenseId, 'user_id' => $su_id, 'amount_due' => $montant_calcule,
                    'created_at' => now(), 'updated_at' => now() 
                ]);
                
                DB::table('user_as_owner')
                    ->where('user_id', $su_id)
                    ->where('propriete_id', $request->sp_identifier)
                    ->decrement($balanceCol, $montant_calcule);
            }
            
            $tableCharges = $request->type_charges === 'previsionnel' ? 'charges_previsionnelles' : 'charges_travaux';
            DB::table($tableCharges)->where('se_identifier', $request->se_identifier)->increment('total_depenses', $request->amount);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Dépense ajoutée avec succès.']);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
  
    public function supprimerDepense(Request $request) { /* Même logique */ }

    // ==========================================
    // 🟢 HELPER INTELLIGENT
    // ==========================================
    private function checkExercice($se_identifier)
    {
        $ex = DB::table('exercices')->where('se_identifier', $se_identifier)->first();
        
        if (!$ex) {
            return "L'exercice '{$se_identifier}' est introuvable dans la base de données.";
        }

        return true; 
    }
}
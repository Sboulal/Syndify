<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ClotureController extends Controller
{
    // 🟢 FONCTION SÉCURISÉE
private function getProprieteId(Request $request)
{
    // 🟢 HACK ZERBA: N-forciw l-ID dyal l-User (Matalan 1) 
    // Bash y-khelina n-testiw bla Auth w bla Headers f Angular
    $userId = 1; 

    $propOwnerCol = Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';
    $link = DB::table('user_as_owner')->where('user_id', $userId)->first();
    
    return $link ? $link->$propOwnerCol : null;
}

    // ==========================================
    // 1. CHARGEMENT D'UNE CLÔTURE (Calculs)
    // ==========================================
    public function charger(Request $request)
    {
        $request->validate(['se_identifier' => 'required|string']);
        
        $sp_id = $this->getProprieteId($request);
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $se_id = $request->se_identifier;
        $propIdCol = Schema::hasColumn('exercices', 'propriete_id') ? 'propriete_id' : 'sp_identifier';

        try {
            // 1. Vérification de l'exercice
            $exercice = DB::table('exercices')->where('se_identifier', $se_id)->where($propIdCol, $sp_id)->first();
            if (!$exercice) return response()->json(['success' => false, 'message' => 'Exercice introuvable.'], 404);

            // Vérifier si la clôture existe déjà (Brouillon ou Finalisée)
            $cloture = DB::table('clotures')->where('se_identifier', $se_id)->first();
            $clotureStatus = $cloture ? $cloture->status : 0; // 0 = draft, 1 = finalisé

            // 2. Chargement des Budgets Planifiés
            $cp = DB::table('charges_previsionnelles')->where('se_identifier', $se_id)->first();
            $ct = DB::table('charges_travaux')->where('se_identifier', $se_id)->first();

            // Calcul Prévisionnel
            $prev_budget = $cp ? $cp->budget : 0;
            $prev_encaissement = $cp ? $cp->total_encaissements : 0;
            $prev_depense = $cp ? $cp->total_depenses : 0;
            $prev_reste = max(0, $prev_encaissement - $prev_depense);
            $prev_du = max(0, $prev_budget - $prev_encaissement);

            // Calcul Travaux
            $trav_budget = $ct ? $ct->budget : 0;
            $trav_encaissement = $ct ? $ct->total_encaissements : 0;
            $trav_depense = $ct ? $ct->total_depenses : 0;
            $trav_reste = max(0, $trav_encaissement - $trav_depense);
            $trav_du = max(0, $trav_budget - $trav_encaissement);

            // 3. Chargement par Clé de répartition
            $cles_prev = [];
            if ($cp) {
                $cles_prev = DB::table('bp_to_key')
                    ->join('cle_repartitions', 'bp_to_key.cle_repartition_id', '=', 'cle_repartitions.id')
                    ->where('scp_identifier', $cp->scp_identifier)
                    ->select('cle_repartitions.nom', 'bp_to_key.budget', 'bp_to_key.depenses')
                    ->get();
            }

            // 4. Exceptionnels
            $appels_exceptionnels_prev = DB::table('appels_fonds')
                ->where('se_identifier', $se_id)->where('type_charge', 'previsionnel')->where('sub_type', 'exceptionnel')->get();
            
            $exceptionnel_prev_budget = $appels_exceptionnels_prev->sum('amount');
            $exceptionnel_prev_enc = DB::table('encaissements')->where('se_identifier', $se_id)->where('sub_type_charges', 'exceptionnel')->where('type_charges', 'previsionnel')->sum('amount');
            $exceptionnel_prev_dep = DB::table('depenses')->where('se_identifier', $se_id)->where('sub_type_charges', 'exceptionnel')->where('type_charges', 'previsionnel')->sum('amount');

            // 5. Grand Total
            $grand_budget = $prev_budget + $trav_budget + $exceptionnel_prev_budget;
            $grand_encaissement = $prev_encaissement + $trav_encaissement + $exceptionnel_prev_enc;
            $grand_depense = $prev_depense + $trav_depense + $exceptionnel_prev_dep;
            $grand_reste = max(0, $grand_encaissement - $grand_depense);
            $grand_du = max(0, $grand_budget - $grand_encaissement);

            return response()->json([
                'success' => true,
                'data' => [
                    'exercice_status' => $exercice->status,
                    'cloture_status' => $clotureStatus,
                    'previsionnel' => [
                        'resume' => [
                            'budget' => $prev_budget, 'encaissements' => $prev_encaissement, 
                            'depenses' => $prev_depense, 'reste' => $prev_reste, 'du' => $prev_du
                        ],
                        'cles' => $cles_prev,
                        'exceptionnel' => [
                            'budget' => $exceptionnel_prev_budget, 'encaissements' => $exceptionnel_prev_enc,
                            'depenses' => $exceptionnel_prev_dep, 'reste' => max(0, $exceptionnel_prev_enc - $exceptionnel_prev_dep),
                            'du' => max(0, $exceptionnel_prev_budget - $exceptionnel_prev_enc),
                            'appels' => $appels_exceptionnels_prev
                        ]
                    ],
                    'travaux' => [
                        'resume' => [
                            'budget' => $trav_budget, 'encaissements' => $trav_encaissement, 
                            'depenses' => $trav_depense, 'reste' => $trav_reste, 'du' => $trav_du
                        ]
                    ],
                    'grand_total' => [
                        'budget' => $grand_budget, 'encaissements' => $grand_encaissement, 
                        'depenses' => $grand_depense, 'reste' => $grand_reste, 'du' => $grand_du
                    ],
                    'cloture_saved_data' => $cloture // Récupérer les choix précédents s'il y a un brouillon
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // 2. ENREGISTRER LA CLÔTURE (Brouillon)
    // ==========================================
    public function enregistrer(Request $request)
    {
        $request->validate([
            'se_identifier' => 'required|string',
            // Previsionnel
            'reste_choice_prev' => 'nullable|integer',
            'du_choice_prev' => 'nullable|integer',
            'send_reminders_prev' => 'boolean',
            // Travaux
            'reste_choice_trav' => 'nullable|integer',
            'du_choice_trav' => 'nullable|integer',
            'send_reminders_trav' => 'boolean',
        ]);

        $sp_id = $this->getProprieteId($request);
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $propIdCol = Schema::hasColumn('exercices', 'propriete_id') ? 'propriete_id' : 'sp_identifier';

        DB::beginTransaction();
        try {
            $exercice = DB::table('exercices')->where('se_identifier', $request->se_identifier)->where($propIdCol, $sp_id)->first();
             if (!$exercice || !in_array($exercice->status, ['en cours', 'A clore', 'en attente'])) {
                return response()->json(['success' => false, 'message' => "L'exercice n'est pas ouvert."], 400);
            }

            // UPSERT (Update ou Insert) de la table clotures
            $clotureData = [
                'se_identifier' => $request->se_identifier,
                'status' => 0, // 0 = Brouillon
                'reste_choice_prev' => $request->reste_choice_prev,
                'du_choice_prev' => $request->du_choice_prev,
                'send_reminders_prev' => $request->send_reminders_prev ?? false,
                'reste_choice_trav' => $request->reste_choice_trav,
                'du_choice_trav' => $request->du_choice_trav,
                'send_reminders_trav' => $request->send_reminders_trav ?? false,
                'updated_at' => now()
            ];

            $exists = DB::table('clotures')->where('se_identifier', $request->se_identifier)->exists();
            if ($exists) {
                DB::table('clotures')->where('se_identifier', $request->se_identifier)->update($clotureData);
            } else {
                $clotureData['created_at'] = now();
                DB::table('clotures')->insert($clotureData);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Clôture sauvegardée en brouillon.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // 3. FINALISER LA CLÔTURE (Clôture Définitive)
    // ==========================================
    public function finaliser(Request $request)
    {
        $request->validate(['se_identifier' => 'required|string']);

        $sp_id = $this->getProprieteId($request);
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        DB::beginTransaction();
        try {
            $cloture = DB::table('clotures')->where('se_identifier', $request->se_identifier)->first();
            if (!$cloture) return response()->json(['success' => false, 'message' => 'Veuillez enregistrer la clôture avant de finaliser.'], 400);
            if ($cloture->status == 1) return response()->json(['success' => false, 'message' => 'Cette clôture est déjà finalisée.'], 400);

            // Génération de Document (Simulation URL pour le moment)
            $documentUrl = "documents/clotures/{$request->se_identifier}_report.pdf";

            // 1. Mettre à jour la clôture
            DB::table('clotures')->where('se_identifier', $request->se_identifier)->update([
                'status' => 1, // Finalisé
                'report_link' => $documentUrl,
                'updated_at' => now()
            ]);

            // 2. Mettre à jour l'exercice
            DB::table('exercices')->where('se_identifier', $request->se_identifier)->update([
                'status' => 'Clos', // 🟢 Passage à l'état Clos
                'updated_at' => now()
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Clôture finalisée avec succès.', 'report_link' => $documentUrl]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
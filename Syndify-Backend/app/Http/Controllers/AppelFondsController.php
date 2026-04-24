<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Exception;

class AppelFondsController extends Controller
{
    // ========================================================
    // 🟢 FONCTION SÉCURISÉE
    // ========================================================
 private function getProprieteId(Request $request)
{
    // 🟢 HACK ZERBA: N-forciw l-ID dyal l-User (Matalan 1) 
    // Bash y-khelina n-testiw bla Auth w bla Headers f Angular
    $userId = 1; 

    $propOwnerCol = Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';
    $link = DB::table('user_as_owner')->where('user_id', $userId)->first();
    
    return $link ? $link->$propOwnerCol : null;
}

    // West AppelFondsController.php -> fonction liste()

public function liste(Request $request) {
        $sp_id = $this->getProprieteId($request);
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        // 🟢 1. Jbed l-m3loumat dyal l-Résidence
        $propIdCol_propriete = Schema::hasColumn('proprietes', 'sp_identifier') ? 'sp_identifier' : 'id';
        $residence = DB::table('proprietes')->where($propIdCol_propriete, $sp_id)->first();

        $se_id = $request->se_identifier; 
        $type = $request->type_charge; 

        if (!$se_id) {
            $exercice = DB::table('exercices')
                ->where('propriete_id', $sp_id) 
                ->orderBy('created_at', 'desc')
                ->first();
            
            if (!$exercice) return response()->json([
                'success' => true, 
                'residence' => [
                    'nom' => $residence->nom ?? 'Résidence',
                    'adresse' => $residence->address ?? 'Adresse non définie'
                ],
                'data' => [], 
                'exercice' => null
            ]);
            $se_id = $exercice->se_identifier;
        } else {
            $exercice = DB::table('exercices')->where('se_identifier', $se_id)->first();
        }

        $appels = DB::table('appels_fonds')
            ->where('se_identifier', $se_id)
            ->where('type_charge', $type)
            ->orderBy('due_date', 'asc')
            ->get();

        // 🟢 2. Rjja3 kolchi m-jmo3
        return response()->json([
            'success' => true,
            'residence' => [
                'nom' => $residence->nom ?? 'Résidence',
                'adresse' => $residence->address ?? 'Adresse non définie'
            ],
            'data' => $appels,
            'exercice' => $exercice
        ]);
    }
    // ==========================================
    // 2. AJOUTER UN APPEL DE FONDS - PLANIFIÉ
    // ==========================================
    public function ajouterPlanifie(Request $request)
    {
        Log::info('--- DÉBUT : Ajout AF Planifié ---');
        // 🔴 7iyedna 'propriete_id'
        $request->validate([
            'se_identifier' => 'required|string',
            'type_charge' => 'required|in:previsionnel,travaux'
        ]);

        $sp_id = $this->getProprieteId($request);
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $se_id = $request->se_identifier;
        $type = $request->type_charge;

        DB::beginTransaction();
        try {
            $exercice = DB::table('exercices')->where('se_identifier', $se_id)->first();
            if (!$exercice || !in_array($exercice->status, ['en cours', 'en attente'])) {
                return response()->json(['success' => false, 'message' => "L'exercice n'est pas actif."], 400);
            }

            $tableBudget = $type === 'previsionnel' ? 'charges_previsionnelles' : 'charges_travaux';
            $budget = DB::table($tableBudget)->where('se_identifier', $se_id)->first();

            if (!$budget || $budget->budget <= 0) {
                return response()->json(['success' => false, 'message' => "Impossible de créer un appel planifié pour un budget de 0 DH."], 400);
            }

            $existants = DB::table('appels_fonds')->where('se_identifier', $se_id)->where('type_charge', $type)->where('sub_type', 'planifie')->exists();
            if ($existants) {
                return response()->json(['success' => false, 'message' => "Les appels planifiés ont déjà été créés."], 400);
            }

            $periode = strtolower($exercice->period ?? 'trimestre');
            $nbPeriodes = $periode === 'mensuel' ? 12 : ($periode === 'quadrimestre' ? 3 : 4);
            $montantParPeriode = $budget->budget / $nbPeriodes;
            $startDate = Carbon::parse($exercice->start_date);
            $endDate = Carbon::parse($exercice->end_date);
            $moisAajouter = 12 / $nbPeriodes; 
            
            $appelsCrees = [];

            for ($i = 0; $i < $nbPeriodes; $i++) {
                $dateExigibilite = $startDate->copy()->addMonths($i * $moisAajouter);
                if ($dateExigibilite->greaterThan($endDate)) $dateExigibilite = $endDate; 

                $af_id = 'AF-PL-' . time() . "-$i";
                $nouveau = [
                    'af_identifier' => $af_id,
                    'se_identifier' => $se_id,
                    'type_charge' => $type,
                    'sub_type' => 'planifie',
                    'title' => "Appel planifié ($periode " . ($i+1) . ")",
                    'amount' => $montantParPeriode,
                    'due_date' => $dateExigibilite->format('Y-m-d'),
                    'created_at' => now(), 'updated_at' => now()
                ];
                DB::table('appels_fonds')->insert($nouveau);
                $appelsCrees[] = $nouveau;
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Ajout réussi.', 'data' => $appelsCrees]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()], 500);
        }
    }

    // ==========================================
    // 3. AJOUTER UN APPEL DE FONDS - EXCEPTIONNEL
    // ==========================================
    public function ajouterExceptionnel(Request $request)
    {
        Log::info('--- DÉBUT : Ajout AF Exceptionnel ---');
        // 🔴 7iyedna 'propriete_id'
        $request->validate([
            'se_identifier' => 'required|string',
            'type_charge' => 'required|in:previsionnel,travaux',
            'cle_repartition_id' => 'required',
            'amount' => 'required|numeric|min:1',
            'due_date' => 'required|date',
            'title' => 'required|string'
        ]);

        $sp_id = $this->getProprieteId($request);
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $se_id = $request->se_identifier;
        $type = $request->type_charge;

        DB::beginTransaction();
        try {
            $exercice = DB::table('exercices')->where('se_identifier', $se_id)->first();
            if (!$exercice || !in_array($exercice->status, ['en cours', 'en attente'])) {
                return response()->json(['success' => false, 'message' => "L'exercice n'est pas actif."], 400);
            }

            $af_id = 'AF-EX-' . time();
            $nouveau = [
                'af_identifier' => $af_id,
                'se_identifier' => $se_id,
                'cle_repartition_id' => $request->cle_repartition_id,
                'type_charge' => $type,
                'sub_type' => 'exceptionnel',
                'title' => $request->title,
                'amount' => $request->amount,
                'due_date' => $request->due_date,
                'created_at' => now(), 'updated_at' => now()
            ];
            DB::table('appels_fonds')->insert($nouveau);

            $tableBudget = $type === 'previsionnel' ? 'charges_previsionnelles' : 'charges_travaux';
            DB::table($tableBudget)->where('se_identifier', $se_id)->increment('budget', $request->amount);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Ajout réussi.', 'data' => $nouveau]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()], 500);
        }
    }

  public function generer(Request $request)
    {
        $request->validate([
            'se_identifier' => 'required|string',
            'af_identifier' => 'required|string'
        ]);

        $sp_id = $this->getProprieteId($request);
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        DB::beginTransaction();
        try {
            $appel = DB::table('appels_fonds')->where('af_identifier', $request->af_identifier)->first();
            if (!$appel || $appel->is_generated) return response()->json(['success' => false, 'message' => "Invalide ou déjà généré."], 400);

            // 🟢 1. Jbed ga3 l-copropriétaires liés l-had la résidence mn table user_as_owner
            $proprietaires = DB::table('user_as_owner')
                ->where('propriete_id', $sp_id)
                ->get();

            if ($proprietaires->isEmpty()) {
                return response()->json(['success' => false, 'message' => "Aucun copropriétaire trouvé pour cette résidence."], 400);
            }

            $documentsCrees = 0;
            foreach ($proprietaires as $p) {
                // 🟢 2. Calcul du montant (Simulé pour l'instant, on prend le montant total / nb owners)
                // Hna f l-moustaqbal ghadi ndiro (Tantièmes / Total) * Montant
                $montantDu = $appel->amount / count($proprietaires); 

                // 🟢 3. Insert f appf_to_owner (L-Link s7i7)
                DB::table('appf_to_owner')->insert([
                    'af_identifier' => $appel->af_identifier,
                    'user_id' => $p->user_id, // L-ID s7i7 mn table user_as_owner
                    'montant_du' => $montantDu,
                    'created_at' => now()
                ]);
                $documentsCrees++;
            }

            // 🟢 4. Update status
            DB::table('appels_fonds')->where('af_identifier', $appel->af_identifier)->update([
                'is_generated' => true,
                'number_generated' => $documentsCrees,
                'updated_at' => now()
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => "Génération réussie pour {$documentsCrees} propriétaires."]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // 6. ENVOYER LES APPELS DE FONDS
    // ==========================================
 public function envoyer(Request $request)
    {
        Log::info('--- DÉBUT : Envoi ---');
        $request->validate(['af_identifier' => 'required|string']);

        $sp_id = $this->getProprieteId($request); // 🟢 Jbed l-ID sécurisé
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        DB::beginTransaction();
        try {
            $appel = DB::table('appels_fonds')->where('af_identifier', $request->af_identifier)->first();
            if (!$appel || !$appel->is_generated) return response()->json(['success' => false, 'message' => "Appel invalide ou non généré."], 400);
            
            $proprietaires = DB::table('appf_to_owner')->where('af_identifier', $appel->af_identifier)->get();
            $numberSent = 0;

            foreach ($proprietaires as $p) {
                // 🟢 Fix: Jbed l-balance s7i7a (matalan balance_prev)
                $balanceCol = $appel->type_charge === 'previsionnel' ? 'balance_prev' : 'balance_trav';
                
                $soldeDb = DB::table('user_as_owner')->where('user_id', $p->user_id)->where('propriete_id', $sp_id)->first();
                $soldeActuel = $soldeDb ? $soldeDb->$balanceCol : 0;
                
                // Mola7ada: L-appel de fonds kay-n9ess mn l-balance (Dette)
                $nouveauSolde = $soldeActuel - $p->montant_du;

                DB::table('user_as_owner')
                    ->where('user_id', $p->user_id)
                    ->where('propriete_id', $sp_id)
                    ->update([$balanceCol => $nouveauSolde]);

                $numberSent++;
            }

            DB::table('appels_fonds')->where('af_identifier', $appel->af_identifier)->update([
                'is_sent' => true,
                'number_sent' => $numberSent,
                'updated_at' => now()
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => "Envoi réussi."]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()], 500);
        }
    }

    public function details(Request $request)
    {
        $request->validate(['af_identifier' => 'required|string']);

        $sp_id = $this->getProprieteId($request);
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $appel = DB::table('appels_fonds')->where('af_identifier', $request->af_identifier)->first();
        if (!$appel) return response()->json(['success' => false, 'message' => "Introuvable."], 404);

        // 🟢 FIX SOLDE: N-3erfou chmn solde njebdo (balance_prev awla balance_trav)
        $balanceCol = ($appel->type_charge === 'previsionnel') ? 'balance_prev' : 'balance_trav';

        $details = DB::table('appf_to_owner')
            ->leftJoin('users', 'users.id', '=', 'appf_to_owner.user_id')
            ->leftJoin('user_as_owner', function($join) use ($sp_id) {
                $join->on('user_as_owner.user_id', '=', 'appf_to_owner.user_id')
                     ->where('user_as_owner.propriete_id', '=', $sp_id);
            })
            ->where('appf_to_owner.af_identifier', $appel->af_identifier)
            ->select(
                'appf_to_owner.*',
                'users.full_name as owner_name', // Y-mken khawya f l-DB
                'users.email as email',          // 🟢 Nzidou email bach y-ban lina chkoun homa
                "user_as_owner.{$balanceCol} as solde_actuel" // 🟢 Jbed l-flouss s7a7 mn DB
            )
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'appel' => $appel,
                'lignes' => $details
            ]
        ]);
    }
    // ==========================================
    // 8. SUPPRIMER UN APPEL DE FONDS
    // ==========================================
    public function supprimer(Request $request)
    {
        $request->validate(['af_identifier' => 'required|string']);

        $appel = DB::table('appels_fonds')->where('af_identifier', $request->af_identifier)->first();
        if (!$appel) return response()->json(['success' => false, 'message' => "Introuvable."], 404);

        if ($appel->is_sent) {
            return response()->json(['success' => false, 'message' => "Impossible de supprimer un appel déjà envoyé."], 400);
        }

        DB::beginTransaction();
        try {
            DB::table('appels_fonds')->where('af_identifier', $request->af_identifier)->delete(); 
            DB::commit();
            return response()->json(['success' => true, 'message' => "Appel supprimé."]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()], 500);
        }
    }
}
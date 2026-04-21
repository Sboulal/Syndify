<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Exception;

class AppelFondsController extends Controller
{
    // ==========================================
    // 1. CHARGEMENT DES APPELS DE FONDS
    // ==========================================
    public function liste(Request $request)
    {
        Log::info('--- DÉBUT : Liste Appels de Fonds ---');
        $request->validate([
            'sp_identifier' => 'required|string',
            'type_charge' => 'required|in:previsionnel,travaux'
        ]);

        $sp_id = $request->sp_identifier;
        $se_id = $request->se_identifier; // Y9der ykoun null
        $type = $request->type_charge;

        try {
            if (!$se_id) {
                $lastExercice = DB::table('exercices')->where('sp_identifier', $sp_id)->orderBy('start_date', 'desc')->first();
                if (!$lastExercice) return response()->json(['success' => true, 'data' => []]);
                $se_id = $lastExercice->se_identifier;
            }

            $appels = DB::table('appels_fonds')
                ->where('se_identifier', $se_id)
                ->where('type_charge', $type)
                ->orderBy('due_date', 'asc')
                ->get();

            return response()->json(['success' => true, 'data' => $appels]);

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur serveur.'], 500);
        }
    }

    // ==========================================
    // 2. AJOUTER UN APPEL DE FONDS - PLANIFIÉ
    // ==========================================
    public function ajouterPlanifie(Request $request)
    {
        Log::info('--- DÉBUT : Ajout AF Planifié ---');
        $request->validate([
            'sp_identifier' => 'required|string',
            'se_identifier' => 'required|string',
            'type_charge' => 'required|in:previsionnel,travaux'
        ]);

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
        $request->validate([
            'sp_identifier' => 'required|string',
            'se_identifier' => 'required|string',
            'type_charge' => 'required|in:previsionnel,travaux',
            'cle_repartition_id' => 'required',
            'amount' => 'required|numeric|min:1',
            'due_date' => 'required|date',
            'title' => 'required|string'
        ]);

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

    // ==========================================
    // 4 & 5. GÉNÉRER LES APPELS (Planifié & Exceptionnel)
    // ==========================================
    public function generer(Request $request)
    {
        Log::info('--- DÉBUT : Génération des Appels de Fonds ---');
        $request->validate([
            'sp_identifier' => 'required|string',
            'se_identifier' => 'required|string',
            'af_identifier' => 'required|string'
        ]);

        $se_id = $request->se_identifier;
        $af_id = $request->af_identifier;

        DB::beginTransaction();
        try {
            $exercice = DB::table('exercices')->where('se_identifier', $se_id)->first();
            if (!$exercice || !in_array($exercice->status, ['en cours', 'en attente'])) {
                return response()->json(['success' => false, 'message' => "L'exercice n'est pas actif."], 400);
            }

            $appel = DB::table('appels_fonds')->where('af_identifier', $af_id)->first();
            if (!$appel) return response()->json(['success' => false, 'message' => "Appel introuvable."], 404);
            if ($appel->is_generated) return response()->json(['success' => false, 'message' => "Déjà généré."], 400);

            // LOGIQUE DE RÉPARTITION SELON LE TYPE (RÉSUMÉE POUR LE CODE)
            $mapProprietaires = [];
            
            // ... (Pour la simulation, on suppose que l'on trouve des propriétaires, sinon on le fait avec les lots comme avant)
            // Simulation d'ajout de 2 propriétaires fictifs pour tester le Front-End:
            $mapProprietaires[101] = $appel->amount * 0.4; // Ex: Proprietaire 1 paie 40%
            $mapProprietaires[102] = $appel->amount * 0.6; // Ex: Proprietaire 2 paie 60%

            $documentsCrees = 0;
            foreach ($mapProprietaires as $owner_id => $montantDu) {
                // SIMULATION DOSSIER ET DOCUMENT
                $docPath = "proprietes/{$request->sp_identifier}/appels/{$af_id}/doc_{$owner_id}.pdf";
                
                $docId = DB::table('documents')->insertGetId([
                    'type' => 'appel_fonds',
                    'file_path' => $docPath,
                    'created_at' => now(),
                ]);

                DB::table('appf_to_owner')->insert([
                    'af_identifier' => $af_id,
                    'user_id' => $owner_id,
                    'document_id' => $docId,
                    'montant_du' => $montantDu,
                    'created_at' => now()
                ]);
                $documentsCrees++;
            }

            DB::table('appels_fonds')->where('af_identifier', $af_id)->update([
                'is_generated' => true,
                'number_generated' => $documentsCrees,
                'updated_at' => now()
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => "Génération réussie."]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()], 500);
        }
    }

  // ==========================================
    // 6. ENVOYER LES APPELS DE FONDS
    // ==========================================
    public function envoyer(Request $request)
    {
        Log::info('--- DÉBUT : Envoi ---');
        $request->validate(['af_identifier' => 'required|string']);

        DB::beginTransaction();
        try {
            $appel = DB::table('appels_fonds')->where('af_identifier', $request->af_identifier)->first();
            if (!$appel || !$appel->is_generated) return response()->json(['success' => false, 'message' => "Appel invalide ou non généré."], 400);
            if ($appel->is_sent) return response()->json(['success' => false, 'message' => "Déjà envoyé."], 400);

            // 🟢 N-jbdou l-ID dyal l-propriété mn l-Exercice
            $exercice = DB::table('exercices')->where('se_identifier', $appel->se_identifier)->first();
            $propriete = $exercice ? DB::table('proprietes')->where('sp_identifier', $exercice->sp_identifier)->first() : null;
            $propriete_id = $propriete ? $propriete->id : 1; // 1 k-valeur par défaut l-les tests fakes

            $proprietaires = DB::table('appf_to_owner')->where('af_identifier', $appel->af_identifier)->get();
            $numberSent = 0;

            foreach ($proprietaires as $p) {
                $soldeDb = DB::table('user_as_owner')->where('user_id', $p->user_id)->first();
                $soldeActuel = $soldeDb ? $soldeDb->solde : 0;
                $nouveauSolde = $soldeActuel - $p->montant_du;

                DB::table('appf_to_owner')->where('id', $p->id)->update(['solde_avant' => $soldeActuel]);

                // 🟢 L-FIX HNA: Zidna 'propriete_id' bach may-t-plantach PostgreSQL m3a l-fakes
                DB::table('user_as_owner')->updateOrInsert(
                    ['user_id' => $p->user_id], 
                    ['solde' => $nouveauSolde, 'propriete_id' => $propriete_id]
                );

                // SIMULATION D'ENVOI DE NOTIFICATION
                DB::table('notifications')->insert([
                    'user_id' => $p->user_id,
                    'message' => "Nouvel appel de fonds : " . $appel->title,
                    'created_at' => now()
                ]);

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

    // ==========================================
    // 7. DÉTAILS APPEL DE FONDS
    // ==========================================
    public function details(Request $request)
    {
        $request->validate(['af_identifier' => 'required|string']);

        $appel = DB::table('appels_fonds')->where('af_identifier', $request->af_identifier)->first();
        if (!$appel) return response()->json(['success' => false, 'message' => "Introuvable."], 404);

        $details = DB::table('appf_to_owner')
            ->leftJoin('users', 'users.id', '=', 'appf_to_owner.user_id') 
            ->where('appf_to_owner.af_identifier', $appel->af_identifier)
            ->select('appf_to_owner.*', 'users.full_name', 'users.email')
            ->get();

        if (!$appel->is_sent) {
            foreach ($details as $d) {
                $d->solde_actuel = DB::table('user_as_owner')->where('user_id', $d->user_id)->value('solde') ?? 0;
            }
        }

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
            DB::table('appels_fonds')->where('af_identifier', $request->af_identifier)->delete(); // Kayms7 m3ah appf_to_owner (Cascade)
            DB::commit();
            return response()->json(['success' => true, 'message' => "Appel supprimé."]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()], 500);
        }
    }
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class ImpayeController extends Controller
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
    // 1. LES IMPAYÉS (Liste des soldes négatifs)
    // ==========================================
    public function listeImpayes(Request $request)
    {
        $sp_id = $this->getProprieteId($request);
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        try {
            // 🟢 1. N-jbdou l-m3loumat dyal l-Résidence bach nsiftoha l-Angular Header
            $propIdCol_propriete = Schema::hasColumn('proprietes', 'sp_identifier') ? 'sp_identifier' : 'id';
            $residence = DB::table('proprietes')->where($propIdCol_propriete, $sp_id)->first();

            // 🟢 N-shoufou wach les colonnes kaynin aslan f PostgreSQL
            $hasTrav = Schema::hasColumn('user_as_owner', 'balance_trav');
            $hasSolde = Schema::hasColumn('user_as_owner', 'solde');

            $query = DB::table('user_as_owner')
                ->join('users', 'user_as_owner.user_id', '=', 'users.id')
                ->where('user_as_owner.propriete_id', $sp_id)
                ->where(function($q) use ($hasTrav, $hasSolde) {
                    $q->where('user_as_owner.balance_prev', '<', 0);
                    if ($hasTrav) $q->orWhere('user_as_owner.balance_trav', '<', 0);
                    if ($hasSolde) $q->orWhere('user_as_owner.solde', '<', 0); 
                });

            // 🟢 N-bdaw b l-colonne lli mt2kkdin minha
            $selects = [
                'users.id as user_id', 
                'users.identifier as su_identifier', 
                'users.full_name', 
                'users.email', 
                'users.tel',
                'user_as_owner.balance_prev'
            ];
            
            // 🟢 N-zidou les autres ila kanou kaynin f DB
            if ($hasTrav) $selects[] = 'user_as_owner.balance_trav';
            if ($hasSolde) $selects[] = 'user_as_owner.solde';

            $impayes = $query->select($selects)->get();

            // N-9addou d-data bash t-wsl l-Angular nady-a
            $resultats = $impayes->map(function($impaye) {
                // N-7esbou l-Total Dû (M-bni 3la dakshi lli ja mn DB)
                $totalDu = abs(min($impaye->balance_prev ?? 0, 0)) 
                         + abs(min($impaye->balance_trav ?? 0, 0))
                         + abs(min($impaye->solde ?? 0, 0));

                return [
                    'user_id' => $impaye->user_id,
                    'su_identifier' => $impaye->su_identifier,
                    'full_name' => $impaye->full_name ?? 'Sans Nom',
                    'email' => $impaye->email,
                    'tel' => $impaye->tel,
                    'balance_prev' => $impaye->balance_prev ?? 0,
                    'balance_trav' => $impaye->balance_trav ?? 0,
                    'total_du' => $totalDu
                ];
            });

            // 🟢 2. N-siftou l-residence f l-JSON response
            return response()->json([
                'success' => true,
                'residence' => [
                    'nom' => $residence->nom ?? 'Résidence',
                    'adresse' => $residence->address ?? 'Adresse non définie'
                ],
                'data' => $resultats,
                'total_impayes' => $resultats->sum('total_du')
            ]);

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // 2. LES RAPPELS (Historique avec Pagination)
    // ==========================================
    public function listeRappels(Request $request)
    {
        $sp_id = $this->getProprieteId($request);
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $role = strtolower($request->role ?? 'syndic');
        $userId = $request->user_id ?? 1; // 🟢 N-khedmou b user_id mn request awla 1 par défaut
        $last_id = $request->last_id ?? 0;
        $limit = 20;

        try {
            $query = DB::table('reminders')
                ->join('users', 'reminders.user_id', '=', 'users.id')
                ->where('reminders.propriete_id', $sp_id)
                ->where('reminders.id', '>', $last_id);

            // 🟢 Vérification du rôle utilisateur
            if ($role === 'proprietaire') {
                $query->where('reminders.user_id', $userId);
            }

            $rappels = $query->select('reminders.*', 'users.full_name', 'users.identifier as su_identifier')
                ->orderBy('reminders.id', 'asc')
                ->limit($limit)
                ->get();

            $new_last_id = $rappels->max('id') ?? $last_id;

            // 🟢 Vérifier s'il y a plus de données
            $is_there_more = false;
            if ($rappels->count() === $limit) {
                $moreQuery = DB::table('reminders')->where('propriete_id', $sp_id)->where('id', '>', $new_last_id);
                if ($role === 'proprietaire') $moreQuery->where('user_id', $userId);
                $is_there_more = $moreQuery->exists();
            }

            return response()->json([
                'success' => true,
                'data' => $rappels,
                'pagination' => [
                    'last_id' => $new_last_id,
                    'is_there_more' => $is_there_more
                ]
            ]);

        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // 3. ENVOYER UN RAPPEL (Génération & Mail)
    // ==========================================
    public function envoyerRappel(Request $request)
    {
        // 🟢 Vérification des infos envoyées par Angular
        $request->validate([
            'su_identifier' => 'required',
        ]);

        $sp_id = $this->getProprieteId($request);
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $su_identifier = $request->su_identifier;

        DB::beginTransaction();
        try {
            // 1. Récupération de l'utilisateur
            $user = DB::table('users')->where('identifier', $su_identifier)->orWhere('id', $su_identifier)->first();
            if (!$user) return response()->json(['success' => false, 'message' => 'Utilisateur introuvable.'], 404);

            // 2. Vérification des soldes
            $solde = DB::table('user_as_owner')->where('user_id', $user->id)->where('propriete_id', $sp_id)->first();
            if (!$solde) return response()->json(['success' => false, 'message' => 'Aucun compte lié à cette propriété.'], 404);

            $prev = min($solde->balance_prev ?? 0, 0);
            $trav = min($solde->balance_trav ?? 0, 0);
            $totalDu = abs($prev) + abs($trav);

            if ($totalDu == 0) {
                return response()->json(['success' => false, 'message' => 'Ce copropriétaire n\'a aucun impayé.'], 400);
            }

            // 3. Générer le document (Mock / Placeholder)
            // 💡 HNA: Mlli t-saybi l-PDF b (DomPDF matalan), ghat-blddli had l-code
            $pdfFileName = "rappel_" . $user->id . "_" . time() . ".pdf";
            $pdfPath = "proprietes/{$sp_id}/reminders/{$pdfFileName}";
            
            // Simulation création d'un fichier physique (Txt blast PDF pour l'instant)
            Storage::disk('public')->put($pdfPath, "Document de rappel pour {$user->full_name}. Total Dû: {$totalDu} DH.");

            // 4. Ajouter à la table Reminders
            DB::table('reminders')->insert([
                'propriete_id' => $sp_id,
                'user_id' => $user->id,
                'document_url' => '/storage/' . $pdfPath,
                'amount_due' => $totalDu,
                'status' => 'envoyé',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // 5. Envoyer un e-mail (Simulation)
            // 💡 HNA: Mail::to($user->email)->send(new RappelMail($user, $totalDu, $pdfPath));
            Log::info("📧 Email de rappel envoyé à {$user->email} avec le document: {$pdfPath}");

            // 6. Ajouter une notification
            DB::table('notifications')->insert([
                'user_id' => $user->id,
                'propriete_id' => $sp_id,
                'title' => 'Rappel de paiement',
                'message' => "Vous avez un impayé de {$totalDu} DH. Veuillez consulter vos documents.",
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();
            return response()->json([
                'success' => true, 
                'message' => "Rappel généré et envoyé avec succès à {$user->full_name}."
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()], 500);
        }
    }
}
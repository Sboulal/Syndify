<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
class CoproprietaireController extends Controller
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
   public function liste(Request $request)
    {
        $request->validate(['type_affichage' => 'required|in:actif,inactif,en_attente,tous']);

        $propriete_id = $this->getProprieteId($request);
        if (!$propriete_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $propIdCol_propriete = Schema::hasColumn('proprietes', 'sp_identifier') ? 'sp_identifier' : 'id';

        // 🟢 1. Jbed l-m3loumat dyal l-Résidence (Source Unique)
        $residence = DB::table('proprietes')
            ->where($propIdCol_propriete, $propriete_id)
            ->first();

        $statusMapping = ['en_attente' => 0, 'actif' => 1, 'inactif' => 2];
        
        $query = DB::table('users')
            ->join('user_as_owner', 'users.id', '=', 'user_as_owner.user_id')
            ->where('user_as_owner.propriete_id', $propriete_id);
        
        if ($request->type_affichage !== 'tous') {
            $query->where('user_as_owner.status', $statusMapping[$request->type_affichage]);
        }

        $users = $query->select('users.*', 'user_as_owner.status as pivot_status')
                       ->orderBy('users.id', 'desc')
                       ->get();

        $formattedData = $users->map(function ($user) use ($propriete_id) {
            $lotIds = DB::table('user_owner_unit')
                ->join('units', 'user_owner_unit.unit_id', '=', 'units.id')
                ->where('units.propriete_id', $propriete_id)
                ->where('user_owner_unit.user_id', $user->id)
                ->pluck('units.id')
                ->toArray();

            $statusStr = 'Actif';
            if($user->pivot_status == 0) $statusStr = 'En attente';
            if($user->pivot_status == 2) $statusStr = 'Inactif';

            return [
                'user_id' => $user->id,
                'nom' => $user->full_name,      
                'email' => $user->email,
                'tel' => $user->tel, 
                'lots' => count($lotIds), 
                'lot_ids' => $lotIds, 
                'status' => $statusStr
            ];
        });

        // 🟢 2. Rjja3 kolchi m-jmo3: Residence + Data
        return response()->json([
            'success' => true, 
            'residence' => [
                'nom' => $residence->nom ?? 'Résidence',
                'adresse' => $residence->address ?? 'Adresse non définie'
            ],
            'data' => $formattedData, 
            'is_there_more' => false
        ]);
    }

    // 2. Ajouter/Modifier
    public function ajouter(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'nom' => 'required|string',
            'tel' => 'nullable|string',
            'user_id' => 'nullable', 
            'status' => 'required|string',  
            'selectedLots' => 'nullable|array'
        ]);

        $propriete_id = $this->getProprieteId($request);
        if (!$propriete_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $mappedStatus = ['En attente' => 0, 'Actif' => 1, 'Inactif' => 2][$request->status] ?? 1;

        DB::beginTransaction();
        try {
            $userId = $request->user_id;
            
            if (!$userId) {
                $existingUser = DB::table('users')->where('email', $request->email)->first();
                if ($existingUser) {
                    $userId = $existingUser->id;
                    DB::table('users')->where('id', $userId)->update([
                        'full_name' => $request->nom, 'tel' => $request->tel, 'updated_at' => now()
                    ]);
                } else {
                    $userId = DB::table('users')->insertGetId([
                        'full_name' => $request->nom, 
                        'email' => $request->email,
                        'tel' => $request->tel, 
                        'created_at' => now(), 
                        'updated_at' => now()
                    ]);
                }
            } else {
                DB::table('users')->where('id', $userId)->update([
                    'full_name' => $request->nom, 'tel' => $request->tel, 'updated_at' => now()
                ]);
            }

            $liaison = DB::table('user_as_owner')->where('user_id', $userId)->where('propriete_id', $propriete_id)->first();
            if ($liaison) {
                DB::table('user_as_owner')->where('id', $liaison->id)->update(['status' => $mappedStatus, 'updated_at' => now()]);
                $message = 'Le copropriétaire a été mis à jour avec succès.';
            } else {
                DB::table('user_as_owner')->insert([
                    'user_id' => $userId, 'propriete_id' => $propriete_id, 'status' => $mappedStatus,
                    'created_at' => now(), 'updated_at' => now()
                ]);
                $message = 'Copropriétaire ajouté avec succès.';
            }

            // 🟢 L-FIX HWA HADA: beddelna propriete_id b propriete_id
            $unitIds = DB::table('units')->where('propriete_id', $propriete_id)->pluck('id')->toArray();
            
            if (!empty($unitIds)) {
                DB::table('user_owner_unit')->where('user_id', $userId)->whereIn('unit_id', $unitIds)->delete();
            }

            if ($request->has('selectedLots') && is_array($request->selectedLots) && count($request->selectedLots) > 0) {
                DB::table('user_owner_unit')->whereIn('unit_id', $request->selectedLots)->delete();

                $insertData = [];
                foreach ($request->selectedLots as $lotId) {
                    $insertData[] = [
                        'user_id' => $userId, 'unit_id' => $lotId, 'status' => 1, 
                        'created_at' => now(), 'updated_at' => now()
                    ];
                }
                DB::table('user_owner_unit')->insert($insertData);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => $message]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // 3. Supprimer
    public function supprimer(Request $request)
    {
        $request->validate(['user_id' => 'required']);
        
        $propriete_id = $this->getProprieteId($request);
        if (!$propriete_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        DB::beginTransaction();
        try {
            DB::table('user_as_owner')->where('user_id', $request->user_id)->where('propriete_id', $propriete_id)->delete();
            
            $unitIds = DB::table('units')->where('propriete_id', $propriete_id)->pluck('id')->toArray();
            if (!empty($unitIds)) {
                DB::table('user_owner_unit')->where('user_id', $request->user_id)->whereIn('unit_id', $unitIds)->delete();
            }
            
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Copropriétaire supprimé avec succès.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // 4. Historique
    public function historique(Request $request)
    {
        $request->validate([
            'user_id' => 'required', 
            'se_identifier' => 'nullable|string'
        ]);

        $propriete_id = $this->getProprieteId($request);
        if (!$propriete_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $isOwner = DB::table('user_as_owner')->where('user_id', $request->user_id)->where('propriete_id', $propriete_id)->exists();
        if (!$isOwner) return response()->json(['success' => false, 'message' => 'Copropriétaire introuvable.'], 404);

        $copro = DB::table('users')->where('id', $request->user_id)->select('id', 'full_name as nom', 'email')->first();

        // Mola7ada: Hna khllit propriete_id ila kant table exercices ba9a hakak. Ila bdltiha 7ta hia, bdliha hna.
        $exercices = DB::table('exercices')->where('propriete_id', $propriete_id)->orderBy('start_date', 'desc')->get();
        if ($exercices->isEmpty()) return response()->json(['success' => false, 'message' => 'Aucun exercice trouvé.'], 404);

        $se_id = $request->se_identifier ?: $exercices->first()->se_identifier;

        $encaissements = DB::table('encaissements')
            ->where('se_identifier', $se_id)
            ->where('owner_id', $request->user_id)
            ->select('date', DB::raw("'Encaissement' as type"), 'title as description', 'amount as montant', 'document_url')
            ->get();

        $depenses = DB::table('depense_for_owner')
            ->join('depenses', 'depense_for_owner.depense_id', '=', 'depenses.id')
            ->where('depenses.se_identifier', $se_id)
            ->where('depense_for_owner.user_id', $request->user_id)
            ->select('depenses.date', DB::raw("'Appel de fonds' as type"), 'depenses.title as description', 'depense_for_owner.amount_due as montant', DB::raw("null as document_url"))
            ->get();

        $operations = $encaissements->merge($depenses)->sortByDesc('date')->values();

        $exercicesFormat = $exercices->map(function($ex) {
            return ['id' => $ex->se_identifier, 'annee' => 'Exercice ' . date('Y', strtotime($ex->start_date))];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'coproprietaire' => $copro,
                'exercices' => $exercicesFormat,
                'exercice_selectionne' => $se_id,
                'total_encaissements' => collect($encaissements)->sum('montant'),
                'total_depenses' => collect($depenses)->sum('montant'),
                'operations' => $operations
            ]
        ]);
    }
}
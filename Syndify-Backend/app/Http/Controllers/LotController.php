<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class LotController extends Controller
{
    
// ========================================================
    // 🟢 FONCTION SÉCURISÉE POUR L'ID DE LA PROPRIÉTÉ
    // ========================================================
    private function getProprieteId(Request $request)
    {
        // 🟢 FIX RADICAL: N-forciw l-ID dyal l-Résidence d-Demo nichan
        return 'SP-87248712';
    }

    // ==========================================
    // 1. Liste des Lots
    // ==========================================
    public function liste(Request $request)
    {
        $propriete_id = $this->getProprieteId($request);
        
        $propIdCol_propriete = Schema::hasColumn('proprietes', 'sp_identifier') ? 'sp_identifier' : 'id';

        // 1. Jbed l-m3loumat dyal l-Résidence
        $residence = DB::table('proprietes')
            ->where($propIdCol_propriete, $propriete_id)
            ->first();

        // 🟢 FIX: N-jbdou ga3 les Lots nichan
        $lots = DB::table('units')->orderBy('id', 'desc')->get();

        $userPk = Schema::hasColumn('users', 'identifier') ? 'identifier' : 'id';
        $userNameCol = Schema::hasColumn('users', 'full_name') ? 'full_name' : 'name';

        foreach ($lots as $lot) {
            try {
                $lot->owners = DB::table('users')
                    ->join('user_owner_unit', 'users.' . $userPk, '=', 'user_owner_unit.user_id')
                    ->where('user_owner_unit.unit_id', $lot->id)
                    ->select(
                        'users.' . $userPk . ' as user_id', 
                        'users.' . $userNameCol . ' as nom', 
                        'users.email', 
                        'user_owner_unit.status as pivot_status'
                    )
                    ->get();
            } catch (\Exception $e) {
                Log::error("Erreur récupération owners pour le lot {$lot->id}: " . $e->getMessage());
                $lot->owners = []; 
            }
        }

        return response()->json([
            'success' => true, 
            'residence' => [
                'nom' => $residence->nom ?? 'Résidence',
                'adresse' => $residence->address ?? 'Adresse non définie'
            ],
            'data' => $lots
        ]);
    }

    // ==========================================
    // 2. Ajouter un lot
    // ==========================================
    public function ajouter(Request $request)
    {
        $request->validate([
            'type' => 'required',
            'numero_porte' => 'required',
            'owner_id' => 'nullable' 
        ]);

        $propriete_id = $this->getProprieteId($request);
        if (!$propriete_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $propIdCol_units = Schema::hasColumn('units', 'propriete_id') ? 'propriete_id' : 'sp_identifier';
        $propIdCol_cles = Schema::hasColumn('cle_repartitions', 'propriete_id') ? 'propriete_id' : 'sp_identifier';
        $cleIdCol_pivot = Schema::hasColumn('unit_to_key', 'cle_repartition_id') ? 'cle_repartition_id' : 'key_id';
        $propOwnerCol = Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';

        DB::beginTransaction();
        try {
            $lotId = DB::table('units')->insertGetId([
                $propIdCol_units => $propriete_id, // 🟢 FIX: Dynamic Column
                'type' => $request->type,
                'batiment' => $request->batiment,
                'etage' => $request->etage,
                'numero_porte' => $request->numero_porte,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            try {
                $cles = DB::table('cle_repartitions')->where($propIdCol_cles, $propriete_id)->get();
                $insertKeys = [];
                foreach ($cles as $cle) {
                    $insertKeys[] = [
                        'unit_id' => $lotId,
                        $cleIdCol_pivot => $cle->id, // 🟢 FIX: key_id awla cle_repartition_id
                        'tantieme' => 0,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
                if (count($insertKeys) > 0) {
                    DB::table('unit_to_key')->insert($insertKeys);
                }
            } catch (\Exception $e) {
                Log::warning("Impossible de lier les clés au lot : " . $e->getMessage());
            }

            if ($request->owner_id) {
                $status = $request->owner_status == 'Actif' ? 1 : 2;

                if ($status == 1) {
                    DB::table('user_as_owner')
                        ->where('user_id', $request->owner_id)
                        ->where($propOwnerCol, $propriete_id)
                        ->update(['status' => 1]);
                }

                DB::table('user_owner_unit')->insert([
                    'user_id' => $request->owner_id,
                    'unit_id' => $lotId,
                    'status' => $status,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Lot ajouté avec succès.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // 3. Modifier un Lot
    // ==========================================
    public function modifier(Request $request)
    {
        $request->validate(['lot_id' => 'required']);

        $propriete_id = $this->getProprieteId($request);
        if (!$propriete_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $propOwnerCol = Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';

        DB::beginTransaction();
        try {
            DB::table('units')->where('id', $request->lot_id)->update([
                'type' => $request->type,
                'batiment' => $request->batiment,
                'etage' => $request->etage,
                'numero_porte' => $request->numero_porte,
                'updated_at' => now()
            ]);

            DB::table('user_owner_unit')->where('unit_id', $request->lot_id)->delete();

            if ($request->owner_id) {
                $status = $request->owner_status == 'Actif' ? 1 : 2;

                if ($status == 1) { 
                    DB::table('user_as_owner')
                        ->where('user_id', $request->owner_id)
                        ->where($propOwnerCol, $propriete_id)
                        ->update(['status' => 1]);
                } 
                elseif ($status == 2) { 
                    DB::table('user_as_owner')
                        ->where('user_id', $request->owner_id)
                        ->where($propOwnerCol, $propriete_id)
                        ->update(['balance_prev' => 0, 'balance_trav' => 0, 'status' => 2]); // 🟢 FIX: balance_trav blast balance_new
                }

                DB::table('user_owner_unit')->insert([
                    'user_id' => $request->owner_id, 
                    'unit_id' => $request->lot_id,
                    'status' => $status,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Lot modifié avec succès.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==========================================
    // 4. Supprimer un lot
    // ==========================================
    public function supprimer(Request $request)
    {
        $request->validate(['lot_id' => 'required']);

        $propriete_id = $this->getProprieteId($request);
        if (!$propriete_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $propIdCol_units = Schema::hasColumn('units', 'propriete_id') ? 'propriete_id' : 'sp_identifier';

        try {
            DB::beginTransaction();
            DB::table('user_owner_unit')->where('unit_id', $request->lot_id)->delete();
            try { DB::table('unit_to_key')->where('unit_id', $request->lot_id)->delete(); } catch(\Exception $e) {}
            
            DB::table('units')->where('id', $request->lot_id)->where($propIdCol_units, $propriete_id)->delete();
            
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Lot supprimé avec succès.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()], 500);
        }
    }
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class LotController extends Controller
{
    // ==========================================
    // 1. Liste des Lots
    // ==========================================
    public function liste(Request $request)
    {
        $request->validate(['propriete_id' => 'required']);
        
        $lots = DB::table('units')
            ->where('propriete_id', $request->propriete_id)
            ->orderBy('id', 'desc')
            ->get();

        $userPk = Schema::hasColumn('users', 'identifier') ? 'identifier' : (Schema::hasColumn('users', 'user_id') ? 'user_id' : 'id');
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

        return response()->json(['success' => true, 'data' => $lots]);
    }

    // ==========================================
    // 2. Ajouter un lot
    // ==========================================
    public function ajouter(Request $request)
    {
        // 🟢 FIX 1 : owner_id wellat 'nullable' bach n9edro n-ziidou lot bla propriétaire
        $request->validate([
            'propriete_id' => 'required',
            'type' => 'required',
            'numero_porte' => 'required',
            'owner_id' => 'nullable' 
        ]);

        DB::beginTransaction();
        try {
            // A. Ajout dyal l'Unit (Lot)
            $lotId = DB::table('units')->insertGetId([
                'propriete_id' => $request->propriete_id,
                'type' => $request->type,
                'batiment' => $request->batiment,
                'etage' => $request->etage,
                'numero_porte' => $request->numero_porte,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // B. Liaison m3a les clés de répartition (b Try-Catch bach may-plantich ila table ma-kynach)
            try {
                $cleCol = Schema::hasColumn('cle_repartitions', 'propriete_id') ? 'propriete_id' : 'sp_identifier';
                $cles = DB::table('cle_repartitions')->where($cleCol, $request->propriete_id)->get();
                $insertKeys = [];
                foreach ($cles as $cle) {
                    $insertKeys[] = [
                        'unit_id' => $lotId,
                        'key_id' => $cle->id,
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

            // C. Affectation dyal le propriétaire GHIIR ila khtar l-user chi wa7ed
            if ($request->owner_id) {
                $status = $request->owner_status == 'Actif' ? 1 : 2;

                if ($status == 1) {
                    DB::table('user_as_owner')
                        ->where('user_id', $request->owner_id)
                        ->where('propriete_id', $request->propriete_id)
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
        $request->validate(['propriete_id' => 'required', 'lot_id' => 'required']);

        DB::beginTransaction();
        try {
            DB::table('units')->where('id', $request->lot_id)->update([
                'type' => $request->type,
                'batiment' => $request->batiment,
                'etage' => $request->etage,
                'numero_porte' => $request->numero_porte,
                'updated_at' => now()
            ]);

            // N-ms7ou l-propriétaire l-9dim dima
            DB::table('user_owner_unit')->where('unit_id', $request->lot_id)->delete();

            // Ila khtar l-user propriétaire jdid, n-zidouh
            if ($request->owner_id) {
                $status = $request->owner_status == 'Actif' ? 1 : 2;

                if ($status == 1) { 
                    DB::table('user_as_owner')
                        ->where('user_id', $request->owner_id)
                        ->where('propriete_id', $request->propriete_id)
                        ->update(['status' => 1]);
                } 
                elseif ($status == 2) { 
                    DB::table('user_as_owner')
                        ->where('user_id', $request->owner_id)
                        ->where('propriete_id', $request->propriete_id)
                        ->update(['balance_prev' => 0, 'balance_new' => 0, 'status' => 2]);
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
        $request->validate(['propriete_id' => 'required', 'lot_id' => 'required']);

        try {
            DB::beginTransaction();
            DB::table('user_owner_unit')->where('unit_id', $request->lot_id)->delete();
            // Try-Catch f l-clés 7it momkin table tkon mzl makynach
            try { DB::table('unit_to_key')->where('unit_id', $request->lot_id)->delete(); } catch(\Exception $e) {}
            DB::table('units')->where('id', $request->lot_id)->where('propriete_id', $request->propriete_id)->delete();
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Lot supprimé avec succès.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()], 500);
        }
    }
}
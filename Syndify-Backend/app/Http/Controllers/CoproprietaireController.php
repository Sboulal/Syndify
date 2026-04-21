<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CoproprietaireController extends Controller
{
    // 1. Liste des copropriétaires
    public function liste(Request $request)
    {
        $request->validate([
            'propriete_id' => 'required',
            'type_affichage' => 'required|in:actif,inactif,en_attente,tous',
        ]);

        $statusMapping = ['en_attente' => 0, 'actif' => 1, 'inactif' => 2];
        
        $query = DB::table('users')
            ->join('user_as_owner', 'users.id', '=', 'user_as_owner.user_id')
            ->where('user_as_owner.propriete_id', $request->propriete_id);
        
        if ($request->type_affichage !== 'tous') {
            $query->where('user_as_owner.status', $statusMapping[$request->type_affichage]);
        }

        $users = $query->select('users.*', 'user_as_owner.status as pivot_status')->orderBy('users.id', 'desc')->get();

        $formattedData = $users->map(function ($user) use ($request) {
            $lotIds = DB::table('user_owner_unit')
                ->join('units', 'user_owner_unit.unit_id', '=', 'units.id')
                ->where('units.propriete_id', $request->propriete_id)
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

        return response()->json(['success' => true, 'data' => $formattedData, 'is_there_more' => false]);
    }

    // 2. Ajouter ou Modifier un Copropriétaire
    public function ajouter(Request $request)
    {
        $request->validate([
            'propriete_id' => 'required',
            'email' => 'required|email',
            'nom' => 'required|string',
            'tel' => 'nullable|string',
            'user_id' => 'nullable', 
            'status' => 'required|string',  
            'selectedLots' => 'nullable|array'
        ]);

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
                        'password' => bcrypt('password123'),
                        'created_at' => now(), 
                        'updated_at' => now()
                    ]);
                }
            } else {
                DB::table('users')->where('id', $userId)->update([
                    'full_name' => $request->nom, 'tel' => $request->tel, 'updated_at' => now()
                ]);
            }

            $liaison = DB::table('user_as_owner')->where('user_id', $userId)->where('propriete_id', $request->propriete_id)->first();
            if ($liaison) {
                DB::table('user_as_owner')->where('id', $liaison->id)->update(['status' => $mappedStatus, 'updated_at' => now()]);
                $message = 'Le copropriétaire a été mis à jour avec succès.';
            } else {
                DB::table('user_as_owner')->insert([
                    'user_id' => $userId, 'propriete_id' => $request->propriete_id, 'status' => $mappedStatus,
                    'created_at' => now(), 'updated_at' => now()
                ]);
                $message = 'Copropriétaire ajouté avec succès.';
            }

            // Liaison Lots
            $unitIds = DB::table('units')->where('propriete_id', $request->propriete_id)->pluck('id')->toArray();
            if (!empty($unitIds)) {
                DB::table('user_owner_unit')->where('user_id', $userId)->whereIn('unit_id', $unitIds)->delete();
            }

            if ($request->has('selectedLots') && is_array($request->selectedLots) && count($request->selectedLots) > 0) {
                // 🟢 FIX IMPORTANT: Nms7ou les anciens propriétaires dyal had les lots bach mayw9e3ch doublon
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

    // 3. Supprimer un Copropriétaire
    public function supprimer(Request $request)
    {
        $request->validate(['propriete_id' => 'required', 'user_id' => 'required']);
        
        DB::beginTransaction();
        try {
            DB::table('user_as_owner')->where('user_id', $request->user_id)->where('propriete_id', $request->propriete_id)->delete();
            
            $unitIds = DB::table('units')->where('propriete_id', $request->propriete_id)->pluck('id')->toArray();
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
}
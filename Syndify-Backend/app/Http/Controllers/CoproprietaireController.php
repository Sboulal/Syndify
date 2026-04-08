<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserAsOwner;
use App\Models\UserOwnerUnit;
use App\Models\Lot;
use Illuminate\Support\Facades\DB;

class CoproprietaireController extends Controller
{
    // 1. Liste des copropriétaires
    public function liste(Request $request)
    {
        $request->validate([
            'propriete_id' => 'required',
            'type_affichage' => 'required|in:actif,inactif,en_attente',
        ]);

        $statusMapping = [
            'en_attente' => 0,
            'actif' => 1,
            'inactif' => 2
        ];
        $status = $statusMapping[$request->type_affichage];

        $users = User::whereHas('coproprietes', function ($query) use ($request, $status) {
            $query->where('propriete_id', $request->propriete_id)
                  ->where('user_as_owner.status', $status);
        })->get();

        $formattedData = $users->map(function ($user) use ($request) {
            
            $lotsCount = UserOwnerUnit::join('units', 'user_owner_unit.unit_id', '=', 'units.id')
                ->where('units.propriete_id', $request->propriete_id)
                ->where('user_owner_unit.user_id', $user->identifier)
                ->count();

            return [
                'user_id' => $user->identifier, 
                'nom' => $user->full_name,      
                'email' => $user->email,
                'tel' => $user->tel, 
                'lots' => $lotsCount > 0 ? $lotsCount . ' lots' : '-- Non affecté --',
                'status' => $request->type_affichage == 'en_attente' ? 'En attente' : ucfirst($request->type_affichage)
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedData,
            'is_there_more' => false
        ]);
    }

    // 2. Ajouter ou Modifier un Copropriétaire (Automatique)
    public function ajouter(Request $request)
    {
        $request->validate([
            'propriete_id' => 'required',
            'email' => 'required|email',
            'nom' => 'required|string',
            'tel' => 'nullable|string',
            'user_id' => 'nullable|string', 
            'status' => 'required|string',  
            // 🛑 ملاحظة: المودال ديالك كيسيفط "lots" ولكن حنا كنتجاهلوه هنا حيت الـ liaison كدار بـ ID ماشي بـ Texte
        ]);

        $statusMapping = [
            'En attente' => 0,
            'Actif' => 1,
            'Inactif' => 2
        ];
        $mappedStatus = $statusMapping[$request->status] ?? 1;

        DB::beginTransaction();
        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                $identifier = $request->filled('user_id') ? $request->user_id : 'SU-' . time();

                $user = User::create([
                    'identifier' => $identifier,
                    'full_name' => $request->nom,
                    'email' => $request->email,
                    'tel' => $request->tel,
                    'password' => bcrypt('password123'),
                ]);
            } else {
                $user->update([
                    'full_name' => $request->nom,
                    'tel' => $request->tel,
                ]);
            }

            $liaison = UserAsOwner::where('user_id', $user->identifier)
                                  ->where('propriete_id', $request->propriete_id)
                                  ->first();

            if ($liaison) {
                $liaison->update(['status' => $mappedStatus]);
                $message = 'Le copropriétaire a été mis à jour avec succès.';
            } else {
                UserAsOwner::create([
                    'user_id' => $user->identifier,
                    'propriete_id' => $request->propriete_id,
                    'status' => $mappedStatus
                ]);
                $message = 'Copropriétaire ajouté avec succès.';
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
        $request->validate([
            'propriete_id' => 'required',
            'user_id' => 'required'
        ]);

        DB::beginTransaction();
        try {
            UserAsOwner::where('user_id', $request->user_id)
                       ->where('propriete_id', $request->propriete_id)
                       ->delete();

            $unitIds = Lot::where('propriete_id', $request->propriete_id)->pluck('id');
            UserOwnerUnit::where('user_id', $request->user_id)
                         ->whereIn('unit_id', $unitIds)
                         ->delete();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Copropriétaire supprimé avec succès.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
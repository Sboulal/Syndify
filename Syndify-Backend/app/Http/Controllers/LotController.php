<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lot; 
use Illuminate\Support\Facades\DB;

class LotController extends Controller
{
    // 1. Liste des Lots (Nzidou fiha l'affectation dyal l'owner bach tban f l'modal)
    public function liste(Request $request)
    {
        $request->validate(['propriete_id' => 'required']);
        
        $lots = Lot::where('propriete_id', $request->propriete_id)
            ->with(['owners' => function($query) {
                // Kanjibou l'owner w l'status dyalo mn pivot table
                $query->select('users.id', 'users.full_name', 'user_owner_unit.status as pivot_status');
            }])
            ->orderBy('id', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $lots]);
    }

    // 2. Ajouter un lot
    public function ajouter(Request $request)
    {
        $request->validate([
            'propriete_id' => 'required',
            'type' => 'required',
            'numero_porte' => 'required'
        ]);

        DB::beginTransaction();
        try {
            // A. Création dyal l'Lot
            $lot = Lot::create([
                'propriete_id' => $request->propriete_id,
                'type' => $request->type,
                'batiment' => $request->batiment,
                'etage' => $request->etage,
                'numero_porte' => $request->numero_porte
            ]);

            // B. 🟢 Créer la connexion avec les clés de répartition existantes (Tantième par défaut = 0)
            $cles = DB::table('cle_repartitions')->where('propriete_id', $request->propriete_id)->get();
            $insertKeys = [];
            foreach ($cles as $cle) {
                $insertKeys[] = [
                    'unit_id' => $lot->id,
                    'key_id' => $cle->id,
                    'tantieme' => 0
                ];
            }
            if (count($insertKeys) > 0) {
                DB::table('unit_to_key')->insert($insertKeys);
            }

            // C. 🟢 Affectation d'un propriétaire (Si choisi f l'Modal)
            if ($request->filled('owner_id')) {
                $status = $request->owner_status == 'Actif' ? 1 : 2;

                if ($status == 1) {
                    // Check: Un seul propriétaire actif maximum
                    $activeOwners = DB::table('user_owner_unit')->where('unit_id', $lot->id)->where('status', 1)->count();
                    if ($activeOwners > 0) {
                        return response()->json(['success' => false, 'message' => "Un autre propriétaire est déjà actif sur ce lot."], 400);
                    }
                }

                DB::table('user_owner_unit')->insert([
                    'user_id' => $request->owner_id,
                    'unit_id' => $lot->id,
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

    // 3. Modifier un Lot
    public function modifier(Request $request)
    {
        $request->validate([
            'propriete_id' => 'required',
            'lot_id' => 'required'
        ]);

        DB::beginTransaction();
        try {
            $lot = Lot::where('id', $request->lot_id)->where('propriete_id', $request->propriete_id)->firstOrFail();

            // A. Update des informations du lot
            $lot->update($request->only(['type', 'batiment', 'etage', 'numero_porte']));

            // B. 🟢 Gestion de l'affectation du propriétaire
            if ($request->filled('owner_id')) {
                $status = $request->owner_status == 'Actif' ? 1 : 2;

                // Si on veut AJOUTER ou REACTIVER un propriétaire
                if ($status == 1) { 
                    $activeOwnersCount = DB::table('user_owner_unit')
                        ->where('unit_id', $lot->id)
                        ->where('status', 1)
                        ->where('user_id', '!=', $request->owner_id) // Vérifier les AUTRES propriétaires
                        ->count();

                    if ($activeOwnersCount > 0) {
                        DB::rollBack();
                        return response()->json(['success' => false, 'message' => "Erreur : Ce lot a déjà un autre propriétaire actif."], 400);
                    }
                } 
                // Si on veut DESACTIVER un propriétaire
                elseif ($status == 2) { 
                    // ⚠️ Vérifier si les soldes sont négatifs (à adapter selon le nom de ta table `soldes` w `montant`)
                    $soldeNegatif = DB::table('soldes') 
                        ->where('user_id', $request->owner_id)
                        ->where('propriete_id', $request->propriete_id)
                        ->where('montant', '<', 0)
                        ->exists();

                    if ($soldeNegatif) {
                        DB::rollBack();
                        return response()->json(['success' => false, 'message' => "Désactivation impossible. Le syndic doit envoyer des rappels pour que l'utilisateur règle les montants en attente."], 400);
                    }

                    // ⚠️ Réinitialiser les soldes à 0
                    DB::table('soldes')
                        ->where('user_id', $request->owner_id)
                        ->where('propriete_id', $request->propriete_id)
                        ->update(['montant' => 0]);
                }

                // Update ou Insert l'association Lot <-> Propriétaire
                DB::table('user_owner_unit')->updateOrInsert(
                    ['user_id' => $request->owner_id, 'unit_id' => $lot->id],
                    ['status' => $status, 'updated_at' => now()]
                );
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Lot modifié avec succès.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // 4. Supprimer un lot
    public function supprimer(Request $request)
    {
        $request->validate(['propriete_id' => 'required', 'lot_id' => 'required']);

        DB::beginTransaction();
        try {
            // 🟢 SUPPRESSION EN CASCADE (Delete Cascade Manuel)
            
            // A. Supprimer l'association Copropriétaire <-> Lot
            DB::table('user_owner_unit')->where('unit_id', $request->lot_id)->delete();
            
            // B. Supprimer l'association Lot <-> Clés de répartition
            DB::table('unit_to_key')->where('unit_id', $request->lot_id)->delete();

            // C. Supprimer le Lot
            Lot::where('id', $request->lot_id)->where('propriete_id', $request->propriete_id)->delete();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Lot supprimé avec succès.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Erreur lors de la suppression: ' . $e->getMessage()], 500);
        }
    }
}
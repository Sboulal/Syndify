<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lot; 
use App\Models\UserOwnerUnit;
use App\Models\UnitToKey;
use App\Models\UserAsOwner;
use Illuminate\Support\Facades\DB;

class LotController extends Controller
{
    // 1. Liste des Lots
    public function liste(Request $request)
    {
        $request->validate(['propriete_id' => 'required']);
        
        $lots = Lot::where('propriete_id', $request->propriete_id)
            ->with(['owners' => function($query) {
                $query->where('user_owner_unit.status', 1);
            }])
            ->get();

        return response()->json(['success' => true, 'data' => $lots]);
    }

    // 2. Détails du Lot
    public function details(Request $request)
    {
        $request->validate([
            'propriete_id' => 'required',
            'lot_id' => 'required'
        ]);

        $lot = Lot::where('id', $request->lot_id)
                  ->where('propriete_id', $request->propriete_id)
                  ->firstOrFail();

        $tantiemes = UnitToKey::where('unit_id', $lot->id)->with('cleRepartition')->get();
        
        $proprietaires = UserOwnerUnit::where('unit_id', $lot->id)
            ->join('users', 'user_owner_unit.user_id', '=', 'users.id')
            ->select('users.*', 'user_owner_unit.status')
            ->get();

        return response()->json([
            'success' => true, 
            'tantiemes' => $tantiemes,
            'proprietaires' => $proprietaires
        ]);
    }

    // 3. Ajouter un lot
    public function ajouter(Request $request)
    {
        $request->validate([
            'propriete_id' => 'required',
            'type' => 'required',
            'numero_porte' => 'required' // Bâtiment et Etage sont optionnels dans le front
        ]);

        DB::beginTransaction();
        try {
            $lot = Lot::create([
                'propriete_id' => $request->propriete_id,
                'type' => $request->type,
                'batiment' => $request->batiment,
                'etage' => $request->etage,
                'numero_porte' => $request->numero_porte
            ]);

            // Lier aux clés de répartition existantes avec tantième 0
            $cles = DB::table('cle_repartitions')->where('propriete_id', $request->propriete_id)->get();
            foreach ($cles as $cle) {
                UnitToKey::create([
                    'unit_id' => $lot->id,
                    'key_id' => $cle->id,
                    'tantieme' => 0
                ]);
            }

            DB::commit();
            // 🛑 FIX: حيدت تحميل الملاك (proprietairesActifs) حيت مكاتحتاجيهش فالمودال ديالك
            return response()->json([
                'success' => true, 
                'lot' => $lot,
                'message' => 'Lot ajouté avec succès.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // 4. Modifier un Lot
    public function modifier(Request $request)
    {
        $request->validate([
            'propriete_id' => 'required',
            'lot_id' => 'required'
        ]);

        DB::beginTransaction();
        try {
            $lot = Lot::where('id', $request->lot_id)
                      ->where('propriete_id', $request->propriete_id)
                      ->firstOrFail();

            // 🛑 FIX: اللوجيك تنقى مزيان. غادي نديرو Update غي للمعلومات الأساسية لي جاية من المودال
            $lot->update($request->only(['type', 'batiment', 'etage', 'numero_porte']));

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Lot modifié avec succès.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // 5. Supprimer un lot
    public function supprimer(Request $request)
    {
        $request->validate(['propriete_id' => 'required', 'lot_id' => 'required']);

        Lot::where('id', $request->lot_id)
           ->where('propriete_id', $request->propriete_id)
           ->delete();

        return response()->json(['success' => true, 'message' => 'Lot supprimé avec succès.']);
    }
}
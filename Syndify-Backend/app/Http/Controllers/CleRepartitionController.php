<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CleRepartition;
use App\Models\Lot;
use App\Models\UnitToKey;
use Illuminate\Support\Facades\DB;

class CleRepartitionController extends Controller
{
    // 1. Chargement des Clés de Répartition
    public function liste(Request $request)
    {
        $request->validate(['propriete_id' => 'required']);

      
        $cles = CleRepartition::where('propriete_id', $request->propriete_id)
            ->with(['lots' => function ($query) {
               
                $query->select('units.*', 'unit_to_key.tantieme as tantieme_applied')
                      ->with(['owners' => function ($q) {
                          $q->where('user_owner_unit.status', 1); // Propriétaire Actif فقط
                      }]);
            }])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $cles
        ]);
    }

    // 2. Ajouter une nouvelle clé de répartition
    public function ajouter(Request $request)
    {
        $request->validate([
            'propriete_id' => 'required',
            'nom_cle' => 'required|string',
            'tantiemes_total' => 'required|numeric',
            'notes' => 'nullable|string',
            'unites' => 'required|array',
            'unites.*.unit_id' => 'required|exists:units,id',
            'unites.*.tantieme_applied' => 'required|numeric|min:0'
        ]);

   
        $existe = CleRepartition::where('propriete_id', $request->propriete_id)
                                ->where('nom', $request->nom_cle)
                                ->exists();
        if ($existe) {
            return response()->json(['success' => false, 'message' => 'Le nom de cette clé existe déjà.'], 400);
        }

        
        $sommeTantiemes = collect($request->unites)->sum('tantieme_applied');
        if ($sommeTantiemes != $request->tantiemes_total) {
            return response()->json([
                'success' => false, 
                'message' => "La somme des tantièmes ($sommeTantiemes) ne correspond pas au total spécifié ({$request->tantiemes_total})."
            ], 400);
        }

        DB::beginTransaction();
        try {
           
            $cle = CleRepartition::create([
                'propriete_id' => $request->propriete_id,
                'nom' => $request->nom_cle,
                'tantiemes_total' => $request->tantiemes_total,
                'notes' => $request->notes
            ]);

           
            foreach ($request->unites as $unite) {
                UnitToKey::create([
                    'unit_id' => $unite['unit_id'],
                    'key_id' => $cle->id,
                    'tantieme' => $unite['tantieme_applied']
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Clé de répartition ajoutée avec succès.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // 3. Modifier une clé de répartition
    public function modifier(Request $request)
    {
        $request->validate([
            'propriete_id' => 'required',
            'scr_identifier' => 'required', // l'ID dyal l'clé
            'nom_cle' => 'required|string',
            'tantiemes_total' => 'required|numeric',
            'unites' => 'required|array'
        ]);

        $cle = CleRepartition::where('id', $request->scr_identifier)
                             ->where('propriete_id', $request->propriete_id)
                             ->firstOrFail();

        // Vérification 1: واش السمية تعاودات لشي Clé أخرى؟
        $existe = CleRepartition::where('propriete_id', $request->propriete_id)
                                ->where('nom', $request->nom_cle)
                                ->where('id', '!=', $cle->id)
                                ->exists();
        if ($existe) {
            return response()->json(['success' => false, 'message' => 'Le nom de cette clé existe déjà.'], 400);
        }

       
        $sommeTantiemes = collect($request->unites)->sum('tantieme_applique'); // f l'cahier des charges semitiha applique f modifier
        if ($sommeTantiemes != $request->tantiemes_total) {
            return response()->json(['success' => false, 'message' => "La somme des tantièmes est incorrecte."], 400);
        }

        DB::beginTransaction();
        try {
           
            $cle->update([
                'nom' => $request->nom_cle,
                'tantiemes_total' => $request->tantiemes_total,
                'notes' => $request->notes ?? $cle->notes
            ]);

            // Sync les tantièmes (kanms7o l9dam w nzido jdad)
            UnitToKey::where('key_id', $cle->id)->delete();
            foreach ($request->unites as $unite) {
                UnitToKey::create([
                    'unit_id' => $unite['id_unite'],
                    'key_id' => $cle->id,
                    'tantieme' => $unite['tantieme_applique']
                ]);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Clé de répartition modifiée avec succès.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CleRepartition;
use App\Models\Lot;
use Illuminate\Support\Facades\DB;

class CleRepartitionController extends Controller
{
    // 1. Chargement des Clés de Répartition
    public function liste(Request $request)
    {
        $request->validate(['propriete_id' => 'required']);

        $cles = CleRepartition::where('propriete_id', $request->propriete_id)
            ->with(['lots' => function ($query) {
                // 🟢 Zidna 'unit_to_key.key_id' bach Laravel y3ref ydir la liaison s7i7a f l'array
               $query->select('units.*', 'unit_to_key.tantieme as tantieme_applied', 'unit_to_key.cle_repartition_id') 
                      ->with(['owners' => function ($q) {
                          $q->where('user_owner_unit.status', 1); // Propriétaire Actif uniquement
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
            'unites.*.id_unite' => 'required|exists:units,id',
            'unites.*.tantieme_applique' => 'required|numeric|min:0'
        ]);

        $existe = CleRepartition::where('propriete_id', $request->propriete_id)
                                ->where('nom', $request->nom_cle)
                                ->exists();
        if ($existe) {
            return response()->json(['success' => false, 'message' => 'Le nom de cette clé existe déjà.'], 400);
        }

        // L'arrondissement des doubles bach ntfadaw les bugs dyal les virgules
        $sommeTantiemes = round(collect($request->unites)->sum('tantieme_applique'), 4);
        $totalAttendu = round($request->tantiemes_total, 4);

        if ($sommeTantiemes != $totalAttendu) {
            return response()->json([
                'success' => false, 
                'message' => "La somme des tantièmes ($sommeTantiemes) ne correspond pas au total spécifié ($totalAttendu)."
            ], 400);
        }

        DB::beginTransaction();
        try {
           
            $cle = CleRepartition::create([
                'propriete_id' => $request->propriete_id,
                'nom' => $request->nom_cle,
                'tantiemes_total' => $totalAttendu,
                'notes' => $request->notes
            ]);

            $pivotData = [];
            foreach ($request->unites as $unite) {
                // 🟢 Hna kanwjdo data l'table 'unit_to_key' (katmchi b id_unite li hwa Integer)
                $pivotData[$unite['id_unite']] = ['tantieme' => $unite['tantieme_applique']];
            }
            
            // 🟢 L'attach() kay-inserer f pivot table b l'ID dyal l'clé w l'ID dyal lot automatiqement
            $cle->lots()->attach($pivotData);

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
            'scr_identifier' => 'required', // 🟢 Hada ghaywssel l'ID Integer (Ex: 1, 2, 3) 
            'nom_cle' => 'required|string',
            'tantiemes_total' => 'required|numeric',
            'unites' => 'required|array',
            'unites.*.id_unite' => 'required|exists:units,id',
            'unites.*.tantieme_applique' => 'required|numeric|min:0'
        ]);

        $cle = CleRepartition::where('id', $request->scr_identifier)
                             ->where('propriete_id', $request->propriete_id)
                             ->firstOrFail();

        $existe = CleRepartition::where('propriete_id', $request->propriete_id)
                                ->where('nom', $request->nom_cle)
                                ->where('id', '!=', $cle->id)
                                ->exists();
        if ($existe) {
            return response()->json(['success' => false, 'message' => 'Le nom de cette clé existe déjà.'], 400);
        }

        $sommeTantiemes = round(collect($request->unites)->sum('tantieme_applique'), 4);
        $totalAttendu = round($request->tantiemes_total, 4);

        if ($sommeTantiemes != $totalAttendu) {
            return response()->json(['success' => false, 'message' => "La somme des tantièmes est incorrecte."], 400);
        }

        DB::beginTransaction();
        try {
           
            $cle->update([
                'nom' => $request->nom_cle,
                'tantiemes_total' => $totalAttendu,
                'notes' => $request->notes ?? $cle->notes
            ]);

            $pivotData = [];
            foreach ($request->unites as $unite) {
                $pivotData[$unite['id_unite']] = ['tantieme' => $unite['tantieme_applique']];
            }

            // 🟢 L'sync() kaymss7 l9dam w kay7et jdad aw kaydir Update automatiqement.
            $cle->lots()->sync($pivotData);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Clé de répartition modifiée avec succès.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // 4. Supprimer une clé de répartition
    public function supprimer(Request $request)
    {
        $request->validate([
            'propriete_id' => 'required',
            'cle_id' => 'required' // 🟢 Integer
        ]);

        $cle = CleRepartition::where('id', $request->cle_id)
                             ->where('propriete_id', $request->propriete_id)
                             ->first();

        if (!$cle) {
            return response()->json(['success' => false, 'message' => 'Clé introuvable.'], 404);
        }

        DB::beginTransaction();
        try {
            // 🟢 wakha 3ndna Cascade Delete f BDD, ndiro detach() bash nkonou n9iyin f code
            $cle->lots()->detach(); 
            $cle->delete();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Clé de répartition supprimée avec succès.']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Erreur lors de la suppression: ' . $e->getMessage()], 500);
        }
    }
}
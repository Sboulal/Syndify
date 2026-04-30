<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CleRepartition;
use App\Models\Lot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleRepartitionController extends Controller
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
// 1. Chargement des Clés de Répartition
   // 1. Chargement des Clés de Répartition
    public function liste(Request $request)
    {
        $propriete_id = $this->getProprieteId($request);
        if (!$propriete_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        $propIdCol_propriete = Schema::hasColumn('proprietes', 'sp_identifier') ? 'sp_identifier' : 'id';

        // 🟢 1. Jbed l-m3loumat dyal l-Résidence nichan mn l-Backend
        $residence = DB::table('proprietes')
            ->where($propIdCol_propriete, $propriete_id)
            ->first();

        // 🟢 2. Jbed les Clés m3a les Lots w les Owners
        $cles = CleRepartition::where('propriete_id', $propriete_id)
            ->with(['lots' => function ($query) {
                $query->select('units.*', 'unit_to_key.tantieme as tantieme_applied', 'unit_to_key.cle_repartition_id') 
                      ->with(['owners' => function ($q) {
                          $q->where('user_owner_unit.status', 1); 
                      }]);
            }])
            ->get();

        // 🟢 3. Rjja3 kolchi m-jmo3
        return response()->json([
            'success' => true,
            'residence' => [
                'nom' => $residence->nom ?? 'Résidence',
                'adresse' => $residence->address ?? 'Adresse non définie'
            ],
            'data' => $cles
        ]);
    }
   // 2. Ajouter une nouvelle clé de répartition
    public function ajouter(Request $request)
    {
        $request->validate([
            'nom_cle' => 'required|string',
            'tantiemes_total' => 'required|numeric',
            'notes' => 'nullable|string',
            'unites' => 'required|array',
            'unites.*.id_unite' => 'required|exists:units,id',
            'unites.*.tantieme_applique' => 'required|numeric|min:0'
        ]);

        $propriete_id = $this->getProprieteId($request);
        if (!$propriete_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        // 🟢 Fix hna
        $existe = CleRepartition::where('propriete_id', $propriete_id)
                                ->where('nom', $request->nom_cle)
                                ->exists();
        if ($existe) {
            return response()->json(['success' => false, 'message' => 'Le nom de cette clé existe déjà.'], 400);
        }

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
                'propriete_id' => $propriete_id, // 🟢 Fix hna (kant propriete_id)
                'nom' => $request->nom_cle,
                'tantiemes_total' => $totalAttendu,
                'notes' => $request->notes
            ]);

            $pivotData = [];
            foreach ($request->unites as $unite) {
                $pivotData[$unite['id_unite']] = ['tantieme' => $unite['tantieme_applique']];
            }
            
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
            'scr_identifier' => 'required', 
            'nom_cle' => 'required|string',
            'tantiemes_total' => 'required|numeric',
            'unites' => 'required|array',
            'unites.*.id_unite' => 'required|exists:units,id',
            'unites.*.tantieme_applique' => 'required|numeric|min:0'
        ]);

        $propriete_id = $this->getProprieteId($request);
        if (!$propriete_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        // 🟢 Fix hna
        $cle = CleRepartition::where('id', $request->scr_identifier)
                             ->where('propriete_id', $propriete_id)
                             ->firstOrFail();

        $existe = CleRepartition::where('propriete_id', $propriete_id)
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
            'cle_id' => 'required' 
        ]);

        $propriete_id = $this->getProprieteId($request);
        if (!$propriete_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        // 🟢 Fix hna
        $cle = CleRepartition::where('id', $request->cle_id)
                             ->where('propriete_id', $propriete_id)
                             ->first();

        if (!$cle) {
            return response()->json(['success' => false, 'message' => 'Clé introuvable.'], 404);
        }

        DB::beginTransaction();
        try {
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
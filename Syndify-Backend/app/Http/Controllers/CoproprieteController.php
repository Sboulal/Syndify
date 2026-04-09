<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Copropriete;
use Illuminate\Support\Facades\DB;

class CoproprieteController extends Controller
{
    public function ajouter(Request $request)
    {
        // 1. L-Vérification dyal l'm3loumat
        $request->validate([
            'name' => 'required|string|max:255',
            'city' => 'required|string',
            'country' => 'required|string',
            'siret' => 'nullable|string',
            'address' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            // 2. N-wldou l'Identifiant personnalisé (B7al SP-17120593)
            $identifier = 'SP-' . time();

            // 3. N-saybou l'Copropriété f BDD
            $propriete = Copropriete::create([
                'identifier' => $identifier,
                'name' => $request->name,
                'city' => $request->city,
                'country' => $request->country,
                'siret' => $request->siret,
                'address' => $request->address
            ]);

          

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Copropriété créée avec succès',
                'data' => $propriete
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
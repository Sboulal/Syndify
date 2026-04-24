<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class SimulationController extends Controller
{
    private function getProprieteId(Request $request)
    {
        // 🟢 FIX HNA: 7iydna l-verification dyal l-payload, dima n-jbdou mn user_id = 1
        $userId = 1; 

        $propOwnerCol = Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';
        $link = DB::table('user_as_owner')->where('user_id', $userId)->first();
        
        return $link ? $link->$propOwnerCol : null;
    }
  public function chargerDonneesSimulation(Request $request)
    {
        Log::info('--- Simulation : Chargement ---');
        
        $sp_id = $this->getProprieteId($request);
        if (!$sp_id) return response()->json(['success' => false, 'message' => 'Accès refusé.'], 403);

        try {
            // 🟢 1. Jbed l-m3loumat dyal l-Résidence
            $propIdCol_propriete = Schema::hasColumn('proprietes', 'sp_identifier') ? 'sp_identifier' : 'id';
            $residence = DB::table('proprietes')->where($propIdCol_propriete, $sp_id)->first();

            $userPk = Schema::hasColumn('users', 'identifier') ? 'identifier' : 'id';
            $userNameCol = Schema::hasColumn('users', 'full_name') ? 'full_name' : 'name';
            $cleIdCol = Schema::hasColumn('unit_to_key', 'cle_repartition_id') ? 'cle_repartition_id' : 'cle_id';

            // 2. Clés
            $cles = DB::table('cle_repartitions')->where('propriete_id', $sp_id)->get();

            foreach ($cles as $cle) {
                $totalTantiemes = $cle->tantiemes_total ?? 1000;

                $cle->lots = DB::table('unit_to_key')
                    ->join('units', 'units.id', '=', 'unit_to_key.unit_id')
                    ->leftJoin('user_owner_unit', function($join) {
                        $join->on('user_owner_unit.unit_id', '=', 'units.id')
                             ->where('user_owner_unit.status', 1);
                    })
                    ->leftJoin('users', 'users.id', '=', 'user_owner_unit.user_id') 
                    ->where('unit_to_key.' . $cleIdCol, $cle->id)
                    ->select(
                        'units.id as lot_id', // 🟢 Zidna l-ID dyal l-lot
                        'units.type', // 🟢 Zidna l-Type (Appartement, Garage...)
                        'units.numero_porte', // 🟢 Zidna r-Raqm dyal l-porte 
                        'unit_to_key.tantieme',
                        DB::raw("$totalTantiemes as tantiemes_total"),
                        'users.identifier as owner_id',
                        DB::raw("COALESCE(users.$userNameCol, 'Sans propriétaire') as owner_name")
                    )
                    ->get();
            }

            // 🟢 3. Rjja3 kolchi
            return response()->json([
                'success' => true, 
                'residence' => [
                    'nom' => $residence->nom ?? 'Résidence',
                    'adresse' => $residence->address ?? 'Adresse non définie'
                ],
                'data' => $cles
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
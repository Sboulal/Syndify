<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class SimulationController extends Controller
{
    // ==========================================
    // RÉCUPÉRER LES DONNÉES DE SIMULATION
    // ==========================================
    public function chargerDonneesSimulation(Request $request)
    {
        Log::info('--- DÉBUT : Chargement des données pour Simulation ---');
        
        $request->validate([
            'sp_identifier' => 'required|string'
        ]);

        $sp_id = $request->sp_identifier;

        try {
            // Check des noms de colonnes dynamiques
            $clesCol = Schema::hasColumn('cle_repartitions', 'propriete_id') ? 'propriete_id' : 'sp_identifier';
            $userPk = Schema::hasColumn('users', 'identifier') ? 'identifier' : (Schema::hasColumn('users', 'user_id') ? 'user_id' : 'id');
            $userNameCol = Schema::hasColumn('users', 'full_name') ? 'full_name' : 'name';

            // 🟢 L-FIX HWA HADA: N-9elbou 3la s-smiya s7i7a dyal l-clé f table unit_to_key
            $cleIdCol = Schema::hasColumn('unit_to_key', 'cle_repartition_id') ? 'cle_repartition_id' : (Schema::hasColumn('unit_to_key', 'cle_id') ? 'cle_id' : 'key_id');

            // 1. Récupération des Clés de Répartition
            $cles = DB::table('cle_repartitions')
                ->where($clesCol, $sp_id)
                ->get();

            // 2. Pour chaque clé
            foreach ($cles as $cle) {
                
                $totalTantiemes = $cle->tantiemes_total ?? 1000;

                $cle->lots = DB::table('unit_to_key')
                    ->join('units', 'units.id', '=', 'unit_to_key.unit_id')
                    ->leftJoin('user_owner_unit', function($join) {
                        $join->on('user_owner_unit.unit_id', '=', 'units.id')
                             ->where('user_owner_unit.status', 1);
                    })
                    ->leftJoin('users', 'users.' . $userPk, '=', 'user_owner_unit.user_id')
                    
                    // 🟢 N-sta3mlou l-colonne s-s7i7a lli l9inah l-fou9 (ghaleban cle_repartition_id)
                    ->where('unit_to_key.' . $cleIdCol, $cle->id)
                    
                    ->select(
                        'units.id as lot_id',
                        'units.numero_porte as lot_identifiant',
                        'unit_to_key.tantieme',
                        DB::raw("$totalTantiemes as tantiemes_total"),
                        'users.' . $userPk . ' as owner_id',
                        DB::raw("COALESCE(users.$userNameCol, 'Sans propriétaire') as owner_name")
                    )
                    ->get();
            }

            Log::info('✅ Données de simulation chargées avec succès.');

            return response()->json([
                'success' => true,
                'data' => $cles
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur chargement simulation : ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
}
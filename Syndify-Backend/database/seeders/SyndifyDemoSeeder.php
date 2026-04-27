<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SyndifyDemoSeeder extends Seeder
{
    public function run()
    {
        // 0. N-ms7ou d-data l-9dima
        DB::statement('SET session_replication_role = replica;'); 
        DB::table('proprietes')->truncate();
        DB::table('users')->truncate();
        DB::table('user_as_owner')->truncate();
        DB::table('units')->truncate();
        DB::table('user_owner_unit')->truncate();
        DB::table('cle_repartitions')->truncate();
        DB::table('unit_to_key')->truncate();
        DB::table('exercices')->truncate();
        DB::table('charges_previsionnelles')->truncate();
        DB::table('depenses')->truncate();
        DB::table('depense_for_owner')->truncate(); 
        DB::table('encaissements')->truncate();
        DB::table('appels_fonds')->truncate();   
        DB::table('appf_to_owner')->truncate();  
        DB::statement('SET session_replication_role = origin;');

        $now = Carbon::now();
        $sp_id = 'SP-87248712';

        // ==========================================
        // 1. RÉSIDENCE
        // ==========================================
        DB::table('proprietes')->insert([
            'id' => $sp_id, 
            'nom' => 'Résidence Les Jardins',
            'address' => 'Boulevard Anfa, Casablanca',
            'city' => 'Casablanca',
            'created_at' => $now,
            'updated_at' => $now
        ]);

        // ==========================================
        // 2. UTILISATEURS (Syndic + Copropriétaires)
        // ==========================================
        DB::table('users')->insert([
            ['id' => 1, 'full_name' => 'Syndic Admin', 'email' => 'admin@syndify.ma', 'tel' => '0600000000', 'identifier' => 'SU-0001'],
            ['id' => 2, 'full_name' => 'Ahmed Alami', 'email' => 'ahmed@gmail.com', 'tel' => '0661234567', 'identifier' => 'SU-0002'],
            ['id' => 3, 'full_name' => 'Sara Bennani', 'email' => 'sara@yahoo.fr', 'tel' => '0669876543', 'identifier' => 'SU-0003'],
            ['id' => 4, 'full_name' => 'Youssef Tazi', 'email' => 'youssef@hotmail.com', 'tel' => '0661112233', 'identifier' => 'SU-0004'],
        ]);

        // 🟢 Ga3 n-nass kay-bdaw b-solde 0 (App Vierge)
        DB::table('user_as_owner')->insert([
            ['user_id' => 1, 'propriete_id' => $sp_id, 'status' => 1, 'balance_prev' => 0],
            ['user_id' => 2, 'propriete_id' => $sp_id, 'status' => 1, 'balance_prev' => 0], 
            ['user_id' => 3, 'propriete_id' => $sp_id, 'status' => 1, 'balance_prev' => 0],        
            ['user_id' => 4, 'propriete_id' => $sp_id, 'status' => 1, 'balance_prev' => 0],  
        ]);

        // ==========================================
        // 3. LOTS (Appartements & Garages)
        // ==========================================
        DB::table('units')->insert([
            ['id' => 1, 'propriete_id' => $sp_id, 'type' => 'Appartement', 'numero_porte' => 'A1', 'etage' => '1', 'batiment' => 'A'],
            ['id' => 2, 'propriete_id' => $sp_id, 'type' => 'Garage', 'numero_porte' => 'G1', 'etage' => 'SS', 'batiment' => 'A'],
            ['id' => 3, 'propriete_id' => $sp_id, 'type' => 'Appartement', 'numero_porte' => 'A2', 'etage' => '2', 'batiment' => 'A'],
            ['id' => 4, 'propriete_id' => $sp_id, 'type' => 'Appartement', 'numero_porte' => 'B1', 'etage' => '1', 'batiment' => 'B'],
        ]);

        DB::table('user_owner_unit')->insert([
            ['user_id' => 2, 'unit_id' => 1, 'status' => 1],
            ['user_id' => 2, 'unit_id' => 2, 'status' => 1],
            ['user_id' => 3, 'unit_id' => 3, 'status' => 1],
            ['user_id' => 4, 'unit_id' => 4, 'status' => 1],
        ]);

        // ==========================================
        // 4. CLÉS DE RÉPARTITION
        // ==========================================
        $cle_generale = DB::table('cle_repartitions')->insertGetId([
            'propriete_id' => $sp_id, 'nom' => 'Charges Générales', 'tantiemes_total' => 10000, 'notes' => 'Répartition globale'
        ]);
        $cle_ascenseur = DB::table('cle_repartitions')->insertGetId([
            'propriete_id' => $sp_id, 'nom' => 'Frais Ascenseur', 'tantiemes_total' => 1000, 'notes' => 'Sauf RDC/Garages'
        ]);

        DB::table('unit_to_key')->insert([
            ['unit_id' => 1, 'cle_repartition_id' => $cle_generale, 'tantieme' => 3500],
            ['unit_id' => 2, 'cle_repartition_id' => $cle_generale, 'tantieme' => 1000],
            ['unit_id' => 3, 'cle_repartition_id' => $cle_generale, 'tantieme' => 3500],
            ['unit_id' => 4, 'cle_repartition_id' => $cle_generale, 'tantieme' => 2000],
            
            ['unit_id' => 1, 'cle_repartition_id' => $cle_ascenseur, 'tantieme' => 400],
            ['unit_id' => 2, 'cle_repartition_id' => $cle_ascenseur, 'tantieme' => 0],
            ['unit_id' => 3, 'cle_repartition_id' => $cle_ascenseur, 'tantieme' => 400],
            ['unit_id' => 4, 'cle_repartition_id' => $cle_ascenseur, 'tantieme' => 200],
        ]);

        $this->command->info('✅ Application initialisée avec la structure de base (App Vierge).');
    }
}
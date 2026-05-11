<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // 1. Création dyal l-Compte Admin
        $userId = DB::table('users')->insertGetId([
            'identifier' => 'SU-100000',
            'full_name' => 'Salma Boulal',
            'email' => 'salma@exemple.com',
            'tel' => '0612345678',
            'activation_code' => null, // Compte déjà actif
            'status' => 'Actif',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Création dyal l-Résidence
        $propIdCol = \Illuminate\Support\Facades\Schema::hasColumn('proprietes', 'sp_identifier') ? 'sp_identifier' : 'id';
        $propId = 'SP-' . rand(10000000, 99999999);
        
        DB::table('proprietes')->insert([
            $propIdCol => $propId,
            'nom' => 'Résidence Les Palmiers',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. R-rabt bin l-Admin w l-Résidence f (user_as_owner)
        $propOwnerCol = \Illuminate\Support\Facades\Schema::hasColumn('user_as_owner', 'propriete_id') ? 'propriete_id' : 'sp_identifier';
        
        DB::table('user_as_owner')->insert([
            'user_id' => $userId, // 🟢 Hna kan-rbtohom
            $propOwnerCol => $propId,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->command->info('✅ Base de données initialisée avec succès ! (Admin + Résidence liées)');
    }
}
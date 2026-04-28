<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SyndifyTestDataSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('fr_MA');

        // ✅ Vider les tables
        DB::statement('SET session_replication_role = replica;');
        DB::statement('TRUNCATE TABLE proprietes, users, user_as_owner, units, user_owner_unit, unit_to_key, exercices, cle_repartitions, encaissements, depenses, depense_for_owner, appels_fonds, appf_to_owner CASCADE;');
        DB::statement('SET session_replication_role = origin;');

        // ==========================================
        // 1. CRÉER UNE PROPRIÉTÉ
        // ==========================================
        $sp_id = 'SP-87248712'; 
        
        $propData = [
            'nom' => 'Résidence Les Jardins de l\'Océan',
            'address' => $faker->address,
            'created_at' => now(), 'updated_at' => now(),
        ];

        if (\Illuminate\Support\Facades\Schema::hasColumn('proprietes', 'sp_identifier')) {
            $propData['sp_identifier'] = $sp_id;
        } else {
            $propData['id'] = $sp_id; 
        }
        $proprieteId = DB::table('proprietes')->insertGetId($propData);

        // ==========================================
        // 2. CRÉER LES CLÉS DE RÉPARTITION 
        // ==========================================
        $cleId = DB::table('cle_repartitions')->insertGetId([
            'propriete_id' => $sp_id, 'nom' => 'Charges Communes Générales',
            'tantiemes_total' => 10000, 'created_at' => now(), 'updated_at' => now()
        ]);

        // ==========================================
        // 3. CRÉER LES COPROPRIÉTAIRES (50 Users)
        // ==========================================
        $usersIds = [];
        for ($i = 0; $i < 50; $i++) {
            $userData = [
                'identifier' => 'COP-00' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'full_name' => $faker->firstName . ' ' . $faker->lastName,
                'email' => $faker->unique()->safeEmail,
                'tel' => $faker->regexify('06[0-9]{8}'),
                'created_at' => now(), 'updated_at' => now(),
            ];

            if (\Illuminate\Support\Facades\Schema::hasColumn('users', 'password')) {
                $userData['password'] = Hash::make('password123');
            }

            $userId = DB::table('users')->insertGetId($userData);
            $usersIds[] = $userId;

            DB::table('user_as_owner')->insert([
                'user_id' => $userId, 'propriete_id' => $sp_id, 'status' => 1,
                'balance_prev' => 0, // 🟢 L-Balance ghat-t7seb b-l-mantiq mn b3d!
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // ==========================================
        // 4. CRÉER LES LOTS ET RÉPARTIR LES TANTIÈMES LOGIQUEMENT
        // ==========================================
        $typesBiens = ['Appartement', 'Garage', 'Magasin'];
        $unitToKeyData = [];
        $totalTantiemesRepartis = 0;
        
        // 🟢 N-khzno l-Tantièmes dyal kol wa7ed bash n-7esbou bihom l-Appels de Fonds
        $userTantiemes = []; 
        foreach ($usersIds as $uid) { $userTantiemes[$uid] = 0; }

        for ($i = 1; $i <= 120; $i++) {
            $type = $faker->randomElement($typesBiens);
            $unitId = DB::table('units')->insertGetId([
                'propriete_id' => $proprieteId, 'type' => $type,
                'numero_porte' => ($type === 'Appartement' ? 'A' : ($type === 'Garage' ? 'G' : 'M')) . str_pad($i, 3, '0', STR_PAD_LEFT),
                'etage' => $type === 'Appartement' ? $faker->numberBetween(1, 10) : 0,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            // 🟢 L-Mantiq: Kol Copropriétaire y-akhoud 2 awla 3 dyal l-Lots b-t-tartib
            $ownerId = $usersIds[$i % 50];

            DB::table('user_owner_unit')->insert([
                'user_id' => $ownerId, 'unit_id' => $unitId, 'status' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            $tantiemeLot = ($i === 120) ? (10000 - $totalTantiemesRepartis) : round(10000 / 120, 4); 
            $totalTantiemesRepartis += $tantiemeLot;
            
            // 🟢 N-zidou l-Tantième f l-Rassid dyal l-Copropriétaire
            $userTantiemes[$ownerId] += $tantiemeLot; 

            $unitToKeyData[] = [
                'unit_id' => $unitId, 'cle_repartition_id' => $cleId, 'tantieme' => $tantiemeLot
            ];
        }

        foreach (array_chunk($unitToKeyData, 50) as $chunk) {
            DB::table('unit_to_key')->insert($chunk);
        }

        // ==========================================
        // 5. CRÉER L'EXERCICE COMPTABLE (2025)
        // ==========================================
        $se_id = 'EX-2025-' . Str::random(5);
        DB::table('exercices')->insert([
            'se_identifier' => $se_id, 'propriete_id' => $sp_id, 'start_date' => '2025-01-01', 'end_date' => '2025-12-31',
            'status' => 'en cours', 'period' => 'trimestre', 'created_at' => now(), 'updated_at' => now(),
        ]);
        
        DB::table('charges_previsionnelles')->insert([
            'scp_identifier' => 'SCP-2025-'.Str::random(3), 'se_identifier' => $se_id, 'budget' => 200000,
            'total_encaissements' => 0, 'total_depenses' => 0
        ]);

        // ==========================================
        // 6. APPELS DE FONDS (Calculés selon les Tantièmes exacts !)
        // ==========================================
        $appfToOwner = [];

        for ($trimestre = 1; $trimestre <= 3; $trimestre++) {
            $af_id = 'AF-T' . $trimestre . '-' . Str::random(4);
            $montantAppel = 40000; // 40,000 DH l-koll trimestre

            DB::table('appels_fonds')->insert([
                'af_identifier' => $af_id, 'se_identifier' => $se_id, 'type_charge' => 'previsionnel', 'sub_type' => 'planifie',
                'title' => "Appel de fonds Trimestre $trimestre - 2025", 'amount' => $montantAppel,
                'due_date' => Carbon::createFromDate(2025, ($trimestre - 1) * 3 + 1, 1)->format('Y-m-d'),
                'is_generated' => true, 'is_sent' => true, 'number_generated' => count($usersIds), 'number_sent' => count($usersIds),
                'created_at' => now(), 'updated_at' => now()
            ]);

            // 🟢 L-Mantiq: L-khalass m-bni 3la Tantièmes (Mashi m9ssoum 3la 50)
            foreach ($usersIds as $uid) {
                $montantDu = $montantAppel * ($userTantiemes[$uid] / 10000); // 7ssab s7i7!
                
                $appfToOwner[] = [
                    'af_identifier' => $af_id, 'user_id' => $uid, 'montant_du' => $montantDu, 'created_at' => now()
                ];
                
                // N-n9ssou l-Montant mn l-Balance (Dette)
                DB::table('user_as_owner')->where('user_id', $uid)->decrement('balance_prev', $montantDu);
            }
        }
        foreach (array_chunk($appfToOwner, 50) as $chunk) {
            DB::table('appf_to_owner')->insert($chunk);
        }

   // ==========================================
        // 7. ENCAISSEMENTS (Paiements logiques)
        // ==========================================
        $encaissements = [];
        $indexEnc = 1; // 🟢 Index bash n-dmanou l-unicité
        foreach (array_slice($usersIds, 0, 20) as $uid) {
            $montantPaye = 5000; 
            $encaissements[] = [
                // 🟢 Zdna $indexEnc bash l-ID ykoune unique 100%
                'sen_identifier' => 'ENC-' . time() . '-' . ($indexEnc++) . '-' . rand(1000, 9999), 
                'se_identifier' => $se_id,
                'owner_id' => $uid, 
                'title' => 'Paiement Cotisation', 
                'amount' => $montantPaye,
                'date' => $faker->dateTimeBetween('2025-01-01', 'now')->format('Y-m-d'),
                'type_charges' => 'previsionnel', 
                'sub_type_charges' => 'planifié',
                'created_at' => now(), 
                'updated_at' => now()
            ];
            // N-zidou l-Khlass f l-Balance
            DB::table('user_as_owner')->where('user_id', $uid)->increment('balance_prev', $montantPaye);
        }
        DB::table('encaissements')->insert($encaissements);

        // ==========================================
        // 8. DÉPENSES
        // ==========================================
        $depenseId = DB::table('depenses')->insertGetId([
            'sdep_identifier' => 'DEP-' . time() . '-1', 'se_identifier' => $se_id, 'cle_repartition_id' => $cleId,
            'title' => 'Facture Lydec + Nettoyage', 'amount' => 12500, 'date' => '2025-02-15',
            'type_charges' => 'previsionnel', 'sub_type_charges' => 'Courante',
            'created_at' => now(), 'updated_at' => now()
        ]);

        $depenseForOwnerData = [];
        foreach ($usersIds as $uid) {
            $partDepense = 12500 * ($userTantiemes[$uid] / 10000);
            $depenseForOwnerData[] = [
                'depense_id' => $depenseId, 'user_id' => $uid, 'amount_due' => $partDepense, 'created_at' => now(), 'updated_at' => now()
            ];
        }
        DB::table('depense_for_owner')->insert($depenseForOwnerData);

        $this->command->info('🚀 BOOM! Données 100% liées et logiques!');
    }
}
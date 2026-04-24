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

        // ==========================================
        // 1. RÉSIDENCE
        // ==========================================
        $sp_id = 'SP-87248712';
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
        $users = [
            ['id' => 1, 'full_name' => 'Syndic Admin', 'email' => 'admin@syndify.ma', 'tel' => '0600000000', 'identifier' => 'SU-0001'],
            ['id' => 2, 'full_name' => 'Ahmed Alami', 'email' => 'ahmed@gmail.com', 'tel' => '0661234567', 'identifier' => 'SU-0002'],
            ['id' => 3, 'full_name' => 'Sara Bennani', 'email' => 'sara@yahoo.fr', 'tel' => '0669876543', 'identifier' => 'SU-0003'],
            ['id' => 4, 'full_name' => 'Youssef Tazi', 'email' => 'youssef@hotmail.com', 'tel' => '0661112233', 'identifier' => 'SU-0004'],
        ];
        DB::table('users')->insert($users);

        $user_as_owner = [
            // 🟢 FIX HNA: Rddinaha 'balance_prev' blast 'solde' bash l-Dashboard y-9raha nichan
            ['user_id' => 1, 'propriete_id' => $sp_id, 'status' => 1, 'balance_prev' => 0],
            ['user_id' => 2, 'propriete_id' => $sp_id, 'status' => 1, 'balance_prev' => -2500.00], // Ahmed dima m-tqel
            ['user_id' => 3, 'propriete_id' => $sp_id, 'status' => 1, 'balance_prev' => 0],        // Sara m-regla
            ['user_id' => 4, 'propriete_id' => $sp_id, 'status' => 1, 'balance_prev' => -850.00],  // Youssef kay-tsalouh shwiya
        ];
        DB::table('user_as_owner')->insert($user_as_owner);

        // ==========================================
        // 3. LOTS (Appartements & Garages)
        // ==========================================
        $lots = [
            ['id' => 1, 'propriete_id' => $sp_id, 'type' => 'Appartement', 'numero_porte' => 'A1', 'etage' => '1', 'batiment' => 'A'],
            ['id' => 2, 'propriete_id' => $sp_id, 'type' => 'Garage', 'numero_porte' => 'G1', 'etage' => 'SS', 'batiment' => 'A'],
            ['id' => 3, 'propriete_id' => $sp_id, 'type' => 'Appartement', 'numero_porte' => 'A2', 'etage' => '2', 'batiment' => 'A'],
            ['id' => 4, 'propriete_id' => $sp_id, 'type' => 'Appartement', 'numero_porte' => 'B1', 'etage' => '1', 'batiment' => 'B'],
        ];
        DB::table('units')->insert($lots);

        $user_owner_unit = [
            ['user_id' => 2, 'unit_id' => 1, 'status' => 1],
            ['user_id' => 2, 'unit_id' => 2, 'status' => 1],
            ['user_id' => 3, 'unit_id' => 3, 'status' => 1],
            ['user_id' => 4, 'unit_id' => 4, 'status' => 1],
        ];
        DB::table('user_owner_unit')->insert($user_owner_unit);

        // ==========================================
        // 4. CLÉS DE RÉPARTITION
        // ==========================================
        $cle_generale = DB::table('cle_repartitions')->insertGetId([
            'propriete_id' => $sp_id, 'nom' => 'Charges Générales', 'tantiemes_total' => 10000, 'notes' => 'Répartition globale'
        ]);
        $cle_ascenseur = DB::table('cle_repartitions')->insertGetId([
            'propriete_id' => $sp_id, 'nom' => 'Frais Ascenseur', 'tantiemes_total' => 1000, 'notes' => 'Sauf RDC/Garages'
        ]);

        $unit_to_key = [
            ['unit_id' => 1, 'cle_repartition_id' => $cle_generale, 'tantieme' => 3500],
            ['unit_id' => 2, 'cle_repartition_id' => $cle_generale, 'tantieme' => 1000],
            ['unit_id' => 3, 'cle_repartition_id' => $cle_generale, 'tantieme' => 3500],
            ['unit_id' => 4, 'cle_repartition_id' => $cle_generale, 'tantieme' => 2000],
            
            ['unit_id' => 1, 'cle_repartition_id' => $cle_ascenseur, 'tantieme' => 400],
            ['unit_id' => 2, 'cle_repartition_id' => $cle_ascenseur, 'tantieme' => 0],
            ['unit_id' => 3, 'cle_repartition_id' => $cle_ascenseur, 'tantieme' => 400],
            ['unit_id' => 4, 'cle_repartition_id' => $cle_ascenseur, 'tantieme' => 200],
        ];
        DB::table('unit_to_key')->insert($unit_to_key);

        // ==========================================
        // 5. EXERCICES (2024 Clôturé & 2025 En cours)
        // ==========================================
        $se_2024 = 'EX-2024-001';
        $se_2025 = 'EX-2025-001';

        DB::table('exercices')->insert([
            [
                'propriete_id' => $sp_id, 'se_identifier' => $se_2024, 
                'start_date' => '2024-01-01', 'end_date' => '2024-12-31', 
                'status' => 'cloture', 'period' => 'Trimestriel' // 🟢 Exercice 9dim Sala
            ],
            [
                'propriete_id' => $sp_id, 'se_identifier' => $se_2025, 
                'start_date' => '2025-01-01', 'end_date' => '2025-12-31', 
                'status' => 'en cours', 'period' => 'Trimestriel' // 🟢 Exercice Jdid
            ]
        ]);

        DB::table('charges_previsionnelles')->insert([
            ['scp_identifier' => 'SCP-2024-001', 'se_identifier' => $se_2024, 'budget' => 50000.00], // Budget 9dim
            ['scp_identifier' => 'SCP-2025-001', 'se_identifier' => $se_2025, 'budget' => 60000.00]  // Budget jdid tzad
        ]);

        // ==========================================
        // 6. DÉPENSES (2024 & 2025)
        // ==========================================
        $dep_2024_1 = DB::table('depenses')->insertGetId(['sdep_identifier' => 'DEP-2024-001', 'se_identifier' => $se_2024, 'cle_repartition_id' => $cle_generale, 'type_charges' => 'Courante', 'sub_type_charges' => 'Entretien', 'title' => 'Nettoyage Décembre 2024', 'amount' => 1200, 'date' => '2024-12-05', 'created_at' => '2024-12-05', 'updated_at' => '2024-12-05']);
        $dep_2024_2 = DB::table('depenses')->insertGetId(['sdep_identifier' => 'DEP-2024-002', 'se_identifier' => $se_2024, 'cle_repartition_id' => $cle_ascenseur, 'type_charges' => 'Courante', 'sub_type_charges' => 'Ascenseur', 'title' => 'Pièce Ascenseur (Carte)', 'amount' => 3500, 'date' => '2024-11-20', 'created_at' => '2024-11-20', 'updated_at' => '2024-11-20']);

        $dep_2025_1 = DB::table('depenses')->insertGetId(['sdep_identifier' => 'DEP-2025-001', 'se_identifier' => $se_2025, 'cle_repartition_id' => $cle_generale, 'type_charges' => 'Courante', 'sub_type_charges' => 'Entretien', 'title' => 'Nettoyage Janvier 2025', 'amount' => 1500, 'date' => '2025-01-05', 'created_at' => '2025-01-05', 'updated_at' => '2025-01-05']);
        $dep_2025_2 = DB::table('depenses')->insertGetId(['sdep_identifier' => 'DEP-2025-002', 'se_identifier' => $se_2025, 'cle_repartition_id' => $cle_generale, 'type_charges' => 'Courante', 'sub_type_charges' => 'Eau/Electricité', 'title' => 'Facture Lydec (Eau/Ecl)', 'amount' => 850, 'date' => '2025-01-10', 'created_at' => '2025-01-10', 'updated_at' => '2025-01-10']);

        // Imputation des Dépenses (Pour l'historique)
        DB::table('depense_for_owner')->insert([
            // 2024
            ['depense_id' => $dep_2024_1, 'user_id' => 2, 'amount_due' => 540],
            ['depense_id' => $dep_2024_1, 'user_id' => 3, 'amount_due' => 420],
            ['depense_id' => $dep_2024_1, 'user_id' => 4, 'amount_due' => 240],
            // 2025
            ['depense_id' => $dep_2025_1, 'user_id' => 2, 'amount_due' => 675],
            ['depense_id' => $dep_2025_1, 'user_id' => 3, 'amount_due' => 525],
            ['depense_id' => $dep_2025_1, 'user_id' => 4, 'amount_due' => 300],
        ]);

        // ==========================================
        // 7. ENCAISSEMENTS (2024 & 2025)
        // ==========================================
        DB::table('encaissements')->insert([
            // L-khalass dyal l-3am li fat (2024)
            [
                'sen_identifier' => 'SEN-2024-001', 'se_identifier' => $se_2024, 'owner_id' => 3, 'date' => '2024-12-15', 'title' => 'Virement Bancaire - Sara (Solde 2024)', 
                'amount' => 4500.00, 'type_charges' => 'Courante', 'sub_type_charges' => 'Régularisation', 'created_at' => '2024-12-15', 'updated_at' => '2024-12-15'
            ],
            // L-khalass dyal had l-3am (2025)
            [
                'sen_identifier' => 'SEN-2025-001', 'se_identifier' => $se_2025, 'owner_id' => 3, 'date' => '2025-01-15', 'title' => 'Virement Bancaire - Sara (T1 2025)', 
                'amount' => 1302.50, 'type_charges' => 'Courante', 'sub_type_charges' => 'Cotisation Trimestrielle', 'created_at' => '2025-01-15', 'updated_at' => '2025-01-15'
            ], 
            [
                'sen_identifier' => 'SEN-2025-002', 'se_identifier' => $se_2025, 'owner_id' => 4, 'date' => '2025-01-20', 'title' => 'Espèces - Youssef (Avance)', 
                'amount' => 800.00, 'type_charges' => 'Courante', 'sub_type_charges' => 'Avance sur charges', 'created_at' => '2025-01-20', 'updated_at' => '2025-01-20'
            ], 
        ]);

        // ==========================================
        // 8. APPELS DE FONDS (2024 Clôturé & 2025 Actif)
        // ==========================================
        
        // --- APPEL 2024 (Trimestre 4 - Khelssouh) ---
        $af_2024 = 'AF-PL-2024-004';
        DB::table('appels_fonds')->insert([
            'af_identifier' => $af_2024, 'se_identifier' => $se_2024, 'type_charge' => 'previsionnel', 'sub_type' => 'planifie',
            'title' => 'Appel planifié (Trimestre 4 - 2024)', 'amount' => 12500.00, 'due_date' => '2024-10-01',
            'is_generated' => true, 'is_sent' => true, 'number_generated' => 3, 'number_sent' => 3, 'created_at' => '2024-10-01', 'updated_at' => '2024-10-01'
        ]);
        DB::table('appf_to_owner')->insert([
            ['af_identifier' => $af_2024, 'user_id' => 2, 'montant_du' => 5625.00, 'created_at' => '2024-10-01'], 
            ['af_identifier' => $af_2024, 'user_id' => 3, 'montant_du' => 4375.00, 'created_at' => '2024-10-01'], 
            ['af_identifier' => $af_2024, 'user_id' => 4, 'montant_du' => 2500.00, 'created_at' => '2024-10-01'], 
        ]);

        // --- APPEL 2025 (Trimestre 1 - Jdid) ---
        $af_2025 = 'AF-PL-2025-001';
        DB::table('appels_fonds')->insert([
            'af_identifier' => $af_2025, 'se_identifier' => $se_2025, 'type_charge' => 'previsionnel', 'sub_type' => 'planifie',
            'title' => 'Appel planifié (Trimestre 1 - 2025)', 'amount' => 15000.00, 'due_date' => '2025-01-01',
            'is_generated' => true, 'is_sent' => true, 'number_generated' => 3, 'number_sent' => 3, 'created_at' => $now, 'updated_at' => $now
        ]);
        DB::table('appf_to_owner')->insert([
            ['af_identifier' => $af_2025, 'user_id' => 2, 'montant_du' => 6750.00, 'created_at' => $now], 
            ['af_identifier' => $af_2025, 'user_id' => 3, 'montant_du' => 5250.00, 'created_at' => $now], 
            ['af_identifier' => $af_2025, 'user_id' => 4, 'montant_du' => 3000.00, 'created_at' => $now], 
        ]);

        $this->command->info('✅ L-Boss Final d-Seeder: 2024 Clôturé + 2025 En cours. Livraison m-nawwra! 🚀');
    }
}
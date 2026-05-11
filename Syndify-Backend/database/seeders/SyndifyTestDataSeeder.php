<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Faker\Factory as Faker;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SyndifyTestDataSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('fr_MA');

        // ==========================================
        // 0. VIDER LES TABLES ET DOSSIERS
        // ==========================================
        DB::statement('SET session_replication_role = replica;');
        DB::statement('TRUNCATE TABLE proprietes, users, user_as_owner, units, user_owner_unit, unit_to_key, exercices, cle_repartitions, encaissements, depenses, depense_for_owner, appels_fonds, appf_to_owner, clotures CASCADE;');
        DB::statement('SET session_replication_role = origin;');

        $sp_id = 'SP-87248712';
        $basePath = "proprietes/{$sp_id}";

        if (Storage::disk('public')->exists($basePath)) {
            Storage::disk('public')->deleteDirectory($basePath);
        }

        // ==========================================
        // 1. CRÉER UNE PROPRIÉTÉ
        // ==========================================
        $propData = [
            'nom' => 'Résidence Les Jardins de l\'Océan',
            'city' => 'Casablanca',
            'address' => 'Angle Boulevard Ghandi et Route de la Corniche',
            'created_at' => now(), 'updated_at' => now(),
        ];

        if (\Illuminate\Support\Facades\Schema::hasColumn('proprietes', 'sp_identifier')) {
            $propData['sp_identifier'] = $sp_id;
        } else {
            $propData['id'] = $sp_id; 
        }
        $proprieteId = DB::table('proprietes')->insertGetId($propData);

        // ==========================================
        // 2. CRÉER LA CLÉ DE RÉPARTITION 
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
                'balance_prev' => 0, 
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // ==========================================
        // 4. CRÉER LES LOTS ET RÉPARTIR LES TANTIÈMES LOGIQUEMENT
        // ==========================================
        $typesBiens = ['Appartement', 'Garage', 'Magasin'];
        $unitToKeyData = [];
        $totalTantiemesRepartis = 0;
        
        $userTantiemes = []; 
        foreach ($usersIds as $uid) { $userTantiemes[$uid] = 0; }

        for ($i = 0; $i < 120; $i++) {
            $type = $faker->randomElement($typesBiens);
            $unitId = DB::table('units')->insertGetId([
                'propriete_id' => $proprieteId, 'type' => $type,
                'numero_porte' => ($type === 'Appartement' ? 'A' : ($type === 'Garage' ? 'G' : 'M')) . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'etage' => $type === 'Appartement' ? $faker->numberBetween(1, 10) : 0,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            $ownerId = $usersIds[$i % 50];

            DB::table('user_owner_unit')->insert([
                'user_id' => $ownerId, 'unit_id' => $unitId, 'status' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            $tantiemeLot = ($i === 119) ? (10000 - $totalTantiemesRepartis) : round(10000 / 120, 4); 
            $totalTantiemesRepartis += $tantiemeLot;
            
            $userTantiemes[$ownerId] += $tantiemeLot; 

            $unitToKeyData[] = [
                'unit_id' => $unitId, 'cle_repartition_id' => $cleId, 'tantieme' => $tantiemeLot
            ];
        }

        foreach (array_chunk($unitToKeyData, 50) as $chunk) {
            DB::table('unit_to_key')->insert($chunk);
        }

        // ==========================================
        // 5. CRÉER LES EXERCICES COMPTABLES
        // ==========================================
        
        // 🟢 EXERCICE 1: 2025 (DÉJÀ CLOS - Pour l'historique)
        $se_id_2025 = 'EX-2025-CLOS';
        DB::table('exercices')->insert([
            'se_identifier' => $se_id_2025, 
            'propriete_id' => $sp_id, 
            'start_date' => '2025-01-01', 
            'end_date' => '2025-12-31',
            'status' => 'Clos', 
            'period' => 'trimestre', 
            'created_at' => now()->subYear(), 
            'updated_at' => now()->subYear(),
        ]);
        
        DB::table('charges_previsionnelles')->insert([
            'scp_identifier' => 'SCP-2025-'.Str::random(3), 
            'se_identifier' => $se_id_2025, 
            'budget' => 150000,
            'total_encaissements' => 140000, 
            'total_depenses' => 135000
        ]);

        // 🟢 EXERCICE 2: 2026 (EN COURS - Pour le Dashboard et la Clôture)
        $se_id = 'EX-2026-TEST';
        DB::table('exercices')->insert([
            'se_identifier' => $se_id, 
            'propriete_id' => $sp_id, 
            'start_date' => '2026-01-01', 
            'end_date' => '2026-12-31',
            'status' => 'en cours',
            'period' => 'trimestre', 
            'created_at' => now(), 
            'updated_at' => now(),
        ]);
        
        // --- A. BUDGET PRÉVISIONNEL 2026 ---
        $scp_id = 'SCP-2026-'.Str::random(3);
        DB::table('charges_previsionnelles')->insert([
            'scp_identifier' => $scp_id, 
            'se_identifier' => $se_id, 
            'budget' => 200000,
            'total_encaissements' => 180000, 
            'total_depenses' => 150000
        ]);

        DB::table('bp_to_key')->insert([
            'scp_identifier' => $scp_id,
            'cle_repartition_id' => $cleId,
            'budget' => 200000,
            'depenses' => 150000,
        ]);

        // --- B. BUDGET TRAVAUX 2026 ---
        $sct_id = 'SCT-2026-'.Str::random(3);
        DB::table('charges_travaux')->insert([
            'sct_identifier' => $sct_id, 
            'se_identifier' => $se_id, 
            'budget' => 50000,
            'total_encaissements' => 40000, 
            'total_depenses' => 35000
        ]);

        // ==========================================
        // 6. APPELS DE FONDS DÉTAILLÉS (2026)
        // ==========================================
        $appfToOwner = [];
        $budgetAnnuel = 200000;
        $montantTrimestre = $budgetAnnuel / 4; 

        // 🟢 A. LES APPELS PLANIFIÉS
        $trimestres = [
            ['num' => 1, 'date' => '2026-01-01', 'titre' => 'Appel de fonds Trimestre 1 - 2026'],
            ['num' => 2, 'date' => '2026-04-01', 'titre' => 'Appel de fonds Trimestre 2 - 2026'],
            ['num' => 3, 'date' => '2026-07-01', 'titre' => 'Appel de fonds Trimestre 3 - 2026'],
            ['num' => 4, 'date' => '2026-10-01', 'titre' => 'Appel de fonds Trimestre 4 - 2026'],
        ];

        foreach ($trimestres as $trim) {
            $af_id = 'AF-T' . $trim['num'] . '-' . Str::random(4);

            DB::table('appels_fonds')->insert([
                'af_identifier' => $af_id, 
                'se_identifier' => $se_id, 
                'type_charge' => 'previsionnel', 
                'sub_type' => 'planifie',
                'title' => $trim['titre'], 
                'amount' => $montantTrimestre,
                'due_date' => $trim['date'],
                'is_generated' => true, 
                'is_sent' => true, 
                'number_generated' => count($usersIds), 
                'number_sent' => count($usersIds),
                'created_at' => now(), 'updated_at' => now()
            ]);

            foreach ($usersIds as $uid) {
                $montantDu = $montantTrimestre * ($userTantiemes[$uid] / 10000); 
                $appfToOwner[] = [
                    'af_identifier' => $af_id, 'user_id' => $uid, 'montant_du' => $montantDu, 'created_at' => now()
                ];
                DB::table('user_as_owner')->where('user_id', $uid)->decrement('balance_prev', $montantDu);
            }
        }

        // 🟢 B. NOUVEAU: L'APPEL EXCEPTIONNEL
        $af_exc_id = 'AF-EXC-' . Str::random(4);
        $montantExceptionnel = 25000;

        DB::table('appels_fonds')->insert([
            'af_identifier' => $af_exc_id, 
            'se_identifier' => $se_id, 
            'type_charge' => 'previsionnel', 
            'sub_type' => 'exceptionnel',
            'title' => 'Appel de fonds Exceptionnel - Réparation urgente', 
            'amount' => $montantExceptionnel,
            'due_date' => '2026-05-15',
            'is_generated' => true, 
            'is_sent' => false, // Nkhalliweh false bash nshoufou bouton "Envoyer" f l-UI
            'number_generated' => count($usersIds), 
            'number_sent' => 0,
            'created_at' => now(), 'updated_at' => now()
        ]);

        foreach ($usersIds as $uid) {
            $montantDu = $montantExceptionnel * ($userTantiemes[$uid] / 10000); 
            $appfToOwner[] = [
                'af_identifier' => $af_exc_id, 'user_id' => $uid, 'montant_du' => $montantDu, 'created_at' => now()
            ];
            DB::table('user_as_owner')->where('user_id', $uid)->decrement('balance_prev', $montantDu);
        }

        // Insérer tout dans appf_to_owner
        foreach (array_chunk($appfToOwner, 50) as $chunk) {
            DB::table('appf_to_owner')->insert($chunk);
        }

        // ==========================================
        // 7. ENCAISSEMENTS (Paiements 2026)
        // ==========================================
        $encaissements = [];
        $indexEnc = 1; 
        foreach (array_slice($usersIds, 0, 20) as $uid) {
            $montantPaye = 5000; 
            $encaissements[] = [
                'sen_identifier' => 'ENC-' . time() . '-' . ($indexEnc++) . '-' . rand(1000, 9999), 
                'se_identifier' => $se_id,
                'owner_id' => $uid, 
                'title' => 'Paiement Cotisation', 
                'amount' => $montantPaye,
                'date' => $faker->dateTimeBetween('2026-01-01', '2026-04-30')->format('Y-m-d'),
                'type_charges' => 'previsionnel', 
                'sub_type_charges' => 'planifié',
                'created_at' => now(), 
                'updated_at' => now()
            ];
            DB::table('user_as_owner')->where('user_id', $uid)->increment('balance_prev', $montantPaye);
        }
        DB::table('encaissements')->insert($encaissements);

        // ==========================================
        // 8. DÉPENSES (2026)
        // ==========================================
        $depenseId = DB::table('depenses')->insertGetId([
            'sdep_identifier' => 'DEP-' . time() . '-1', 'se_identifier' => $se_id, 'cle_repartition_id' => $cleId,
            'title' => 'Facture Lydec + Nettoyage', 'amount' => 12500, 'date' => '2026-02-15',
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

      // ==========================================
        // 9. 🟢 GÉNÉRATION DES DOCUMENTS (PDF) AVEC LE NOUVEAU DESIGN
        // ==========================================
        $this->command->info('⏳ Création des Documents PDF en cours...');

        Storage::disk('public')->makeDirectory("{$basePath}/appels_fonds");
        Storage::disk('public')->makeDirectory("{$basePath}/reminders");
        Storage::disk('public')->makeDirectory("{$basePath}/encaissements");
        Storage::disk('public')->makeDirectory("{$basePath}/assemblees");
        Storage::disk('public')->makeDirectory("{$basePath}/contrats");

        // 🟢 Fonction jdida (BLA PLAN B! BLA IF/ELSE!)
        $createPdfFromView = function($path, $viewName, $data, $fallbackTitle) {
            
            if ($viewName === 'generic') {
                $html = "<div style='font-family: Helvetica, Arial, sans-serif; padding: 40px;'>
                            <h1 style='color: #1e1b4b; font-size: 28px;'>{$fallbackTitle}</h1>
                         </div>";
                $pdf = Pdf::loadHTML($html);
            } else {
                $pdf = Pdf::loadView("pdf.{$viewName}", $data)
                ->setOption(['isRemoteEnabled' => true]);
            }
            
            Storage::disk('public')->put($path, $pdf->output());
        };

        // 1. Générer Appels de Fonds
        $createPdfFromView("{$basePath}/appels_fonds/Appel_Fonds_T1_2026.pdf", 'appel_fonds', [
            'reference' => 'SAF-T1-2026',
            'destinataire' => 'Salma Boulal',
            'adresse' => 'Angle Boulevard Ghandi et Route de la Corniche',
            'lots' => 'Appartement A001',
            'montant' => 1250.00,
            'date_limite' => '31/01/2026',
            'telephone' => '06 00 00 00 00',
            'email' => 'syndic@residence.com',
            'iban' => 'MA64 007 780 0000000000000000'
        ], "");

        $createPdfFromView("{$basePath}/appels_fonds/Appel_Fonds_T2_2026.pdf", 'appel_fonds', [
            'reference' => 'SAF-T2-2026',
            'destinataire' => 'Salma Boulal',
            'adresse' => 'Angle Boulevard Ghandi et Route de la Corniche',
            'lots' => 'Appartement A001',
            'montant' => 1250.00,
            'date_limite' => '30/04/2026',
            'telephone' => '06 00 00 00 00',
            'email' => 'syndic@residence.com',
            'iban' => 'MA64 007 780 0000000000000000'
        ], "");

        // 2. Générer Reçu d'encaissement
        $createPdfFromView("{$basePath}/encaissements/Recu_Paiement_T1_2026.pdf", 'recu', [
            'reference' => 'REC-2026-001',
            'destinataire' => 'Salma Boulal',
            'adresse' => 'Angle Boulevard Ghandi et Route de la Corniche',
            'lots' => 'Appartement A001',
            'montant' => 1250.00,
            'date_paiement' => '15/01/2026',
            'mode_paiement' => 'Virement Bancaire',
            'periode' => 'Trimestre 1 - 2026',
            'type_frais' => 'Cotisation Prévisionnelle',
            'telephone' => '06 00 00 00 00',
            'email' => 'syndic@residence.com',
            'iban' => 'MA64 007 780 0000000000000000'
        ], "");

        // 3. Générer le reste (Rappels, PV, Contrats)
        $createPdfFromView("{$basePath}/reminders/Rappel_Impaye.pdf", 'appel_fonds', [
            'reference' => 'RAP-2026-001',
            'destinataire' => 'Salma Boulal',
            'adresse' => 'Angle Boulevard Ghandi et Route de la Corniche',
            'lots' => 'Appartement A001',
            'montant' => 2500.00,
            'date_limite' => 'Immédiat',
            'telephone' => '06 00 00 00 00',
            'email' => 'syndic@residence.com',
            'iban' => 'MA64 007 780 0000000000000000'
        ], "");

        $createPdfFromView("{$basePath}/assemblees/PV_Assemblee_Generale_2025.pdf", 'generic', [], "PV de l'Assemblée Générale 2025");
        $createPdfFromView("{$basePath}/contrats/Contrat_Syndic_2026.pdf", 'generic', [], "Contrat de Syndic (Exercice 2026)");

        $this->command->info('🚀 BOOM! Seeder Complet (Appels Planifiés + Exceptionnels) exécuté avec succès!');
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf; // 🟢 Zidna DomPDF hna bash n-saybou Vrai PDF

class SyndifyDocumentSeeder extends Seeder
{
    public function run()
    {
        $sp_id = 'SP-87248712';
        $basePath = "proprietes/{$sp_id}";

        $this->command->info('⏳ Création des VRAIS documents PDF en cours...');

        // 1. N-ms7ou l-dossier l-9dim ila kan bash n-bdayo mn ziro
        if (Storage::disk('public')->exists($basePath)) {
            Storage::disk('public')->deleteDirectory($basePath);
        }

        // 2. N-creyiw l-Dossiers
        Storage::disk('public')->makeDirectory("{$basePath}/appels_fonds");
        Storage::disk('public')->makeDirectory("{$basePath}/reminders");
        Storage::disk('public')->makeDirectory("{$basePath}/encaissements");
        Storage::disk('public')->makeDirectory("{$basePath}/assemblees");
        Storage::disk('public')->makeDirectory("{$basePath}/contrats");

        // 3. 🟢 Fonction jdida kat-sayeb VRAI PDF
        $createRealPdf = function($path, $title) {
            // HTML dyal l-PDF
            $html = "
                <div style='font-family: Arial, sans-serif; text-align: center; padding: 40px;'>
                    <h1 style='color: #251b5c;'>Syndify Document</h1>
                    <h2 style='color: #444;'>{$title}</h2>
                    <p style='margin-top: 30px; font-size: 14px;'>Ceci est un document PDF valide généré automatiquement pour la démonstration.</p>
                    <hr style='margin-top: 50px; border: 0; border-top: 1px solid #ddd;'>
                    <p style='color: #888; font-size: 11px;'>Date de génération : " . date('d/m/Y H:i') . "</p>
                </div>
            ";

            // Génération dyal PDF
            $pdf = Pdf::loadHTML($html);
            
            // Sauvegarde f l-Chemin s7i7
            Storage::disk('public')->put($path, $pdf->output());
        };

        // ==========================================
        // 4. N-3emmrou l-Dossiers b les Vrais PDFs
        // ==========================================

        $this->command->info('Génération des Appels de fonds...');
        $createRealPdf("{$basePath}/appels_fonds/Appel_Fonds_T1_2025.pdf", "Appel de Fonds T1 - 2025"); 
        $createRealPdf("{$basePath}/appels_fonds/Appel_Fonds_T4_2024.pdf", "Appel de Fonds T4 - 2024"); 

        $this->command->info('Génération des Rappels...');
        $createRealPdf("{$basePath}/reminders/Rappel_Impaye_Ahmed.pdf", "Rappel Impayé - Propriétaire Ahmed"); 
        $createRealPdf("{$basePath}/reminders/Mise_en_demeure_Youssef.pdf", "Mise en Demeure - Propriétaire Youssef"); 

        $this->command->info('Génération des Encaissements...');
        $createRealPdf("{$basePath}/encaissements/Recu_Paiement_Sara_T1.pdf", "Reçu de Paiement - Sara"); 
        $createRealPdf("{$basePath}/encaissements/Recu_Paiement_Youssef_Avance.pdf", "Reçu de Paiement (Avance) - Youssef"); 

        $this->command->info('Génération des Assemblées...');
        $createRealPdf("{$basePath}/assemblees/PV_Assemblee_Generale_2024.pdf", "PV de l'Assemblée Générale 2024"); 
        $createRealPdf("{$basePath}/assemblees/Convocation_AG_2025.pdf", "Convocation à l'Assemblée Générale 2025"); 

        $this->command->info('Génération des Contrats...');
        $createRealPdf("{$basePath}/Contrat_Syndic_2025.pdf", "Contrat de Syndic (Exercice 2025)"); 
        $createRealPdf("{$basePath}/Reglement_Copropriete.pdf", "Règlement de la Copropriété"); 
        $createRealPdf("{$basePath}/Devis_Reparation_Ascenseur.pdf", "Devis de Réparation - Ascenseur Bloc A"); 

        $this->command->info('✅ Vrais documents PDF générés avec succès !');
    }
}
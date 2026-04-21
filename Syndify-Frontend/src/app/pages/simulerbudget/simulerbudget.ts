import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { PageHeader } from '../../components/page-header/page-header';
import { HttpClient } from '@angular/common/http'; // 🟢 Zidna HttpClient bach n-3eytou l-API

@Component({
  selector: 'app-simulation-budget',
  standalone: true,
  imports: [CommonModule, FormsModule, PageHeader],
  templateUrl: './simulerbudget.html',
})
export class SimulationBudget implements OnInit {
  
  proprieteId: string = '';
  montantSaisi: number | null = null;
  cleSelectionnee: string = ''; // 🟢 Khass t-bda khawya bach t-ched l-lowla mn l-API
  activeTab: 'coproprietaire' | 'lot' = 'coproprietaire';

  isLoading: boolean = false;
  listeCles: any[] = []; // 🟢 Hna ghadi n-khebiw ga3 l-data lli jat mn Laravel
  resultatsSimulation: any[] = [];

  constructor(
    private http: HttpClient,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit() {
    this.proprieteId = localStorage.getItem('active_propriete_id') || 'SP-1775215295';
    this.chargerDonneesSimulation();
  }

// ==========================================
  // 1. RÉCUPÉRATION DES DONNÉES API (FIX POST)
  // ==========================================
  chargerDonneesSimulation() {
    this.isLoading = true;
    
    // 🟢 L-URL bla l-paramètre, 7it ghadi n-siftouh f l-payload
    const apiUrl = `http://nomade-cloud.com:8085/api/simulation/charger`;
    
    // 🟢 N-wejdo l-Payload (ID dyal Propriété)
    const payload = {
        sp_identifier: this.proprieteId
    };

    // 🟢 Derna .post blast .get
    this.http.post(apiUrl, payload).subscribe({
      next: (res: any) => {
        if (res.success && res.data.length > 0) {
          this.listeCles = res.data;
          this.cleSelectionnee = this.listeCles[0].id; // N-selectionniw l-clé l-lowla par défaut
          console.log('✅ Données de simulation chargées:', this.listeCles);
        }
        this.isLoading = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        console.error('❌ Erreur API:', err);
        this.isLoading = false;
        alert("Erreur de connexion au serveur.");
      }
    });
  }

  setTab(tab: 'coproprietaire' | 'lot') {
    this.activeTab = tab;
    if (this.montantSaisi) {
       this.genererSimulation(); // N-3awdou l-calcul ila beddelna l-Tab
    }
  }

  // ==========================================
  // 2. GÉNÉRER LE CALCUL
  // ==========================================
  genererSimulation() {
    if (!this.montantSaisi || !this.cleSelectionnee) {
        alert("Veuillez saisir un montant et sélectionner une clé.");
        return;
    }

    console.log(`🚀 Simulation en cours: ${this.montantSaisi} DH sur la clé ID: ${this.cleSelectionnee}`);

    // N-jebdou l-data dyal l-clé lli khtar l-utilisateur
    const cleActuelle = this.listeCles.find(c => c.id == this.cleSelectionnee);
    if (!cleActuelle || !cleActuelle.lots) return;

    this.resultatsSimulation = [];

    // 🟢 LOGIQUE DYAL L-CALCUL
    if (this.activeTab === 'lot') {
        
        // Affichage par Lot (Sahl, kol lot w l-7a9 dyalo)
        this.resultatsSimulation = cleActuelle.lots.map((lot: any) => {
            const part = this.montantSaisi! * (lot.tantieme / lot.tantiemes_total);
            return {
                identifiant: lot.lot_identifiant || `Lot ${lot.lot_id}`,
                budget: part,
                total: part
            };
        });

    } else if (this.activeTab === 'coproprietaire') {
        
        // Affichage par Copropriétaire (Khassna n-jem3ou l-lots dyal nfs chakhs)
        const mapProprietaires = new Map<string, any>();

        cleActuelle.lots.forEach((lot: any) => {
            const part = this.montantSaisi! * (lot.tantieme / lot.tantiemes_total);
            const ownerId = lot.owner_id || 'sans-proprio';
            const ownerName = lot.owner_name || 'Sans propriétaire';

            if (mapProprietaires.has(ownerId)) {
                // Ila kanch 3ndo lot akhor, n-zidouh 3la l-9dim
                const existant = mapProprietaires.get(ownerId);
                existant.budget += part;
                existant.total += part;
            } else {
                // Ila awel merra n-l9awh
                mapProprietaires.set(ownerId, {
                    nom: ownerName,
                    budget: part,
                    total: part
                });
            }
        });

        // N-rddouh tableau bach y-ban f HTML
        this.resultatsSimulation = Array.from(mapProprietaires.values());
    }

    this.cdr.detectChanges();
  }
}
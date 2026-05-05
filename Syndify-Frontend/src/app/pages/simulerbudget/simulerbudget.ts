import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { PageHeader } from '../../components/page-header/page-header';
import { HttpClient } from '@angular/common/http'; 

@Component({
  selector: 'app-simulation-budget',
  standalone: true,
  imports: [CommonModule, FormsModule, PageHeader],
  templateUrl: './simulerbudget.html',
})
export class SimulationBudget implements OnInit {
  
  proprieteId: string = '';
  montantSaisi: number | null = null;
  cleSelectionnee: string = ''; 
  activeTab: 'coproprietaire' | 'lot' = 'coproprietaire';

  isLoading: boolean = false;
  listeCles: any[] = []; 
  resultatsSimulation: any[] = [];
  residenceInfo = { nom: '...', adresse: '...' };

  constructor(
    private http: HttpClient,
    private cdr: ChangeDetectorRef
  ) {}

 ngOnInit() {
  
    this.chargerDonneesSimulation();
  }

chargerDonneesSimulation() {
   this.isLoading = true;
   const apiUrl = `http://51.178.87.234:8085/api/simulation/charger`; 
   
   // 🟢 Kantsiftou requête khawya, l-Backend houwa li ghadi y3raf chkoun l-user
   this.http.post(apiUrl, {}).subscribe({
     next: (res: any) => {
       if (res.success) {
         if (res.residence) {
           this.residenceInfo = res.residence;
         }

         if (res.data && res.data.length > 0) {
           this.listeCles = res.data;
           
           // Nkhliyo simulation logic kima hya
           const savedMontant = localStorage.getItem('sim_montant');
           const savedCle = localStorage.getItem('sim_cle');
           
           if (savedMontant && savedCle) {
               this.montantSaisi = Number(savedMontant);
               this.cleSelectionnee = savedCle;
               this.genererSimulation(); 
           } else {
               this.cleSelectionnee = this.listeCles[0].id;
           }
         }
       }
       this.isLoading = false;
       this.cdr.detectChanges();
     },
     error: (err) => {
       console.error('❌ Erreur API Simulation:', err);
       this.isLoading = false;
       this.cdr.detectChanges();
     }
   });
  }
  setTab(tab: 'coproprietaire' | 'lot') {
    this.activeTab = tab;
    if (this.montantSaisi && this.cleSelectionnee) {
       this.genererSimulation(); 
    }
  }

  genererSimulation() {
    if (!this.montantSaisi || !this.cleSelectionnee) {
        alert("Veuillez saisir un montant et sélectionner une clé.");
        return;
    }

    const cleActuelle = this.listeCles.find(c => c.id == this.cleSelectionnee);
    if (!cleActuelle || !cleActuelle.lots) return;

    this.resultatsSimulation = [];

    if (this.activeTab === 'lot') {
        this.resultatsSimulation = cleActuelle.lots.map((lot: any) => {
            const part = this.montantSaisi! * (Number(lot.tantieme) / Number(lot.tantiemes_total));
            
            const typeBien = lot.type || 'Appartement';
            const numPorte = lot.numero_porte || lot.lot_id || lot.id;
            const nomCompletBien = `${typeBien} ${numPorte}`;

            return {
                identifiant: nomCompletBien,
                budget: part,
                total: part
            };
        });
    }
    else if (this.activeTab === 'coproprietaire') {
        const mapProprietaires = new Map<string, any>();

        cleActuelle.lots.forEach((lot: any) => {
            const part = this.montantSaisi! * (Number(lot.tantieme) / Number(lot.tantiemes_total));
            const ownerId = lot.owner_id ? lot.owner_id.toString() : 'sans-proprio';
            const ownerName = lot.owner_name || 'Sans propriétaire';

            if (mapProprietaires.has(ownerId)) {
                const existant = mapProprietaires.get(ownerId);
                existant.budget += part;
                existant.total += part; 
            } else {
                mapProprietaires.set(ownerId, {
                    nom: ownerName,
                    budget: part,
                    total: part 
                });
            }
        });
        this.resultatsSimulation = Array.from(mapProprietaires.values());
    }

    localStorage.setItem('sim_montant', this.montantSaisi.toString());
    localStorage.setItem('sim_cle', this.cleSelectionnee.toString());

    this.cdr.detectChanges();
  }

  formatLotId(id: any): string {
    if (!id) return '';
    const cleanId = String(id).replace(/\D/g, ''); 
    if (!cleanId) return String(id); 
    const visualId = Number(cleanId) + 845752; 
    return 'LOT-' + visualId.toString().padStart(8, '0');
  }
}
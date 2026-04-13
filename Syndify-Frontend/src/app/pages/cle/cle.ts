import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { PageHeader } from '../../components/page-header/page-header';
import { FormsModule } from '@angular/forms';
import { CleRepartitionService } from '../../services/cle-repartition'; 
import { LotService } from '../../services/lot'; 

@Component({
  selector: 'app-cle',
  standalone: true,
  imports: [CommonModule, PageHeader, FormsModule], 
  templateUrl: './cle.html',
})
export class Cle implements OnInit {
  
  proprieteId: string = 'SP-1775215295'; 
  currentTab: 'lots' | 'coproprietaires' = 'lots';
  isLoading: boolean = false;
  activeDropdown: string | number | null = null;
  
  clesList: any[] = []; 
  lignesTableau: any[] = []; 
  
  isAddModalOpen: boolean = false;
  isSaving: boolean = false;
  tousLesLots: any[] = []; 
  
  cleForm: any = {
    id: null,
    nom_cle: '',
    tantiemes_total: null,
    notes: '',
    unites: [] 
  };

  isDeleteModalOpen: boolean = false;
  cleToDelete: number | null = null;

  // 🟢 Bach ngeddou n-st3mlo parseFloat f HTML
  parseFloat = parseFloat;

  constructor(
    private cleService: CleRepartitionService,
    private lotService: LotService,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit() {
    this.chargerDonnees();
  }

  changerTab(tab: 'lots' | 'coproprietaires') {
    this.currentTab = tab;
  }

  chargerDonnees() {
    this.isLoading = true;
    
    this.lotService.getListe(this.proprieteId).subscribe({
      next: (res) => {
        if (res.success) this.tousLesLots = res.data;
      },
      error: (err) => console.error('Erreur API Lot :', err)
    });

    this.cleService.getListe(this.proprieteId).subscribe({
      next: (res: any) => {
        if (res.success) {
          this.clesList = res.data;
          this.formaterTableau();
        }
        this.isLoading = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        console.error('Erreur API Cle :', err);
        this.isLoading = false;
      }
    });
  }

  formaterTableau() {
    const lotsMap = new Map();

    this.clesList.forEach(cle => {
      if (cle.lots && Array.isArray(cle.lots)) {
        cle.lots.forEach((lot: any) => {
          if (!lotsMap.has(lot.id)) {
            const proprio = lot.owners && lot.owners.length > 0 ? lot.owners[0].full_name : '-- Non affecté --';
            
            lotsMap.set(lot.id, {
              id: lot.id,
              numero_porte: lot.numero_porte,
              details: `${lot.type} ${lot.numero_porte} / ${lot.batiment || ''} / ${lot.etage || ''}`,
              coproprietaire: proprio,
              tantiemes: {} 
            });
          }
          lotsMap.get(lot.id).tantiemes[cle.id] = lot.tantieme_applied;
        });
      }
    });

    this.lignesTableau = Array.from(lotsMap.values());
  }

  openAddModal(cleAModifier: any = null) {
    this.closeDropdown();

    if (cleAModifier) {
      const unitesFormatees = this.tousLesLots.map(lot => {
        const lotTrouve = cleAModifier.lots.find((l: any) => l.id === lot.id);
        return {
          id_unite: lot.id,
          numero_porte: lot.numero_porte,
          tantieme_applique: lotTrouve ? parseFloat(lotTrouve.tantieme_applied) : 0 
        };
      });

      this.cleForm = {
        id: cleAModifier.id,
        nom_cle: cleAModifier.nom,
        tantiemes_total: parseFloat(cleAModifier.tantiemes_total),
        notes: cleAModifier.notes,
        unites: unitesFormatees
      };
    } else {
      this.cleForm = {
        id: null,
        nom_cle: '',
        tantiemes_total: 10000, 
        notes: '',
        unites: this.tousLesLots.map(l => ({
          id_unite: l.id,
          numero_porte: l.numero_porte, 
          tantieme_applique: 0
        }))
      };
    }
    
    this.isAddModalOpen = true;
  }

  closeAddModal() {
    this.isAddModalOpen = false;
  }

  // 🟢 HNA: L'modification dyal la sauvegarde
  enregistrerCle() {
    const totalSaisi = this.getSommeTantiemes();
    const totalAttendu = parseFloat(Number(this.cleForm.tantiemes_total).toFixed(4));

    if (totalSaisi !== totalAttendu) {
      alert(`La somme des tantièmes (${totalSaisi}) ne correspond pas au total (${totalAttendu}).`);
      return;
    }

    this.isSaving = true;
    
    const payload: any = {
      propriete_id: this.proprieteId,
      nom_cle: this.cleForm.nom_cle,
      tantiemes_total: totalAttendu, // Kan-sifto l'vrai decimal
      notes: this.cleForm.notes,
      unites: this.cleForm.unites
    };

    if (this.cleForm.id) {
      payload.scr_identifier = this.cleForm.id;
    }

    const action = this.cleForm.id 
      ? this.cleService.modifier(payload) 
      : this.cleService.ajouter(payload);

    action.subscribe({
      next: (res: any) => {
        if (res.success) {
          this.closeAddModal();
          this.chargerDonnees();
        }
        this.isSaving = false;
      },
      error: (err) => {
        alert(err.error?.message || "Une erreur s'est produite.");
        this.isSaving = false;
      }
    });
  }

  // 🟢 HNA: L'modification dyal la somme avec arrondi 4 chiffres
  getSommeTantiemes(): number {
    if (!this.cleForm.unites) return 0;
    const sum = this.cleForm.unites.reduce((acc: number, u: any) => acc + (parseFloat(u.tantieme_applique) || 0), 0);
    return parseFloat(sum.toFixed(4)); 
  }

  supprimerCle(id: number) {
    this.closeDropdown();
    this.cleToDelete = id;
    this.isDeleteModalOpen = true;
  }

  closeDeleteModal() {
    this.isDeleteModalOpen = false;
    this.cleToDelete = null;
  }

  confirmerSuppression() {
    if (!this.cleToDelete) return;

    this.cleService.supprimer(this.proprieteId, this.cleToDelete).subscribe({
      next: (res: any) => {
        if (res.success) {
          this.closeDeleteModal();
          this.chargerDonnees();
        }
      },
      error: (err) => alert("Erreur lors de la suppression.")
    });
  }

  toggleDropdown(cleId: string | number, event: Event) {
    event.stopPropagation();
    this.activeDropdown = this.activeDropdown === cleId ? null : cleId;
  }

  closeDropdown() {
    this.activeDropdown = null;
  }
}
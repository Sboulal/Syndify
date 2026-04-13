import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { PageHeader } from '../../components/page-header/page-header'; 
import { LotService } from '../../services/lot';
import { CoproprietaireService } from '../../services/coproprietaire'; // 🟢 Zidna hada l'affectation
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-gestion-lots',
  standalone: true,
  imports: [CommonModule, PageHeader, FormsModule],
  templateUrl: './gestion-lots.html',
})
export class GestionLots implements OnInit {
  
  proprieteId: string = '';
  lots: any[] = []; 
  listCoproprietaires: any[] = []; // 🟢 Liste dyal ga3 l'owners
  isLoading: boolean = false;

  isModalOpen: boolean = false;
  activeDropdown: string | null = null;
  
  lotForm: any = {
    id: null,
    type: 'Appartement',
    batiment: '',
    etage: '',
    numero_porte: '',
    owner_id: null,        // 🟢 ID dyal l'proprietaire
    owner_status: 'Actif'  // 🟢 Actif wla Inactif
  };

  // Variables dyal Custom Notification (Toast)
  showNotification: boolean = false;
  notificationMessage: string = '';
  notificationType: 'success' | 'error' = 'success';
  notificationTimeout: any;

  // Variables dyal Modal de Suppression
  isDeleteModalOpen: boolean = false;
  lotToDelete: any = null;

  constructor(
    private lotService: LotService,
    private coproprietaireService: CoproprietaireService, 
    private route: ActivatedRoute,
    private cdr: ChangeDetectorRef 
  ) {}

  ngOnInit() {
    this.proprieteId = 'SP-1775215295'; 
    this.chargerLots(); 
    this.chargerUsers(); // 🟢 Njibou nass f lewel
  }

  // 🟢 Fonction bach njibou ga3 les copropriétaires l'Dropdown
  chargerUsers() {
    this.coproprietaireService.getListe(this.proprieteId, 'tous').subscribe({
      next: (res: any) => {
        if (res.success) {
          this.listCoproprietaires = res.data;
        }
      }
    });
  }

  showToast(message: string, type: 'success' | 'error' = 'success') {
    this.notificationMessage = message;
    this.notificationType = type;
    this.showNotification = true;
    this.cdr.detectChanges();

    if (this.notificationTimeout) {
      clearTimeout(this.notificationTimeout);
    }
    this.notificationTimeout = setTimeout(() => {
      this.showNotification = false;
      this.cdr.detectChanges();
    }, 3000);
  }

  chargerLots() {
    if (!this.proprieteId) return;
    
    this.isLoading = true;
    this.cdr.detectChanges(); 
    
    this.lotService.getListe(this.proprieteId).subscribe({
      next: (res: any) => {
        if (res.success) {
          this.lots = res.data; 
        }
        this.isLoading = false;
        this.cdr.detectChanges(); 
      },
      error: (err) => {
        this.isLoading = false;
        this.showToast("Erreur lors du chargement des lots.", 'error'); 
        this.cdr.detectChanges(); 
      }
    });
  }

  toggleDropdown(numeroId: string, event: Event) {
    event.stopPropagation();
    this.activeDropdown = this.activeDropdown === numeroId ? null : numeroId;
    this.cdr.detectChanges(); 
  }

  closeDropdown() {
    if (this.activeDropdown !== null) {
      this.activeDropdown = null;
      this.cdr.detectChanges(); 
    }
  }

  openModal(lotAModifier: any = null) {
    if (lotAModifier) {
      // 🟢 Kanjibou l'owner Actif (wla Inactif) lli m-affecté l'had lot
      let currentOwnerId = null;
      let currentStatus = 'Actif';
      
      if (lotAModifier.owners && lotAModifier.owners.length > 0) {
          // Kanfdrdo bli l'backend kaysifet array dyal owners
          currentOwnerId = lotAModifier.owners[0].id;
          currentStatus = lotAModifier.owners[0].pivot_status == 1 ? 'Actif' : 'Inactif';
      }

      this.lotForm = {
        id: lotAModifier.id,
        type: lotAModifier.type,
        batiment: lotAModifier.batiment,
        etage: lotAModifier.etage,
        numero_porte: lotAModifier.numero_porte,
        owner_id: currentOwnerId,
        owner_status: currentStatus
      };
    } else {
      this.lotForm = { 
        id: null, type: 'Appartement', batiment: '', etage: '', numero_porte: '', 
        owner_id: null, owner_status: 'Actif' 
      };
    }
    
    this.isModalOpen = true;
    this.closeDropdown();
    this.cdr.detectChanges(); 
  }

  closeModal() {
    this.isModalOpen = false;
    this.cdr.detectChanges(); 
  }

  enregistrerLot() {
    const payload = {
      propriete_id: this.proprieteId,
      type: this.lotForm.type,
      batiment: this.lotForm.batiment,
      etage: this.lotForm.etage,
      numero_porte: this.lotForm.numero_porte,
      owner_id: this.lotForm.owner_id,         // 🟢 ID dyal l'owner
      owner_status: this.lotForm.owner_status, // 🟢 Statut (Actif/Inactif)
      ...(this.lotForm.id && { lot_id: this.lotForm.id }) 
    };

    const action = this.lotForm.id 
      ? this.lotService.modifier(payload) 
      : this.lotService.ajouter(payload);

    action.subscribe({
      next: (res: any) => {
        if (res.success) {
          this.closeModal(); 
          this.chargerLots(); 
          this.showToast(res.message || "Opération réussie !", 'success'); 
        }
      },
      error: (err) => {
        this.showToast(err.error?.message || "Une erreur est survenue.", 'error'); 
      }
    });
  }

  supprimerLot(lot: any) {
    this.closeDropdown();
    this.lotToDelete = lot;
    this.isDeleteModalOpen = true;
    this.cdr.detectChanges();
  }

  closeDeleteModal() {
    this.isDeleteModalOpen = false;
    this.lotToDelete = null;
    this.cdr.detectChanges();
  }

  confirmerSuppression() {
    if (!this.lotToDelete) return;

    this.lotService.supprimer(this.proprieteId, this.lotToDelete.id).subscribe({
      next: (res: any) => {
        if (res.success) {
          this.chargerLots(); 
          this.showToast("Lot supprimé avec succès.", 'success');
          this.closeDeleteModal();
        }
      },
      error: (err) => {
        this.showToast(err.error?.message || "Erreur lors de la suppression.", 'error');
        this.closeDeleteModal();
      }
    });
  }
}
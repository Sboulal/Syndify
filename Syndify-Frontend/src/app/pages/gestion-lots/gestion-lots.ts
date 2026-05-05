import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute } from '@angular/router'; 
import { PageHeader } from '../../components/page-header/page-header'; 
import { LotService } from '../../services/lot';
import { CoproprietaireService } from '../../services/coproprietaire'; 
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-gestion-lots',
  standalone: true,
  imports: [CommonModule, PageHeader, FormsModule],
  templateUrl: './gestion-lots.html',
})
export class GestionLots implements OnInit {
  

  lots: any[] = []; 
  listCoproprietaires: any[] = []; 
  isLoading: boolean = false;
  residenceInfo = { nom: '...', adresse: '...' };

  isModalOpen: boolean = false;
  activeDropdown: number | null = null;
  
  lotForm: any = {
    id: null,
    random_ref: '',
    type: 'Appartement',
    batiment: '',
    etage: '',
    numero_porte: '',
    owner_id: null,
    owner_status: 'Actif'
  };

  isAddOwnerModalOpen: boolean = false;
  isAddingOwner: boolean = false;
  newOwner: any = {
    nom: '',
    email: '',
    tel: '',
    status: 'Actif'
  };

  showNotification: boolean = false;
  notificationMessage: string = '';
  notificationType: 'success' | 'error' = 'success';
  notificationTimeout: any;

  isDeleteModalOpen: boolean = false;
  lotToDelete: any = null;

  constructor(
    private lotService: LotService,
    private coproprietaireService: CoproprietaireService, 
    private route: ActivatedRoute,
    private cdr: ChangeDetectorRef 
  ) {}

  ngOnInit() {
    console.log('🟢 [ngOnInit] Démarrage de Gestion des Lots...');
    this.chargerLots(); 
    this.chargerUsers(); 
  }

  chargerUsers() {
    console.log('⏳ [chargerUsers] Chargement des copropriétaires...');
    // 🟢 Modifier l-appel bach y-wafi l-service jdid
    this.coproprietaireService.getListe('tous').subscribe({
      next: (res: any) => {
        if (res.success) {
          this.listCoproprietaires = res.data;
        }
      },
      error: (err: any) => {
        console.error('❌ [chargerUsers] Erreur Backend:', err);
      }
    });
  }

  showToast(message: string, type: 'success' | 'error' = 'success') {
    this.notificationMessage = message;
    this.notificationType = type;
    this.showNotification = true;
    this.cdr.detectChanges();
    if (this.notificationTimeout) clearTimeout(this.notificationTimeout);
    this.notificationTimeout = setTimeout(() => {
      this.showNotification = false;
      this.cdr.detectChanges();
    }, 3000);
  }

  chargerLots() {
    this.isLoading = true;
    this.lotService.getListe().subscribe({
      next: (res: any) => {
        if (res.success) {
          this.lots = res.data;
          // 🟢 Cheddi s-smiya d-bsse7 mn l-Backend!
          this.residenceInfo = res.residence;
        }
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
  }
  toggleDropdown(lotId: number, event: Event) {
    event.stopPropagation();
    this.activeDropdown = this.activeDropdown === lotId ? null : lotId;
    this.cdr.detectChanges(); 
  }

  closeDropdown() {
    if (this.activeDropdown !== null) {
      this.activeDropdown = null;
      this.cdr.detectChanges(); 
    }
  }

  openModal(lotAModifier: any = null) {
    this.activeDropdown = null;
    
    if (lotAModifier) {
      let currentOwnerId = null;
      if (lotAModifier.owners && lotAModifier.owners.length > 0) {
          currentOwnerId = lotAModifier.owners[0].user_id || lotAModifier.owners[0].id; 
      }

      this.lotForm = {
        id: lotAModifier.id,
        random_ref: lotAModifier.random_ref,
        type: lotAModifier.type,
        batiment: lotAModifier.batiment,
        etage: lotAModifier.etage,
        numero_porte: lotAModifier.numero_porte,
        owner_id: currentOwnerId,
        owner_status: 'Actif'
      };
    } else {
      this.lotForm = { 
        id: null, type: 'Appartement', batiment: '', etage: '', numero_porte: '', random_ref: '',
        owner_id: null, owner_status: 'Actif' 
      };
    }
    
    this.isModalOpen = true;
    this.cdr.detectChanges(); 
  }

  closeModal() {
    this.isModalOpen = false;
    this.cdr.detectChanges(); 
  }

  openAddOwnerModal() {
    this.newOwner = { nom: '', email: '', tel: '', status: 'Actif' };
    this.isAddOwnerModalOpen = true;
    this.cdr.detectChanges();
  }

  closeAddOwnerModal() {
    this.isAddOwnerModalOpen = false;
    this.cdr.detectChanges();
  }

  ajouterProprietaire() {
    if (!this.newOwner.nom || !this.newOwner.email) return;

    this.isAddingOwner = true;
    this.cdr.detectChanges();

    // 🔴 7iyedna proprieteId
    this.coproprietaireService.ajouter(this.newOwner).subscribe({
      next: (res: any) => {
        if (res.success) {
          this.coproprietaireService.getListe('tous').subscribe({
            next: (resList: any) => {
              if (resList.success) {
                this.listCoproprietaires = resList.data;
                if (resList.data && resList.data.length > 0) {
                  this.lotForm.owner_id = resList.data[0].user_id || resList.data[0].id; 
                }
              }
            }
          });
          this.closeAddOwnerModal();
          this.showToast("Propriétaire ajouté et sélectionné !", 'success');
        }
        this.isAddingOwner = false;
        this.cdr.detectChanges();
      },
      error: (err: any) => {
        this.showToast(err.error?.message || "Erreur lors de l'ajout.", 'error');
        this.isAddingOwner = false;
        this.cdr.detectChanges();
      }
    });
  }

  enregistrerLot() {
    if (!this.lotForm.numero_porte) {
      this.showToast("Veuillez remplir le N° de porte.", "error");
      return;
    }

    const payload = {
      // 🔴 7iyedna proprieteId mn l-payload
      type: this.lotForm.type,
      batiment: this.lotForm.batiment,
      etage: this.lotForm.etage,
      numero_porte: this.lotForm.numero_porte,
      owner_id: this.lotForm.owner_id, 
      owner_status: 'Actif',
      owners: this.lotForm.owner_id ? [{ owner_id: this.lotForm.owner_id, owner_status: 'Actif' }] : [],
      ...(this.lotForm.id && { lot_id: this.lotForm.id }) 
    };

    const action = this.lotForm.id ? this.lotService.modifier(payload) : this.lotService.ajouter(payload);

    action.subscribe({
      next: (res: any) => {
        if (res.success) {
          this.closeModal(); 
          this.chargerLots(); 
          this.showToast(res.message || "Opération réussie !", 'success'); 
        }
      },
      error: (err: any) => {
        this.showToast(err.error?.message || "Une erreur est survenue.", 'error'); 
      }
    });
  }

formatLotId(id: any): string {
    if (!id) return '';
    
    // 🟢 FIX: N-zidou base kbira (matalan 845752) 3la l-ID l-asli
    // Bash ila kan l-ID f MySQL hwa 32, ghadi y-wlli 845784 f l-Affichage
    const visualId = Number(id) + 845752; 
    
    return 'BN-' + visualId.toString().padStart(8, '0');
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
    
    // 🔴 7iyedna proprieteId
    this.lotService.supprimer(this.lotToDelete.id).subscribe({
      next: (res: any) => {
        if (res.success) {
          this.chargerLots(); 
          this.showToast("Lot supprimé avec succès.", 'success');
          this.closeDeleteModal();
        }
      },
      error: (err: any) => {
        this.showToast(err.error?.message || "Erreur lors de la suppression.", 'error');
        this.closeDeleteModal();
      }
    });
  }
}
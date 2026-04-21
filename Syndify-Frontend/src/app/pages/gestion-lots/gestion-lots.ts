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
  
  proprieteId: string = '';
  lots: any[] = []; 
  listCoproprietaires: any[] = []; 
  isLoading: boolean = false;

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
    this.proprieteId = 'SP-1775215295'; 
    console.log('🟢 [ngOnInit] Démarrage... Propriété ID:', this.proprieteId);
    this.chargerLots(); 
    this.chargerUsers(); 
  }

  chargerUsers() {
    console.log('⏳ [chargerUsers] Chargement des copropriétaires...');
    this.coproprietaireService.getListe(this.proprieteId, 'tous').subscribe({
      next: (res: any) => {
        console.log('📦 [chargerUsers] Réponse Backend:', res);
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
    if (!this.proprieteId) return;
    this.isLoading = true;
    this.cdr.detectChanges(); 
    
    console.log('⏳ [chargerLots] Chargement des lots depuis le Backend...');
    this.lotService.getListe(this.proprieteId).subscribe({
      next: (res: any) => {
        console.log('📦 [chargerLots] Réponse Backend:', res);
        if (res.success) {
          this.lots = res.data.map((lot: any) => {
            const randomNum = Math.floor(10000000 + Math.random() * 90000000);
            lot.random_ref = 'SB-' + randomNum;
            return lot;
          });
          console.log('✅ [chargerLots] Lots formatés:', this.lots);
        }
        this.isLoading = false;
        this.cdr.detectChanges(); 
      },
      error: (err: any) => {
        console.error('❌ [chargerLots] Erreur Backend:', err);
        this.isLoading = false;
        this.showToast("Erreur lors du chargement des lots.", 'error'); 
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
    console.log('🟢 [openModal] Clic sur Ajouter/Modifier. Données reçues:', lotAModifier);
    
    if (lotAModifier) {
      let currentOwnerId = null;
      if (lotAModifier.owners && lotAModifier.owners.length > 0) {
          currentOwnerId = lotAModifier.owners[0].user_id || lotAModifier.owners[0].id; // 🟢 T2kedi mn l'ID wach user_id wla id
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
      console.log('📝 [openModal] Mode Modification. Formulaire initialisé:', this.lotForm);
    } else {
      this.lotForm = { 
        id: null, type: 'Appartement', batiment: '', etage: '', numero_porte: '', random_ref: '',
        owner_id: null, owner_status: 'Actif' 
      };
      console.log('📝 [openModal] Mode Ajout. Formulaire vidé:', this.lotForm);
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
    console.log('🚀 [ajouterProprietaire] Envoi au Backend:', this.newOwner);
    this.cdr.detectChanges();

    this.coproprietaireService.ajouter(this.proprieteId, this.newOwner).subscribe({
      next: (res: any) => {
        console.log('✅ [ajouterProprietaire] Réponse:', res);
        if (res.success) {
          // Recharger les users
          this.coproprietaireService.getListe(this.proprieteId, 'tous').subscribe({
            next: (resList: any) => {
              if (resList.success) {
                this.listCoproprietaires = resList.data;
                if (resList.data && resList.data.length > 0) {
                  this.lotForm.owner_id = resList.data[0].user_id || resList.data[0].id; 
                  console.log('🎯 [ajouterProprietaire] Nouveau proprio auto-sélectionné:', this.lotForm.owner_id);
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
        console.error('❌ [ajouterProprietaire] Erreur Backend:', err);
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
      propriete_id: this.proprieteId,
      type: this.lotForm.type,
      batiment: this.lotForm.batiment,
      etage: this.lotForm.etage,
      numero_porte: this.lotForm.numero_porte,
      owner_id: this.lotForm.owner_id, 
      owner_status: 'Actif',
      owners: this.lotForm.owner_id ? [{ owner_id: this.lotForm.owner_id, owner_status: 'Actif' }] : [],
      ...(this.lotForm.id && { lot_id: this.lotForm.id }) 
    };

    console.log('🚀 [enregistrerLot] Envoi au Backend du Payload:', payload);

    const action = this.lotForm.id ? this.lotService.modifier(payload) : this.lotService.ajouter(payload);

    action.subscribe({
      next: (res: any) => {
        console.log('✅ [enregistrerLot] Réponse Backend:', res);
        if (res.success) {
          this.closeModal(); 
          this.chargerLots(); 
          this.showToast(res.message || "Opération réussie !", 'success'); 
        }
      },
      error: (err: any) => {
        console.error('❌ [enregistrerLot] Erreur Backend:', err);
        this.showToast(err.error?.message || "Une erreur est survenue.", 'error'); 
      }
    });
  }

  formatLotId(id: any): string {
    if (!id) return '';
    return 'SB-' + id.toString().padStart(8, '0');
  }

  supprimerLot(lot: any) {
    this.closeDropdown();
    this.lotToDelete = lot;
    this.isDeleteModalOpen = true;
    console.log('🗑️ [supprimerLot] Préparation à la suppression du lot:', lot);
    this.cdr.detectChanges();
  }

  closeDeleteModal() {
    this.isDeleteModalOpen = false;
    this.lotToDelete = null;
    this.cdr.detectChanges();
  }

  confirmerSuppression() {
    if (!this.lotToDelete) return;
    
    console.log('🚀 [confirmerSuppression] Envoi de la requête de suppression pour ID:', this.lotToDelete.id);

    this.lotService.supprimer(this.proprieteId, this.lotToDelete.id).subscribe({
      next: (res: any) => {
        console.log('✅ [confirmerSuppression] Réponse Backend:', res);
        if (res.success) {
          this.chargerLots(); 
          this.showToast("Lot supprimé avec succès.", 'success');
          this.closeDeleteModal();
        }
      },
      error: (err: any) => {
        console.error('❌ [confirmerSuppression] Erreur Backend:', err);
        this.showToast(err.error?.message || "Erreur lors de la suppression.", 'error');
        this.closeDeleteModal();
      }
    });
  }
}
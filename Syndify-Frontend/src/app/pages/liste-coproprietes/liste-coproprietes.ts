import { Component, OnInit, ChangeDetectorRef } from '@angular/core'; 
import { CommonModule } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { PageHeader } from '../../components/page-header/page-header';
import { CoproprietaireService } from '../../services/coproprietaire'; 
import { LotService } from '../../services/lot'; 
import { FormsModule } from '@angular/forms'; 

@Component({
  selector: 'app-liste-coproprietaires',
  standalone: true,
  imports: [CommonModule, PageHeader, FormsModule], 
  templateUrl: './liste-coproprietes.html',
})
export class ListeCoproprietes implements OnInit {
  proprieteId: string = ''; 
  currentStatus: 'actif' | 'inactif' | 'en_attente' = 'actif';
  totalAffichage: number = 0;
  
  coproprietaires: any[] = [];
  lastId: number | null = null;
  isThereMore: boolean = false;
  isLoading: boolean = false;

  tousLesLots: any[] = [];
  isLotsDropdownOpen: boolean = false;

  isAddModalOpen: boolean = false;
  isAdding: boolean = false;
  
  newCopro: any = {
    user_id: '',
    nom: '',
    email: '',
    tel: '',
    lots: '',
    selectedLots: [], 
    status: 'Actif'
  };

  activeDropdown: string | null = null;

  // 🟢 Variables dyal Custom Notification (Toast)
  showNotification: boolean = false;
  notificationMessage: string = '';
  notificationType: 'success' | 'error' = 'success';
  notificationTimeout: any;

  // 🟢 Variables dyal Modal de Suppression
  isDeleteModalOpen: boolean = false;
  coproToDeleteId: string | null = null;

  constructor(
    private coproprietaireService: CoproprietaireService,
    private lotService: LotService, 
    private route: ActivatedRoute,
    private cdr: ChangeDetectorRef 
  ) {}

  ngOnInit() {
    this.proprieteId = 'SP-1775215295'; 
    this.chargerListe(true); 
    this.chargerLots(); 
  }

  // 🟢 Fonction dyal Notification Toast
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
    this.lotService.getListe(this.proprieteId).subscribe({
      next: (res: any) => {
        if (res.success) this.tousLesLots = res.data;
      }
    });
  }

  toggleLotSelection(lotId: number) {
    const index = this.newCopro.selectedLots.indexOf(lotId);
    if (index > -1) {
      this.newCopro.selectedLots.splice(index, 1); 
    } else {
      this.newCopro.selectedLots.push(lotId); 
    }
  }

  getSelectedLotsText(): string {
    if (!this.newCopro.selectedLots || this.newCopro.selectedLots.length === 0) {
      return 'Sélectionner des lots...';
    }
    const selected = this.tousLesLots.filter(l => this.newCopro.selectedLots.includes(l.id));
    return selected.map(l => 'N° ' + l.numero_porte).join(', ');
  }

  changerTab(status: 'actif' | 'inactif' | 'en_attente') {
    if (this.currentStatus !== status) {
      this.currentStatus = status;
      this.chargerListe(true);
    }
  }

  chargerListe(reset: boolean = false) {
    if (this.isLoading || !this.proprieteId) return; 

    if (reset) {
      this.coproprietaires = [];
      this.lastId = null;
    }

    this.isLoading = true;
    this.cdr.detectChanges(); 

    this.coproprietaireService.getListe(this.proprieteId, this.currentStatus, this.lastId ?? undefined)
      .subscribe({
        next: (response: any) => {
          if (response.success) {
            this.coproprietaires = [...this.coproprietaires, ...response.data];
            this.isThereMore = response.is_there_more;
            if (response.data.length > 0) {
              this.lastId = response.data[response.data.length - 1].id;
            }
          }
          this.isLoading = false;
          this.cdr.detectChanges();
        },
        error: (err) => {
          this.isLoading = false;
          this.showToast("Erreur lors du chargement.", 'error');
          this.cdr.detectChanges();
        }
      });
  }

  // 🟢 HNA: Fonction jdida li kat7el ghir l'Modal dyal Suppression
  supprimerCopro(userId: string) {
    this.closeDropdown(); 
    this.coproToDeleteId = userId;
    this.isDeleteModalOpen = true;
    this.cdr.detectChanges();
  }

  // 🟢 HNA: Fonction katssed l'Modal dyal Suppression
  closeDeleteModal() {
    this.isDeleteModalOpen = false;
    this.coproToDeleteId = null;
    this.cdr.detectChanges();
  }

  // 🟢 HNA: Fonction li katsifet l'Api bash tsouprimi
  confirmerSuppression() {
    if (!this.coproToDeleteId) return;

    this.coproprietaireService.supprimer(this.proprieteId, this.coproToDeleteId).subscribe({
      next: (res: any) => {
        if (res.success) {
          this.chargerListe(true); 
          this.showToast("Copropriétaire supprimé avec succès.", 'success');
          this.closeDeleteModal();
        }
      },
      error: (err: any) => {
        this.showToast(err.error?.message || 'Erreur lors de la suppression', 'error');
        this.closeDeleteModal();
      }
    });
  }

  toggleDropdown(userId: string, event: Event) {
    event.stopPropagation();
    this.activeDropdown = this.activeDropdown === userId ? null : userId;
  }

  closeDropdown() {
    this.activeDropdown = null;
  }

  openEditModal(copro: any) {
    this.closeDropdown(); 
    this.newCopro = { 
      user_id: copro.user_id, 
      nom: copro.nom, 
      email: copro.email, 
      tel: copro.tel,
      selectedLots: copro.lot_ids ? [...copro.lot_ids] : [], 
      status: copro.status 
    };
    this.isAddModalOpen = true;
    this.cdr.detectChanges();
  }

  openAddModal() {
    this.newCopro = { 
      user_id: '', 
      nom: '', 
      email: '', 
      tel: '',
      selectedLots: [], 
      status: 'Actif' 
    };
    
    this.isAddModalOpen = true;
    this.cdr.detectChanges();
  }

  closeAddModal() {
    this.isAddModalOpen = false;
    this.isLotsDropdownOpen = false; 
    this.cdr.detectChanges();
  }

  ajouterCopro() {
    if (!this.newCopro.email || !this.newCopro.nom || !this.proprieteId) return;

    this.isAdding = true;
    this.cdr.detectChanges();

    this.coproprietaireService.ajouter(this.proprieteId, this.newCopro).subscribe({
      next: (res: any) => {
        if (res.success) {
          this.closeAddModal();
          this.chargerListe(true); 
          this.showToast(res.message || "Opération réussie !", 'success'); // 🟢 Toast Success
        }
        this.isAdding = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        this.showToast(err.error?.message || "Erreur lors de l'ajout.", 'error'); // 🟢 Toast Error
        this.isAdding = false;
        this.cdr.detectChanges();
      }
    });
  }
  
  getSelectedLotObjects(): any[] {
    if (!this.newCopro.selectedLots) return [];
    return this.tousLesLots.filter(l => this.newCopro.selectedLots.includes(l.id));
  }

  supprimerLotSelectionne(lotId: number, event: Event) {
    event.stopPropagation(); 
    this.toggleLotSelection(lotId);
  }
}
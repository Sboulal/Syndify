import { Component, OnInit, ChangeDetectorRef } from '@angular/core'; 
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
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

  isAddModalOpen: boolean = false;
  isAdding: boolean = false;
  
  newCopro: any = {
    user_id: '',
    nom: '',
    email: '',
    tel: '',
    selectedLots: [], 
    status: 'Actif'
  };


  isLotsModalOpen: boolean = false;
  selectedCoproName: string = '';
  selectedCoproLots: any[] = [];
  activeDropdown: string | null = null;

  showNotification: boolean = false;
  notificationMessage: string = '';
  notificationType: 'success' | 'error' = 'success';
  notificationTimeout: any;

  isDeleteModalOpen: boolean = false;
  coproToDeleteId: string | null = null;

  constructor(
    private coproprietaireService: CoproprietaireService,
    private lotService: LotService,
    private route: ActivatedRoute,
    private router: Router,
    private cdr: ChangeDetectorRef 
  ) {}

  ngOnInit() {
    this.proprieteId = 'SP-1775215295'; 
    this.chargerListe(true); 
    this.chargerLots(); 
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
    this.lotService.getListe(this.proprieteId).subscribe({
      next: (res: any) => {
        if (res.success) this.tousLesLots = res.data;
      }
    });
  }

  getSelectedLotObjects(): any[] {
    if (!this.newCopro.selectedLots || this.newCopro.selectedLots.length === 0) return [];
    return this.tousLesLots.filter(l => this.newCopro.selectedLots.includes(l.id));
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

  supprimerCopro(userId: string) {
    this.closeDropdown(); 
    this.coproToDeleteId = userId;
    this.isDeleteModalOpen = true;
    this.cdr.detectChanges();
  }

  closeDeleteModal() {
    this.isDeleteModalOpen = false;
    this.coproToDeleteId = null;
    this.cdr.detectChanges();
  }

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

  voirHistorique(userId: number) {
    this.router.navigate(['/coproprietaires/historique', userId]); 
  }

  // 🟢 Fonction jdida mlli t-clicki 3la l-badge dyal "Nbr de biens"
voirListeLots(copro: any, event: Event) {
    event.stopPropagation(); 
    this.closeDropdown();
    
    this.selectedCoproName = copro.nom;
    
    // Kan-jebdo les objets dyal les lots mn tousLesLots b l'ID dyalhom
    if (copro.lot_ids && copro.lot_ids.length > 0) {
      this.selectedCoproLots = this.tousLesLots.filter(l => copro.lot_ids.includes(l.id));
    } else {
      this.selectedCoproLots = [];
    }
    
    this.isLotsModalOpen = true;
    this.cdr.detectChanges();
  }

  toggleDropdown(userId: string, event: Event) {
    event.stopPropagation();
    this.activeDropdown = this.activeDropdown === userId ? null : userId;
    this.cdr.detectChanges();
  }

  closeDropdown() {
    this.activeDropdown = null;
    this.cdr.detectChanges();
  }

  openEditModal(copro: any, event?: Event) {
    if (event) event.stopPropagation(); // 🟢 Zidna hada bach n-mne3 l-click yt-rchercher l-fo9
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

  formatCoproId(id: any): string {
    if (!id) return '';
    return 'SP-' + id.toString().padStart(8, '0');
  }

  closeAddModal() {
    this.isAddModalOpen = false;
    this.cdr.detectChanges();
  }

 
  closeLotsModal() {
    this.isLotsModalOpen = false;
    this.selectedCoproLots = [];
    this.cdr.detectChanges();
  }

  ajouterCopro() {
    if (!this.newCopro.email || !this.newCopro.nom || !this.proprieteId) return;

    this.isAdding = true;
    this.cdr.detectChanges();

    const payload = {
      nom: this.newCopro.nom,
      email: this.newCopro.email,
      tel: this.newCopro.tel,
      status: this.newCopro.status,
      selectedLots: this.newCopro.selectedLots,
      user_id: this.newCopro.user_id, 
      owner_id: this.newCopro.user_id 
    };

    this.coproprietaireService.ajouter(this.proprieteId, payload).subscribe({
      next: (res: any) => {
        if (res.success) {
          this.closeAddModal();
          this.chargerListe(true); 
          this.showToast(res.message || "Opération réussie !", 'success'); 
        }
        this.isAdding = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        this.showToast(err.error?.message || "Erreur lors de l'ajout.", 'error'); 
        this.isAdding = false;
        this.cdr.detectChanges();
      }
    });
  }
}
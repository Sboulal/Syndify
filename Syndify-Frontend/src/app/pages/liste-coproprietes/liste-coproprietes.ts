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
  
  // 🔴 7iyedna proprieteId mn hna f merra!
  currentStatus: 'actif' | 'inactif' | 'en_attente' = 'actif';
  totalAffichage: number = 0;

  residenceInfo = { nom: '...', adresse: '...' };
  
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
    // 🟢 Angular kay-3eyet nichan, l-Backend kay-3ref chkoun lli m-connecté!
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
    // 🔴 7iyedna proprieteId mn l-paramètres
    this.lotService.getListe().subscribe({
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
    if (this.isLoading) return; 

    if (reset) {
      this.coproprietaires = [];
      this.lastId = null;
    }

    this.isLoading = true;
    this.cdr.detectChanges(); 

    this.coproprietaireService.getListe(this.currentStatus, this.lastId ?? undefined)
      .subscribe({
        next: (response: any) => {
          if (response.success) {
            // 🟢 Fix: Response m-appendiya m3a d-data l-9dima (Pagination)
            this.coproprietaires = [...this.coproprietaires, ...response.data];
            
            // 🟢 Fix: response.residence (machi res.residence)
            if (response.residence) {
              this.residenceInfo = response.residence;
            }

            this.isThereMore = response.is_there_more;
            
            if (response.data.length > 0) {
              // T2kkdi blli l-id smitou 'user_id' kifma rj3nah f l-Backend
              this.lastId = response.data[response.data.length - 1].user_id;
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

    // 🔴 7iyedna proprieteId mn l-paramètres
    this.coproprietaireService.supprimer(this.coproToDeleteId).subscribe({
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

  voirListeLots(copro: any, event: Event) {
    event.stopPropagation(); 
    this.closeDropdown();
    
    this.selectedCoproName = copro.nom;
    
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
    if (event) event.stopPropagation(); 
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
    
    // 🟢 FIX: N-zidou base kbira (matalan 845752) b7al l-Lots
    // Bash ila kan l-ID hwa 3, ghadi y-wlli 845755 f l-Affichage
    const visualId = Number(id) + 845752; 
    
    return 'COP-' + visualId.toString().padStart(8, '0');
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
    // 🔴 7iyedna verfication dyal proprieteId
    if (!this.newCopro.email || !this.newCopro.nom) return;

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

    // 🔴 7iyedna proprieteId mn l-paramètres
    this.coproprietaireService.ajouter(payload).subscribe({
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
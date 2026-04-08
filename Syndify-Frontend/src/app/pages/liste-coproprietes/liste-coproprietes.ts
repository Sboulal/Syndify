import { Component, OnInit, ChangeDetectorRef } from '@angular/core'; 
import { CommonModule } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { PageHeader } from '../../components/page-header/page-header';
import { CoproprietaireService } from '../../services/coproprietaire'; 
import { FormsModule } from '@angular/forms'; 

@Component({
  selector: 'app-liste-coproprietaires',
  standalone: true,
  imports: [CommonModule, PageHeader, FormsModule], 
  templateUrl: './liste-coproprietes.html',
})
export class ListeCoproprietes implements OnInit {
  // 1. Les variables d'état
  proprieteId: string = ''; 
  currentStatus: 'actif' | 'inactif' | 'en_attente' = 'actif';
  totalAffichage: number = 0;
  
  coproprietaires: any[] = [];
  lastId: number | null = null;
  isThereMore: boolean = false;
  isLoading: boolean = false;

  // 2. Variables dyal l'Modal dyal l'Ajout
  isAddModalOpen: boolean = false;
  isAdding: boolean = false;
  
  newCopro: any = {
    user_id: '',
    nom: '',
    email: '',
    lots: '',
    status: 'Actif'
  };

  // ==========================================
  // 🛑 FIX: VARIABLES W FONCTIONS DYAL L-DROPDOWN 
  // ==========================================
  activeDropdown: string | null = null;

  toggleDropdown(userId: string, event: Event) {
    event.stopPropagation();
    this.activeDropdown = this.activeDropdown === userId ? null : userId;
  }

  closeDropdown() {
    this.activeDropdown = null;
  }

  openEditModal(copro: any) {
    this.closeDropdown(); // N-seddo l-menu 9bel ma n-7ellou l-modal
    // N-3mmro l-Modal b l-m3loumat dyal l-copropriétaire
    this.newCopro = { 
      user_id: copro.user_id, 
      nom: copro.nom, 
      email: copro.email, 
      tel: copro.tel,
      lots: copro.lots === '-- Non affecté --' ? '' : copro.lots, 
      status: copro.status 
    };
    this.isAddModalOpen = true;
    this.cdr.detectChanges();
  }
  // ==========================================


  constructor(
    private coproprietaireService: CoproprietaireService,
    private route: ActivatedRoute,
    private cdr: ChangeDetectorRef 
  ) {}

  ngOnInit() {
    console.log('🚀 [INIT] Page "Liste Copropriétaires" chargée.');
    
    // ID forcé pour le test
    this.proprieteId = 'SP-1775215295'; 
    console.log('📌 ID forcé pour le test:', this.proprieteId);
    this.chargerListe(true); 
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
          console.error("Erreur API Liste:", err);
          this.isLoading = false;
          this.cdr.detectChanges();
        }
      });
  }

 supprimerCopro(userId: string) {
    this.closeDropdown(); // N-seddo l-menu
    if (confirm('Voulez-vous vraiment supprimer ce copropriétaire ?')) {
      this.coproprietaireService.supprimer(this.proprieteId, userId).subscribe({
        next: (res: any) => {
          if (res.success) {
            this.chargerListe(true); 
          }
        },
        error: (err: any) => {
          alert(err.error?.message || 'Erreur lors de la suppression');
        }
      });
    }
  }

openAddModal() {

 

   
    this.newCopro = { 
      user_id: '', 
      nom: '', 
      email: '', 
      tel: '',
      lots: '', 
      status: 'Actif' 
    };
    
    this.isAddModalOpen = true;
    this.cdr.detectChanges();
  }

  closeAddModal() {
    this.isAddModalOpen = false;
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
        }
        this.isAdding = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        console.error("Erreur API Ajout:", err);
        alert(err.error?.message || "Erreur lors de l'ajout.");
        this.isAdding = false;
        this.cdr.detectChanges();
      }
    });
  }
}
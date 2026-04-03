import { Component, OnInit, ChangeDetectorRef } from '@angular/core'; // 🛑 Zdt ChangeDetectorRef
import { CommonModule } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { PageHeader } from '../../components/page-header/page-header';
import { CoproprietaireService } from '../../services/coproprietaire'; // T2kdi mn l'chemin
import { FormsModule } from '@angular/forms'; // 🛑 Zdt FormsModule bash t-khdem ngModel

@Component({
  selector: 'app-liste-coproprietaires',
  standalone: true,
  imports: [CommonModule, PageHeader, FormsModule], // 🛑 Zdt FormsModule hna
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

  // 🛑 Variables dyal l'Modal dyal l'Ajout
  isAddModalOpen: boolean = false;
  newCoproEmail: string = '';
  isAdding: boolean = false;

  constructor(
    private coproprietaireService: CoproprietaireService,
    private route: ActivatedRoute,
    private cdr: ChangeDetectorRef // 🛑 L'Injection dyal cdr bash UI t-tjaweb b-zerba
  ) {}

ngOnInit() {
    console.log('🚀 [INIT] Page "Liste Copropriétaires" chargée.');
    
    // 🛑 KHTWA MO2AQATA: N-7ettou l'ID b-yeddina bash n-testiw d-deqqa l-wla
    this.proprieteId = 'SP-1775215295'; // 👈 T2kdi blli had l'ID houwa li 3ndk f BDD
    
    console.log('📌 ID forcé pour le test:', this.proprieteId);
    this.chargerListe(true); 
    
    /* 🛑 COMMENTINA HADA MO2A9ATAN BASH MAY-KHRBQNACH
    this.route.paramMap.subscribe(params => {
      const id = params.get('id'); 
      if (id) {
        this.proprieteId = id;
        this.chargerListe(true); 
      } else {
        const savedId = localStorage.getItem('current_propriete_id');
        if (savedId) {
          this.proprieteId = savedId;
          this.chargerListe(true);
        } else {
          console.error("Aucune propriété sélectionnée !");
        }
      }
    });
    */
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

  desactiverCopro(userId: string) {
    if (confirm('Voulez-vous vraiment désactiver ce copropriétaire ?')) {
      this.coproprietaireService.desactiver(this.proprieteId, userId).subscribe({
        next: (res: any) => {
          if (res.success) {
            this.chargerListe(true); 
          }
        },
        error: (err) => {
          alert(err.error?.message || 'Erreur lors de la désactivation');
        }
      });
    }
  }

  // ==========================================
  // 🛑 NOUVELLES FONCTIONS : AJOUTER UN COPROPRIÉTAIRE
  // ==========================================
  openAddModal() {
    this.newCoproEmail = ''; // N-khwiw l'input
    this.isAddModalOpen = true;
    this.cdr.detectChanges();
  }

  closeAddModal() {
    this.isAddModalOpen = false;
    this.cdr.detectChanges();
  }

  ajouterCopro() {
    if (!this.newCoproEmail || !this.proprieteId) return;

    this.isAdding = true;
    this.cdr.detectChanges();

    this.coproprietaireService.ajouter(this.proprieteId, this.newCoproEmail).subscribe({
      next: (res: any) => {
        if (res.success) {
          this.closeAddModal();
          this.chargerListe(true); // N-rechargew l'liste bach i-ban j-jdid
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
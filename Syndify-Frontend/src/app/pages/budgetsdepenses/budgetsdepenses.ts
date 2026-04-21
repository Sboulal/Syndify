import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { PageHeader } from '../../components/page-header/page-header';
import { BudgetService } from '../../services/budget'; 
import { ExerciceService } from '../../services/exercice'; 
import { CoproprietaireService } from '../../services/coproprietaire';
import { CleRepartitionService } from '../../services/cle-repartition';

@Component({
  selector: 'app-budgets-operations',
  standalone: true,
  imports: [CommonModule, FormsModule, PageHeader],
  templateUrl: './budgetsdepenses.html',
})
export class BudgetsOperations implements OnInit {
  
  proprieteId: string = '';
  activeTab: 'previsionnel' | 'travaux' = 'previsionnel';
  
  exercices: any[] = [];
  exerciceSelectionne: string = '';

  isLoading: boolean = false;

  stats = {
    budgetTotal: 0,
    encaissements: 0,
    depenses: 0,
    reste: 0,
    totalDu: 0
  };

  operations: any[] = [];

  lastEncId: number = 0;
  lastDepId: number = 0;
  isThereMore: boolean = false;

  // ==========================================
  // VARIABLES DYAL L-MODAL (NOUVEAU)
  // ==========================================
  isAddModalOpen: boolean = false;
  isSubmitting: boolean = false;
  
  proprietairesActifs: any[] = [];
  clesRepartition: any[] = [];

  newMouvement: any = {
    type_mouvement: 'encaissement',
    title: '',
    amount: null,
    date: '',
    type_charges: 'previsionnel',
    sub_type_charges: 'planifié',
    owner_id: '',
    cle_repartition_id: ''
  };

  // ==========================================
  // 🟢 VARIABLES DYAL L-MODAL (MODIFICATION)
  // ==========================================
  isEditModalOpen: boolean = false;
  editMouvement: any = {};

  constructor(
    private cdr: ChangeDetectorRef,
    private budgetService: BudgetService,
    private exerciceService: ExerciceService,
    private coproprietaireService: CoproprietaireService, 
    private cleService: CleRepartitionService             
  ) {}

  ngOnInit() {
    this.proprieteId = localStorage.getItem('active_propriete_id') || 'SP-1775215295';
    this.chargerExercices();
  }

  // ==========================================
  // 1. CHARGEMENT DES DONNÉES
  // ==========================================
  chargerExercices() {
    this.exerciceService.getListe(this.proprieteId).subscribe({
      next: (res: any) => {
        if (res.success && res.data.length > 0) {
          this.exercices = res.data;
          this.exerciceSelectionne = this.exercices[0].se_identifier; 
          this.chargerDonnees(true); 
        }
      }
    });
  }

  chargerDonnees(reset: boolean = false) {
    if (!this.exerciceSelectionne) return;

    if (reset) {
      this.operations = [];
      this.lastEncId = 0;
      this.lastDepId = 0;
    }

    this.isLoading = true;
    const payload = {
      sp_identifier: this.proprieteId,
      exercise: this.exerciceSelectionne,
      type: this.activeTab,
      last_enc_id: this.lastEncId,
      last_dep_id: this.lastDepId
    };

    this.budgetService.chargerDonnees(payload).subscribe({
      next: (res: any) => {
        if (res.success) {
          const t = res.data.totaux;
          if (t) {
            this.stats.budgetTotal = t.budget || 0;
            this.stats.encaissements = t.total_encaissements || 0;
            this.stats.depenses = t.total_depenses || 0;
            this.stats.reste = this.stats.budgetTotal - this.stats.depenses;
            this.stats.totalDu = this.stats.budgetTotal - this.stats.encaissements;
          } else {
            this.stats = { budgetTotal: 0, encaissements: 0, depenses: 0, reste: 0, totalDu: 0 };
          }

          this.operations = [...this.operations, ...res.data.operations];
          this.lastEncId = res.data.pagination.last_enc_id;
          this.lastDepId = res.data.pagination.last_dep_id;
          this.isThereMore = res.data.pagination.is_there_more;
        }
        this.isLoading = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        console.error('❌ [chargerDonnees] Erreur:', err);
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
  }

  onExerciceChange() {
    this.chargerDonnees(true);
  }

  setTab(tab: 'previsionnel' | 'travaux') {
    this.activeTab = tab;
    this.chargerDonnees(true); 
  }

  chargerPlus() {
    this.chargerDonnees(false); 
  }

  // ==========================================
  // 2. NOUVEAU MOUVEMENT
  // ==========================================
  nouveauMouvement() {
    this.newMouvement = {
      type_mouvement: 'encaissement',
      title: '',
      amount: null,
      date: new Date().toISOString().split('T')[0],
      type_charges: this.activeTab, 
      sub_type_charges: 'planifié',
      owner_id: '',
      cle_repartition_id: ''
    };
    this.isAddModalOpen = true;
    this.chargerProprietairesEtCles(); 
  }

  closeAddModal() {
    this.isAddModalOpen = false;
    this.cdr.detectChanges();
  }

  chargerProprietairesEtCles() {
    this.coproprietaireService.getListe(this.proprieteId, 'tous').subscribe((res: any) => {
      if (res.success) {
        this.proprietairesActifs = res.data.filter((p: any) => p.status === 'Actif');
      }
    });

    this.cleService.getListe(this.proprieteId).subscribe((res: any) => {
      if (res.success) {
        this.clesRepartition = res.data;
      }
    });
  }

  soumettreMouvement() {
    if (!this.newMouvement.title || !this.newMouvement.amount || !this.newMouvement.date) {
      alert("⚠️ Veuillez remplir tous les champs obligatoires (Libellé, Montant, Date).");
      return;
    }
    if (this.newMouvement.type_mouvement === 'encaissement' && !this.newMouvement.owner_id) {
      alert("⚠️ Veuillez sélectionner un propriétaire pour cet encaissement.");
      return;
    }
    if (this.newMouvement.type_mouvement === 'depense' && !this.newMouvement.cle_repartition_id) {
      alert("⚠️ Veuillez sélectionner une clé de répartition pour cette dépense.");
      return;
    }

    this.isSubmitting = true;

    const payload = {
      sp_identifier: this.proprieteId,
      se_identifier: this.exerciceSelectionne,
      title: this.newMouvement.title,
      amount: this.newMouvement.amount,
      date: this.newMouvement.date,
      type_charges: this.newMouvement.type_charges,
      sub_type_charges: this.newMouvement.sub_type_charges,
      owner_id: this.newMouvement.type_mouvement === 'encaissement' ? this.newMouvement.owner_id : null,
      cle_repartition_id: this.newMouvement.type_mouvement === 'depense' ? this.newMouvement.cle_repartition_id : null,
    };

    const requeteApi = this.newMouvement.type_mouvement === 'encaissement' 
      ? this.budgetService.ajouterEncaissement(payload)
      : this.budgetService.ajouterDepense(payload);

    requeteApi.subscribe({
      next: (res: any) => {
        if (res.success) {
          this.closeAddModal();
          this.chargerDonnees(true); 
        }
        this.isSubmitting = false;
        this.cdr.detectChanges();
      },
      error: (err: any) => {
        alert(err.error?.message || "Une erreur est survenue lors de l'enregistrement.");
        this.isSubmitting = false;
        this.cdr.detectChanges();
      }
    });
  }

  telechargerReleve() {
    console.log('📥 Clic sur Télécharger le relevé');
  }

  // ==========================================
  // 3. GESTION DES ACTIONS (Kebab Menu)
  // ==========================================
  toggleMenu(operationActuelle: any) {
    this.operations.forEach(op => {
      if (op !== operationActuelle) {
        op.showMenu = false;
      }
    });
    operationActuelle.showMenu = !operationActuelle.showMenu;
  }

  // ==========================================
  // 🟢 4. MODIFICATION
  // ==========================================
  modifierOperation(op: any) {
    op.showMenu = false; 
    
    // N-copiw l-data
    this.editMouvement = { ...op };
    this.editMouvement.montant_absolu = Math.abs(op.montant); 

    this.isEditModalOpen = true; 
  }

  closeEditModal() {
    this.isEditModalOpen = false;
    this.editMouvement = {};
  }

  soumettreModification() {
    if (!this.editMouvement.libelle || !this.editMouvement.montant_absolu || !this.editMouvement.date) {
      alert("⚠️ Veuillez remplir tous les champs obligatoires.");
      return;
    }

    this.isSubmitting = true;

    const payload = {
      sp_identifier: this.proprieteId,
      se_identifier: this.exerciceSelectionne,
      origin_id: this.editMouvement.origin_id,
      title: this.editMouvement.libelle,
      amount: this.editMouvement.montant_absolu,
      date: this.editMouvement.date,
      sub_type_charges: this.editMouvement.sub_type_charges
    };

    // F L-MOUSTA9BAL: Hna dir l'appel API dyal l-modification mlli t-creyiha f l-Backend
    /*
    const requeteApi = this.editMouvement.type === 'Encaissement' 
      ? this.budgetService.modifierEncaissement(payload)
      : this.budgetService.modifierDepense(payload);

    requeteApi.subscribe(...)
    */

    // Daba n-dirou simulation bima 9additi l-Backend
    setTimeout(() => {
      this.isSubmitting = false;
      this.closeEditModal();
      this.chargerDonnees(true);
    }, 800);
  }

  // ==========================================
  // 🟢 5. SUPPRESSION
  // ==========================================
 // ==========================================
  // 🟢 5. SUPPRESSION
  // ==========================================
  supprimerOperation(op: any) {
    op.showMenu = false; 
    
    if (confirm(`Êtes-vous sûr de vouloir supprimer l'opération "${op.libelle}" d'un montant de ${Math.abs(op.montant)} DH ?`)) {
      
      // 🟢 FIX HNA: Zedna ": any" bach TypeScript y-khellina n-zidou les IDs 3la khatrna
      const payload: any = {
        sp_identifier: this.proprieteId,
        se_identifier: this.exerciceSelectionne
      };

      if (op.type === 'Encaissement') {
        payload.sen_identifier = op.origin_id;
      } else {
        payload.sdep_identifier = op.origin_id;
      }

      // F L-MOUSTA9BAL: Appel API
      /*
      const requeteApi = op.type === 'Encaissement' 
        ? this.budgetService.supprimerEncaissement(payload)
        : this.budgetService.supprimerDepense(payload);

      requeteApi.subscribe({
        next: (res: any) => {
          if (res.success) this.chargerDonnees(true);
        },
        error: (err: any) => alert("Erreur lors de la suppression.")
      });
      */

      console.log('🗑️ Simulation suppression:', payload);
      // N-ms7ouha mn l-tableau temporairement bach t-bani lik l-animation f l-front
      this.operations = this.operations.filter(o => o.origin_id !== op.origin_id);
    }
  }
}
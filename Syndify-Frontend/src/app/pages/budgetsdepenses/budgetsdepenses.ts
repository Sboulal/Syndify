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
  residenceInfo = { nom: '...', adresse: '...' };

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
    // Angular kay-3eyet nichan, l-Backend kay-3ref l-propriété rasso!
    this.chargerExercices();
  }

  chargerExercices() {
    // 🔴 7iyedna proprieteId mn hna
    this.exerciceService.getListe().subscribe({
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
      exercise: this.exerciceSelectionne,
      type: this.activeTab,
      last_enc_id: this.lastEncId,
      last_dep_id: this.lastDepId
    };

    this.budgetService.chargerDonnees(payload).subscribe({
      next: (res: any) => {
        if (res.success) {
          // 🟢 FIX: Synchro l-Header
          if (res.residence) {
            this.residenceInfo = res.residence;
          }

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
    // 🟢 HADA HWA L-FIX LI KAN DAYR ERREUR! (7iyedna this.proprieteId)
    this.coproprietaireService.getListe('tous').subscribe((res: any) => {
      if (res.success) {
        this.proprietairesActifs = res.data.filter((p: any) => p.status === 'Actif');
      }
    });

    // 🔴 7iyedna proprieteId mn hna 7ta hwa
    this.cleService.getListe().subscribe((res: any) => {
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

    // 🔴 7iyedna propriete_id mn l-payload
    const payload = {
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
    console.log('📥 Demande de téléchargement du relevé PDF en cours...');
    
    if (!this.exerciceSelectionne) {
      alert("Veuillez sélectionner un exercice d'abord.");
      return;
    }

    const payload = {
      exercise: this.exerciceSelectionne,
      type: this.activeTab
    };

    this.budgetService.telechargerReleve(payload).subscribe({
      next: (blob: Blob) => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `Releve_${this.activeTab}_${this.exerciceSelectionne}.pdf`;
        document.body.appendChild(a);
        
        a.click(); 
        
        // 🟢 FIX HNA: N-tsennaw 2 tawani 3ad n-ms7ou l-Blob mn l-mémoire
        // Bash n-3tiw we9t l-Navigateur (Edge/Chrome) y-9ra l-PDF 3la khatrou
        setTimeout(() => {
            window.URL.revokeObjectURL(url);
            a.remove();
        }, 2000);
      },
      error: (err) => {
        console.error("❌ Erreur lors du téléchargement", err);
        alert("Une erreur est survenue lors du téléchargement du relevé.");
      }
    });
  }

  toggleMenu(operationActuelle: any) {
    this.operations.forEach(op => {
      if (op !== operationActuelle) {
        op.showMenu = false;
      }
    });
    operationActuelle.showMenu = !operationActuelle.showMenu;
  }

  modifierOperation(op: any) {
    op.showMenu = false; 
    
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

    // 🔴 7iyedna propriete_id
    const payload = {
      se_identifier: this.exerciceSelectionne,
      origin_id: this.editMouvement.origin_id,
      title: this.editMouvement.libelle,
      amount: this.editMouvement.montant_absolu,
      date: this.editMouvement.date,
      sub_type_charges: this.editMouvement.sub_type_charges
    };

    setTimeout(() => {
      this.isSubmitting = false;
      this.closeEditModal();
      this.chargerDonnees(true);
    }, 800);
  }

  supprimerOperation(op: any) {
    op.showMenu = false; 
    
    if (confirm(`Êtes-vous sûr de vouloir supprimer l'opération "${op.libelle}" d'un montant de ${Math.abs(op.montant)} DH ?`)) {
      
      // 🔴 7iyedna propriete_id
      const payload: any = {
        se_identifier: this.exerciceSelectionne
      };

      if (op.type === 'Encaissement') {
        payload.sen_identifier = op.origin_id;
      } else {
        payload.sdep_identifier = op.origin_id;
      }

      console.log('🗑️ Simulation suppression:', payload);
      this.operations = this.operations.filter(o => o.origin_id !== op.origin_id);
    }
  }
}
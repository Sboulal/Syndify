import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute } from '@angular/router'; 
import { PageHeader } from '../../components/page-header/page-header';
import { FormsModule } from '@angular/forms';
import { ExerciceService } from '../../services/exercice'; 
import { CleRepartitionService } from '../../services/cle-repartition'; 

@Component({
  selector: 'app-exercises',
  standalone: true,
  imports: [CommonModule, PageHeader, FormsModule],
  templateUrl: './exercises.html',
})
export class Exercises implements OnInit {
  
  proprieteId: string = ''; 
  isLoading: boolean = false;
  isAddModalOpen: boolean = false;
  isSubmitting: boolean = false;

  activeDropdown: string | null = null;
  isDeleteModalOpen: boolean = false;
  exerciceToDeleteId: string | null = null;

  exercices: any[] = []; 

  newExercice: any = {
    se_identifier: null, 
    sp_identifier: '', 
    start_date: '',
    end_date: '',
    periode: 'trimestre', // 🟢 Fix: smitha periode f HTML
    budget_previsionnel_total: 0,
    budget_travaux_total: 0,
    list_cles_previsionnel: [],
    list_cles_travaux: []
  };

  showNotification: boolean = false;
  notificationMessage: string = '';
  notificationType: 'success' | 'error' = 'success';
  notificationTimeout: any;

  constructor(
    private cdr: ChangeDetectorRef,
    private exerciceService: ExerciceService,
    private cleService: CleRepartitionService,
    private route: ActivatedRoute 
  ) {}

  ngOnInit() {
    this.recupererProprieteId();
  }

  recupererProprieteId() {
    this.proprieteId = localStorage.getItem('active_propriete_id') || 'SP-1775215295'; 
    console.log('🟢 [ngOnInit] Propriété active:', this.proprieteId);
    if (this.proprieteId) {
      this.chargerExercices(); 
    }
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

  chargerExercices() {
    this.isLoading = true;
    console.log('⏳ [chargerExercices] Récupération de la liste...');
    this.exerciceService.getListe(this.proprieteId).subscribe({
      next: (res: any) => {
        console.log('📦 [chargerExercices] Réponse:', res);
        if (res.success) {
          this.exercices = res.data;
        }
        this.isLoading = false;
        this.cdr.detectChanges();
      },
      error: (err: any) => {
        console.error('❌ [chargerExercices] Erreur:', err);
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
  }

  toggleDropdown(id: string, event: Event) {
    event.stopPropagation();
    this.activeDropdown = this.activeDropdown === id ? null : id;
    this.cdr.detectChanges();
  }

  closeDropdown() {
    this.activeDropdown = null;
    this.cdr.detectChanges();
  }

  // ==================== AJOUT / MODIFICATION ====================
  openAddModal() {
    this.closeDropdown();
    this.newExercice = {
      se_identifier: null,
      sp_identifier: this.proprieteId, 
      start_date: '',
      end_date: '',
      periode: 'trimestre',
      budget_previsionnel_total: 0,
      budget_travaux_total: 0,
      list_cles_previsionnel: [],
      list_cles_travaux: []
    };
    console.log('📝 [openAddModal] Formulaire vidé pour nouvel exercice.');
    this.isAddModalOpen = true;
    this.fetchClesRepartition();
  }

  // 🟢 FIX: openEditModal m-sowba bach t-jme3 l-totaux l-rasha
  openEditModal(ex: any) {
    this.closeDropdown(); 
    
    console.log("✏️ Exercice brut reçu de la base de données :", ex);

    // 1. N-9addou l-listes dyal les clés bach n-7esbou bihom l-totaux l-rasha
    const prevCles = ex.cles_previsionnel || ex.list_cles_previsionnel || ex.charges_previsionnelles || [];
    const travCles = ex.cles_travaux || ex.list_cles_travaux || ex.charges_travaux || [];

    // 2. N-7esbou l-Totaux b l-Calcul (Math.js sghir f Angular)
    let calculTotalPrev = 0;
    prevCles.forEach((item: any) => {
        calculTotalPrev += Number(item.budget || item.montant || item.amount || 0);
    });

    let calculTotalTrav = 0;
    travCles.forEach((item: any) => {
        calculTotalTrav += Number(item.budget || item.montant || item.amount || 0);
    });

    // 3. N-3emmrou l-Formulaire
    this.newExercice = {
      ...ex,
      start_date: ex.start_date ? new Date(ex.start_date).toISOString().split('T')[0] : '',
      end_date: ex.end_date ? new Date(ex.end_date).toISOString().split('T')[0] : '',
      
      periode: (ex.periode || ex.period || 'trimestre').toLowerCase(),

      // 🟢 HNA L-FIX: Kan-3tiwh l-Calcul lli drna l-fou9 ila l-Backend majab walo
      budget_previsionnel_total: Number(ex.budget_previsionnel_total || ex.budget_previsionnel || ex.total_previsionnel) || calculTotalPrev,
      budget_travaux_total: Number(ex.budget_travaux_total || ex.budget_travaux || ex.total_travaux) || calculTotalTrav,
      
      list_cles_previsionnel: [],
      list_cles_travaux: []
    };

    console.log("💰 Totaux calculés :", { Prev: this.newExercice.budget_previsionnel_total, Trav: this.newExercice.budget_travaux_total });

    this.isAddModalOpen = true; 
    this.fetchClesRepartition(ex); 
  }

  closeAddModal() {
    this.isAddModalOpen = false;
    this.cdr.detectChanges();
  }

  // 🟢 FIX: Jbed l-flouss dyal les clés mn l'exercice a editer
  fetchClesRepartition(exerciceAEditer: any = null) {
    console.log('⏳ [fetchClesRepartition] Récupération des clés Master...');
    this.cleService.getListe(this.proprieteId).subscribe({
      next: (res: any) => {
        if (res.success) {
          const keysFromApi = res.data;
          console.log('🔑 [fetchClesRepartition] Clés trouvées:', keysFromApi);
          
          // 1. Les clés Prévisionnelles
          this.newExercice.list_cles_previsionnel = keysFromApi.map((k: any) => {
            let montantSauvegardé = 0;
            if (exerciceAEditer) {
              // N-9elbou 3la s-smiya s7i7a dyal l-array mn l-backend
              const clesPrev = exerciceAEditer.cles_previsionnel || exerciceAEditer.list_cles_previsionnel || exerciceAEditer.charges_previsionnelles || [];
              const findKey = clesPrev.find((item: any) => 
                (item.cle_repartition_id || item.cle_id || item.id) === k.id
              );
              if (findKey) montantSauvegardé = findKey.budget || findKey.montant || findKey.amount || 0;
            }
            return { cle_id: k.id, nom: k.nom, montant: montantSauvegardé };
          });
          
          // 2. Les clés Travaux
          this.newExercice.list_cles_travaux = keysFromApi.map((k: any) => {
            let montantSauvegardé = 0;
            if (exerciceAEditer) {
              const clesTrav = exerciceAEditer.cles_travaux || exerciceAEditer.list_cles_travaux || exerciceAEditer.charges_travaux || [];
              const findKey = clesTrav.find((item: any) => 
                 (item.cle_repartition_id || item.cle_id || item.id) === k.id
              );
              if (findKey) montantSauvegardé = findKey.budget || findKey.montant || findKey.amount || 0;
            }
            return { cle_id: k.id, nom: k.nom, montant: montantSauvegardé };
          });
          
          console.log('📊 [fetchClesRepartition] Listes finales avec montants:', {
            previsionnel: this.newExercice.list_cles_previsionnel,
            travaux: this.newExercice.list_cles_travaux
          });

          this.cdr.detectChanges();
        }
      },
      error: (err) => console.error('❌ [fetchClesRepartition] Erreur API Clés:', err)
    });
  }

  calculateRemaining(type: 'previsionnel' | 'travaux'): number {
    const total = type === 'previsionnel' ? this.newExercice.budget_previsionnel_total : this.newExercice.budget_travaux_total;
    const list = type === 'previsionnel' ? this.newExercice.list_cles_previsionnel : this.newExercice.list_cles_travaux;
    const sum = list.reduce((acc: number, item: any) => acc + (Number(item.montant) || 0), 0);
    return Number((total - sum).toFixed(2));
  }

  submitExercice() {
    console.log('🚀 [submitExercice] Déclenchement...');
    if (this.calculateRemaining('previsionnel') !== 0 || this.calculateRemaining('travaux') !== 0) {
      console.warn('⚠️ [submitExercice] Budget non équilibré.');
      this.showToast("Le reste à répartir doit être égal à 0.", 'error');
      return;
    }

    this.isSubmitting = true;
    console.log('📡 [submitExercice] Payload:', this.newExercice);
    
    const request = this.newExercice.se_identifier 
      ? this.exerciceService.modifier(this.newExercice)
      : this.exerciceService.ajouter(this.newExercice);

    request.subscribe({
      next: (res: any) => {
        console.log('✅ [submitExercice] Succès:', res);
        this.showToast(res.message || "Opération réussie.", 'success');
        this.closeAddModal();
        this.chargerExercices(); 
        this.isSubmitting = false;
        this.cdr.detectChanges();
      },
      error: (err: any) => {
        console.error('❌ [submitExercice] Erreur API:', err);
        this.showToast(err.error?.message || "Erreur lors de l'enregistrement.", 'error');
        this.isSubmitting = false;
        this.cdr.detectChanges();
      }
    });
  }

  supprimerExercice(id: string) {
    this.closeDropdown();
    this.exerciceToDeleteId = id;
    this.isDeleteModalOpen = true;
    console.log('🗑️ [supprimerExercice] Clic suppression pour:', id);
    this.cdr.detectChanges();
  }

  closeDeleteModal() {
    this.isDeleteModalOpen = false;
    this.exerciceToDeleteId = null;
    this.cdr.detectChanges();
  }

  confirmerSuppression() {
    if (!this.exerciceToDeleteId) return;
    console.log('🚀 [confirmerSuppression] Envoi suppression ID:', this.exerciceToDeleteId);
    this.exerciceService.supprimer(this.proprieteId, this.exerciceToDeleteId).subscribe({
      next: (res: any) => {
        console.log('✅ [confirmerSuppression] Réponse:', res);
        this.showToast("Exercice supprimé.", 'success');
        this.closeDeleteModal();
        this.chargerExercices();
      },
      error: (err) => console.error('❌ [confirmerSuppression] Erreur API:', err)
    });
  }

  cloturerExercice(id: string) {
    this.closeDropdown();
    console.log('🔐 [cloturerExercice] Appel clôture pour:', id);
  }
}
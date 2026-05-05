import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule, Location } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { PageHeader } from '../../components/page-header/page-header';
import { ClotureService } from '../../services/cloture'; // 🟢 Rdi l-bal l-chemin

@Component({
  selector: 'app-cloture',
  standalone: true,
  imports: [CommonModule, FormsModule, PageHeader],
  templateUrl: './cloture.html'
})
export class Cloture implements OnInit {
  
  activeTab: 'previsionnel' | 'travaux' = 'previsionnel';
  isLoading = true;
  isSaving = false;

  spIdentifier: string = '';
  seIdentifier: string = '';

  // Formulaire dyal les actions (Choices)
  actionsForm = {
    prev: { reste_choice: '', du_choice: '', send_reminders: false, date_limit_reminders: '' },
    trav: { reste_choice: '', du_choice: '', send_reminders: false, date_limit_reminders: '' }
  };

  // Data vide, ghadi t-3mer mlli y-jawebna Laravel
  data: any = null;
  residenceInfo: any = { nom: 'Chargement...', adresse: '...' };

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private cdr: ChangeDetectorRef,
    private clotureService: ClotureService,
    private location: Location
  ) {}

  ngOnInit() {
    // 🟢 Jbed ID d-l'exercice mn l-URL (matalan: /cloture/EX-2026-...)
    this.seIdentifier = this.route.snapshot.paramMap.get('se_id') || 'EX-2026-TEST';
    
    // 🟢 Jbed ID d-Propriété mn localStorage (Awla 3la 7ssab kifash kheddama biha)
    this.spIdentifier = localStorage.getItem('propriete_id') || 'SP-87248712'; 

    this.chargerDonnees();
  }

  switchTab(tab: 'previsionnel' | 'travaux') {
    this.activeTab = tab;
  }

  chargerDonnees() {
    this.isLoading = true;
    this.clotureService.getClotureData(this.spIdentifier, this.seIdentifier).subscribe({
      next: (res: any) => {
        if (res.success) {
          const d = res.data;
          this.residenceInfo = res.residence;

          // 🟢 Kan-Mapiw (n-9addou) data d Laravel m3a l-HTML bash may-khsserch Design
          this.data = {
            previsionnel: {
              planifie: d.previsionnel.resume,
              cles: d.previsionnel.cles,
              exceptionnel: d.previsionnel.exceptionnel,
              appels_exceptionnels: d.previsionnel.exceptionnel.appels,
              grandTotal: {
                montant: d.grand_total.budget, // Laravel kay-ssmih budget, HTML kay-ssmih montant
                encaissements: d.grand_total.encaissements,
                depenses: d.grand_total.depenses,
                reste: d.grand_total.reste,
                du: d.grand_total.du
              }
            },
            travaux: {
              planifie: d.travaux.resume,
              cles: [],
              exceptionnel: { budget: 0, encaissements: 0 },
              appels_exceptionnels: [],
              grandTotal: {
                montant: d.grand_total.budget,
                encaissements: d.grand_total.encaissements,
                depenses: d.grand_total.depenses,
                reste: d.grand_total.reste,
                du: d.grand_total.du
              }
            }
          };

          // 🟢 Ila kant l-Clôture déjà m-sauvegardya (Brouillon), n-rddou l-Formulaire kima kan
          if (d.cloture_saved_data) {
            const saved = d.cloture_saved_data;
            this.actionsForm.prev.reste_choice = saved.reste_choice_prev ? saved.reste_choice_prev.toString() : '';
            this.actionsForm.prev.du_choice = saved.du_choice_prev ? saved.du_choice_prev.toString() : '';
            this.actionsForm.prev.send_reminders = saved.send_reminders_prev === 1;
            
            this.actionsForm.trav.reste_choice = saved.reste_choice_trav ? saved.reste_choice_trav.toString() : '';
            this.actionsForm.trav.du_choice = saved.du_choice_trav ? saved.du_choice_trav.toString() : '';
            this.actionsForm.trav.send_reminders = saved.send_reminders_trav === 1;
          }
        }
        this.isLoading = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        console.error("Erreur API:", err);
        alert(err.error?.message || "Erreur lors du chargement des données.");
        this.isLoading = false;
      }
    });
  }

  // 🟢 6.2 Enregistrer la clôture (Brouillon)
  enregistrerCloture() {
    const payload = this.preparerPayload();
    this.isSaving = true;

    this.clotureService.enregistrerBrouillon(payload).subscribe({
      next: (res) => {
        if (res.success) {
          alert("Clôture enregistrée en brouillon avec succès !");
        }
        this.isSaving = false;
      },
      error: (err) => {
        alert(err.error?.message || "Erreur lors de la sauvegarde.");
        this.isSaving = false;
      }
    });
  }

   retourListe() {
    this.location.back(); 
  }
// 🟢 6.3 Finaliser la clôture
  finaliserCloture() {
    if(confirm("Êtes-vous sûr de vouloir finaliser cette clôture ? Cette action est irréversible et générera le rapport final.")) {
      const payload = this.preparerPayload();
      this.isSaving = true;

      this.clotureService.finaliserCloture(payload).subscribe({
        next: (res) => {
          if (res.success) {
            alert("Clôture finalisée avec succès ! Document généré.");
            // 🟢 Bdellna l-retour hna bash y-rje3 l-page Exercice
            this.router.navigate(['/exercice']); 
          }
          this.isSaving = false;
        },
        error: (err) => {
          alert(err.error?.message || "Erreur lors de la finalisation.");
          this.isSaving = false;
        }
      });
    }
  }

  // Fonction d'aide bach n-jm3ou d-data qbl ma n-ssiftoha l-Backend
  private preparerPayload() {
    return {
      sp_identifier: this.spIdentifier,
      se_identifier: this.seIdentifier,
      
      reste_choice_prev: this.actionsForm.prev.reste_choice ? parseInt(this.actionsForm.prev.reste_choice) : null,
      du_choice_prev: this.actionsForm.prev.du_choice ? parseInt(this.actionsForm.prev.du_choice) : null,
      send_reminders_prev: this.actionsForm.prev.send_reminders,
      date_limit_reminders_prev: this.actionsForm.prev.date_limit_reminders,

      reste_choice_trav: this.actionsForm.trav.reste_choice ? parseInt(this.actionsForm.trav.reste_choice) : null,
      du_choice_trav: this.actionsForm.trav.du_choice ? parseInt(this.actionsForm.trav.du_choice) : null,
      send_reminders_trav: this.actionsForm.trav.send_reminders,
      date_limit_reminders_trav: this.actionsForm.trav.date_limit_reminders,
    };
  }
}
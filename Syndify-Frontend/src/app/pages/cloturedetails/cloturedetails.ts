import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule, Location } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient, HttpHeaders } from '@angular/common/http'; // 🟢 ZEDNA HttpHeaders HNA
import { ActivatedRoute, Router } from '@angular/router';
import { PageHeader } from '../../components/page-header/page-header';

@Component({
  selector: 'app-cloture-details',
  standalone: true,
  imports: [CommonModule, FormsModule, PageHeader],
  templateUrl: './cloturedetails.html',
})
export class Cloturedetails implements OnInit {
  
  se_identifier: string = '';
  isLoading: boolean = false;
  isSaving: boolean = false;
  residenceInfo: any = { nom: "Clôture de l'exercice", adresse: "Vérifiez les comptes et prenez les décisions finales." };
  
  activeTab: 'previsionnel' | 'travaux' = 'previsionnel';
  
  data: any = null;
  
  formChoices: any = {
    reste_choice_prev: null,
    du_choice_prev: null,
    send_reminders_prev: false,
    reste_choice_trav: null,
    du_choice_trav: null,
    send_reminders_trav: false
  };

  private baseUrl = 'http://51.178.87.234:8085/api';

  constructor(
    private http: HttpClient,
    private route: ActivatedRoute,
    private router: Router,
    private location: Location,
    private cdr: ChangeDetectorRef
  ) {}

ngOnInit() {
    this.route.paramMap.subscribe(params => {
      const id = params.get('id');
      if (id) {
        this.se_identifier = id;
        console.log("🟢 ID Exercice récupéré mn l-URL:", this.se_identifier);
        this.chargerCloture();
      } else {
        // 🟢 FIX HNA: Ila l-ID ma-dazsh, n-rjj3ou l-User l-liste awtomatiki
        console.error("❌ L-ID khawi f l-URL! Redirection vers la liste...");
        this.router.navigate(['/clotures']); // Bddliha b l-Chemin dyal l-Liste dyalk
      }
    });
  }

  retour() {
    this.location.back();
  }

  // 🟢 Fonction bach t-jib l-Headers fihom l-User ID (B7al Postman)
  private getHeaders() {
    const userId = localStorage.getItem('user_id') || '1';
    return new HttpHeaders({
      'X-User-Id': userId
    });
  }

chargerCloture() {
    this.isLoading = true;
    
    this.http.post(`${this.baseUrl}/clotures/charger`, { se_identifier: this.se_identifier }, { headers: this.getHeaders() }).subscribe({
      next: (res: any) => {
        if (res.success) {
          this.data = res.data;
          
          // 🟢 FIX: 7iydna l-Commentaire hna!
          if (res.residence) {
             this.residenceInfo = res.residence; 
          }

          if (this.data.cloture_saved_data) {
            const saved = this.data.cloture_saved_data;
            this.formChoices.reste_choice_prev = saved.reste_choice_prev;
            this.formChoices.du_choice_prev = saved.du_choice_prev;
            this.formChoices.send_reminders_prev = saved.send_reminders_prev;
            this.formChoices.reste_choice_trav = saved.reste_choice_trav;
            this.formChoices.du_choice_trav = saved.du_choice_trav;
            this.formChoices.send_reminders_trav = saved.send_reminders_trav;
          }
        }
        this.isLoading = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        console.error("❌ Erreur de chargement", err);
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
  }

  enregistrerBrouillon() {
    this.isSaving = true;
    const payload = {
      se_identifier: this.se_identifier,
      ...this.formChoices
    };

    // 🟢 Zedna { headers: this.getHeaders() }
    this.http.post(`${this.baseUrl}/clotures/enregistrer`, payload, { headers: this.getHeaders() }).subscribe({
      next: (res: any) => {
        if (res.success) {
          alert("Brouillon sauvegardé avec succès !");
          this.chargerCloture();
        }
        this.isSaving = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        alert("Erreur: " + (err.error?.message || "Impossible de sauvegarder."));
        this.isSaving = false;
        this.cdr.detectChanges();
      }
    });
  }

  finaliserCloture() {
    if (!confirm("Attention, la finalisation est irréversible. L'exercice sera clos définitivement. Voulez-vous continuer ?")) {
      return;
    }

    this.isSaving = true;

    // 🟢 1. N-ssauvegardiw l-Khtiyarat (Brouillon) luwel awtomatiki
    const payloadBrouillon = {
      se_identifier: this.se_identifier,
      ...this.formChoices
    };

    this.http.post(`${this.baseUrl}/clotures/enregistrer`, payloadBrouillon, { headers: this.getHeaders() }).subscribe({
      next: (resBrouillon: any) => {
        if (resBrouillon.success) {
          
          // 🟢 2. Mlli kay-douz l-Brouillon, 3ad n-3eytou l-Finaliser bash y-sayeb l-PDF
          this.http.post(`${this.baseUrl}/clotures/finaliser`, { se_identifier: this.se_identifier }, { headers: this.getHeaders() }).subscribe({
            next: (resFinal: any) => {
              if (resFinal.success) {
                alert("Exercice clos avec succès ! Document généré.");
                this.chargerCloture(); 
              }
              this.isSaving = false;
              this.cdr.detectChanges();
            },
            error: (errFinal) => {
              alert("Erreur: " + (errFinal.error?.message || "Erreur lors de la finalisation."));
              this.isSaving = false;
              this.cdr.detectChanges();
            }
          });

        }
      },
      error: (errBrouillon) => {
        alert("Erreur: " + (errBrouillon.error?.message || "Impossible de sauvegarder les choix."));
        this.isSaving = false;
        this.cdr.detectChanges();
      }
    });
  }
}
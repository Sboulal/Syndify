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

  private baseUrl = 'http://nomade-cloud.com:8085/api';

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
        console.log("🟢 ID Exercice récupéré mn l-URL:", this.se_identifier); // 🟢 CHECK F CONSOLE
        
        this.chargerCloture();
   
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
    
    // 🟢 Payload m3a headers l-id dyal l-user
    this.http.post(`${this.baseUrl}/clotures/charger`, { se_identifier: this.se_identifier }, { headers: this.getHeaders() }).subscribe({
      next: (res: any) => {
        if (res.success) {
          this.data = res.data;
          
          // 🟢 FIX: Synchro l-Header m3a d-data dyal l-Backend
          // Khass ykoun l-Backend dyal ClotureController@charger kay-rjja3 'residence'
          if (res.residence) {
            // Ila konti saybti l-backend b7al les pages akhrin
            // this.residenceInfo = res.residence; 
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
    
    // 🟢 Zedna { headers: this.getHeaders() }
    this.http.post(`${this.baseUrl}/clotures/finaliser`, { se_identifier: this.se_identifier }, { headers: this.getHeaders() }).subscribe({
      next: (res: any) => {
        if (res.success) {
          alert("Exercice clos avec succès ! Document généré.");
          this.chargerCloture(); 
        }
        this.isSaving = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        alert("Erreur: " + (err.error?.message || "Veuillez d'abord enregistrer le brouillon."));
        this.isSaving = false;
        this.cdr.detectChanges();
      }
    });
  }
}
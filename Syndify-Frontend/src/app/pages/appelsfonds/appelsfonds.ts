import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { PageHeader } from '../../components/page-header/page-header';
import { HttpClient } from '@angular/common/http';
import { ActivatedRoute, Router } from '@angular/router';

@Component({
  selector: 'app-appelsfonds',
  standalone: true,
  imports: [CommonModule, PageHeader],
  templateUrl: './appelsfonds.html',
})
export class Appelsfonds implements OnInit {

  // 🔴 7iyedna proprieteId
  activeTab: 'previsionnel' | 'travaux' = 'previsionnel';
  
  appelsDeFonds: any[] = [];
  appelsPlanifies: any[] = [];
  appelsExceptionnels: any[] = [];
residenceInfo = { nom: '...', adresse: '...' };
  isModalOpen = false;
modalType: 'planifie' | 'exceptionnel' = 'planifie';
clesRepartition: any[] = [];
newExceptionnel = { title: '', amount: 0, due_date: '', cle_id: '' };

  totalMontant: number = 0;
  isLoading: boolean = false;

  exerciceInfos : any = null; 

  // 🟢 IP Unifiée
  private baseUrl = 'http://nomade-cloud.com:8085/api';

  constructor(
    private http: HttpClient,
    private cdr: ChangeDetectorRef,
    private router: Router
  ) {}

  ngOnInit() {
    // 🟢 Angular kay-3eyet nichan
    this.chargerListeAppels();
  }

  switchTab(tab: 'previsionnel' | 'travaux') {
    this.activeTab = tab;
    this.chargerListeAppels();
  }

chargerListeAppels() {
    this.isLoading = true;
    const apiUrl = `${this.baseUrl}/appels-fonds/liste`;
    
    const payload = {
        type_charge: this.activeTab
    };

    this.http.post(apiUrl, payload).subscribe({
      next: (res: any) => {
        if (res.success) {
          // 🟢 FIX: Synchro s-smiya mn l-Backend
          if (res.residence) {
            this.residenceInfo = res.residence;
          }

          this.appelsDeFonds = res.data;
          this.appelsPlanifies = this.appelsDeFonds.filter(a => a.sub_type === 'planifie');
          this.appelsExceptionnels = this.appelsDeFonds.filter(a => a.sub_type === 'exceptionnel');

          if (res.exercice) {
            this.exerciceInfos = {
              annee: new Date(res.exercice.start_date).getFullYear(),
              dateDebut: this.formaterDate(res.exercice.start_date),
              dateFin: this.formaterDate(res.exercice.end_date),
              montant_total: res.exercice.budget_previsionnel_total // Awla l-budget s7i7
            };
            this.totalMontant = res.exercice.budget_previsionnel_total || 0;
          }
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
  
  // 🟢 3. Fonction sghira bach t-9add chkel dyal d-date b l-Francais
  formaterDate(dateString: string): string {
    if (!dateString) return '';
    const options: Intl.DateTimeFormatOptions = { day: '2-digit', month: 'long', year: 'numeric' };
    return new Date(dateString).toLocaleDateString('fr-FR', options);
  }

  allerVersDetails(af_identifier: string) {
    this.router.navigate(['/financement/appels-fonds/details', af_identifier]);
  }

  genererAppel(appel: any) {
    if (confirm("Voulez-vous vraiment générer les documents pour cet appel de fonds ?")) {
      this.isLoading = true;
      
      // 🔴 7iyedna propriete_id mn l-payload
      const payload = {
          se_identifier: appel.se_identifier, 
          af_identifier: appel.af_identifier
      };
      
      this.http.post(`${this.baseUrl}/appels-fonds/generer`, payload).subscribe({
          next: (res: any) => { if(res.success) this.chargerListeAppels(); },
          error: (err) => {
              alert(err.error?.message || "Erreur de génération");
              this.isLoading = false;
              this.cdr.detectChanges();
          }
      });
    }
  }

  envoyerAppel(appel: any) {
    if (confirm("Êtes-vous sûr de vouloir envoyer cet appel de fonds ?")) {
      this.isLoading = true;
      this.http.post(`${this.baseUrl}/appels-fonds/envoyer`, { af_identifier: appel.af_identifier }).subscribe({
          next: (res: any) => { if(res.success) this.chargerListeAppels(); },
          error: (err) => {
              alert(err.error?.message || "Erreur d'envoi");
              this.isLoading = false;
              this.cdr.detectChanges();
          }
      });
    }
  }
}
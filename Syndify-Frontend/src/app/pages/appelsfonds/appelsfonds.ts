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

  proprieteId: string = '';
  activeTab: 'previsionnel' | 'travaux' = 'previsionnel';
  
  appelsDeFonds: any[] = [];
  
  // 🟢 ZEDNA HAD J-JOUJ BACH N-FER9OU L-DATA
  appelsPlanifies: any[] = [];
  appelsExceptionnels: any[] = [];

  totalMontant: number = 0;
  isLoading: boolean = false;

  exerciceInfos = {
    annee: 2023,
    dateDebut: '01 Janvier 2023',
    dateFin: '31 Décembre 2023'
  };

  constructor(
    private http: HttpClient,
    private cdr: ChangeDetectorRef,
    private router: Router
  ) {}

  ngOnInit() {
    this.proprieteId = localStorage.getItem('active_propriete_id') || 'SP-1775215295';
    this.chargerListeAppels();
  }

  switchTab(tab: 'previsionnel' | 'travaux') {
    this.activeTab = tab;
    this.chargerListeAppels();
  }

  chargerListeAppels() {
    this.isLoading = true;
    const apiUrl = `http://51.178.87.234:8085/api/appels-fonds/liste`;
    
    const payload = {
        sp_identifier: this.proprieteId,
        type_charge: this.activeTab
    };

    this.http.post(apiUrl, payload).subscribe({
      next: (res: any) => {
        if (res.success) {
          this.appelsDeFonds = res.data;
          
          // 🟢 KAN-FILTRIW L-APPELS (L-Planifiés bou7dhom, w l-Exceptionnels bou7dhom)
       this.appelsPlanifies = this.appelsDeFonds.filter(a => a.sub_type === 'planifie');
  this.appelsExceptionnels = this.appelsDeFonds.filter(a => a.sub_type === 'exceptionnel');

          this.totalMontant = this.appelsDeFonds.reduce((sum, appel) => sum + Number(appel.amount), 0);
        }
        this.isLoading = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        console.error('❌ Erreur Chargement Liste:', err);
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
  }

  allerVersDetails(af_identifier: string) {
    this.router.navigate(['/financement/appels-fonds/details', af_identifier]);
  }

  genererAppel(appel: any) {
    if (confirm("Voulez-vous vraiment générer les documents pour cet appel de fonds ?")) {
      this.isLoading = true;
      const payload = {
          sp_identifier: this.proprieteId,
          se_identifier: appel.se_identifier, 
          af_identifier: appel.af_identifier
      };
      this.http.post('http://51.178.87.234:8085/api/appels-fonds/generer', payload).subscribe({
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
      this.http.post('http://51.178.87.234:8085/api/appels-fonds/envoyer', { af_identifier: appel.af_identifier }).subscribe({
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
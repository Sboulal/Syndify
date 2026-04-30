import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule, Location } from '@angular/common'; 
import { PageHeader } from '../../components/page-header/page-header';
import { HttpClient } from '@angular/common/http';
import { ActivatedRoute, Router } from '@angular/router';

@Component({
  selector: 'app-appels-fonds-details',
  standalone: true,
  imports: [CommonModule, PageHeader],
  templateUrl: './appels-fonds-details.html',
})
export class AppelsFondsDetails implements OnInit {
  
  // 🔴 7iyedna proprieteId
  appelIdActuel: string = '';
  
  appelPrincipal: any = null;
  lignesProprietaires: any[] = [];
  isLoading: boolean = false;
  residenceInfo = { nom: '...', adresse: '...' };

  // 🟢 IP Unifiée
  private baseUrl = 'http://nomade-cloud.com:8085/api';

  constructor(
    private http: HttpClient,
    private cdr: ChangeDetectorRef,
    private route: ActivatedRoute,
    private router: Router,
    private location: Location 
  ) {}

  ngOnInit() {
    // 🔴 7iyedna l-appel dyal localStorage hna
    this.route.paramMap.subscribe(params => {
        const id = params.get('id');
        if (id) {
            this.appelIdActuel = id;
            this.chargerDetails();
        } else {
            this.retourListe();
        }
    });
  }

  retourListe() {
    this.location.back(); 
  }

  chargerDetails() {
    this.isLoading = true;
    const payload = { af_identifier: this.appelIdActuel };

    this.http.post(`${this.baseUrl}/appels-fonds/details`, payload).subscribe({
      next: (res: any) => {
        if(res.success) {
         if(res.data && res.data.residence) {
   this.residenceInfo = res.data.residence;
} else if(res.residence) {
   this.residenceInfo = res.residence;
} else if(res.data && res.data.propriete) {
   this.residenceInfo = res.data.propriete; 
}
          this.appelPrincipal = res.data.appel;
          
          this.lignesProprietaires = res.data.lignes.map((ligne: any) => {
            // 🟢 Jbed l-solde lli 3ad 9adinah f l-Backend
            const solde = Number(ligne.solde_actuel) || 0;
            const provision = Number(ligne.montant_du) || 0;
            
            return {
              // 🟢 Ila kant owner_name khawya, a-ffiche l-Email. Ila mchaw b-jouj, kteb 'Propriétaire Inconnu'
              coproprietaire: ligne.owner_name || ligne.email || 'Propriétaire Inconnu', 
              soldeAvant: solde,
              provision: provision,
              soldeApres: solde - provision
            };
          });
        }
        this.isLoading = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        console.error('❌ Erreur Détails:', err);
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
  }
  // ==========================================
  // GÉNÉRER & ENVOYER DEPUIS LES DÉTAILS
  // ==========================================
  genererAppel() {
    if (!this.appelPrincipal || this.appelPrincipal.is_generated) return;
    
    if (confirm("Voulez-vous générer les documents pour cet appel ?")) {
      this.isLoading = true;
      
      // 🔴 7iyedna propriete_id mn l-payload
      const payload = { 
          se_identifier: this.appelPrincipal.se_identifier, 
          af_identifier: this.appelIdActuel 
      };
      
      this.http.post(`${this.baseUrl}/appels-fonds/generer`, payload).subscribe({
          next: (res: any) => {
              if(res.success) this.chargerDetails(); 
          },
          error: (err) => {
              alert(err.error?.message || "Erreur de génération");
              this.isLoading = false;
              this.cdr.detectChanges();
          }
      });
    }
  }

  envoyerAppel() {
    if (!this.appelPrincipal || !this.appelPrincipal.is_generated || this.appelPrincipal.is_sent) return;
    
    if (confirm("Voulez-vous envoyer cet appel aux copropriétaires ?")) {
      this.isLoading = true;
      this.http.post(`${this.baseUrl}/appels-fonds/envoyer`, { af_identifier: this.appelIdActuel }).subscribe({
          next: (res: any) => {
              if(res.success) this.chargerDetails(); 
          },
          error: (err) => {
              alert(err.error?.message || "Erreur d'envoi");
              this.isLoading = false;
              this.cdr.detectChanges();
          }
      });
    }
  }
}
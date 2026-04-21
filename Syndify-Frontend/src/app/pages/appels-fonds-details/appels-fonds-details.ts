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
  
  proprieteId: string = '';
  appelIdActuel: string = '';
  
  appelPrincipal: any = null;
  lignesProprietaires: any[] = [];
  isLoading: boolean = false;

  constructor(
    private http: HttpClient,
    private cdr: ChangeDetectorRef,
    private route: ActivatedRoute,
    private router: Router,
    private location: Location // 🟢 Bach n-zido bouton "Retour"
  ) {}

  ngOnInit() {
    this.proprieteId = localStorage.getItem('active_propriete_id') || 'SP-1775215295';
    
    // N-jbdou l-ID mn l-URL (/details/:id)
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
    this.location.back(); // Kat-rje3 l-page lli 9bel (La liste)
  }

  // ==========================================
  // CHARGER LES DÉTAILS DE L'APPEL (API)
  // ==========================================
  chargerDetails() {
    this.isLoading = true;
    const payload = { af_identifier: this.appelIdActuel };

    this.http.post('http://51.178.87.234:8085/api/appels-fonds/details', payload).subscribe({
        next: (res: any) => {
          if(res.success) {
            this.appelPrincipal = res.data.appel;
            
            // N-9addo l-Tableau dyal l-Mollak
            this.lignesProprietaires = res.data.lignes.map((ligne: any) => {
              const soldeAvant = this.appelPrincipal.is_sent ? (Number(ligne.solde_avant) || 0) : (Number(ligne.solde_actuel) || 0);
              const provision = Number(ligne.montant_du) || 0;
              
              return {
                coproprietaire: ligne.full_name || 'Sans Nom',
                soldeAvant: soldeAvant,
                provision: provision,
                soldeApres: soldeAvant - provision
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
      const payload = { 
          sp_identifier: this.proprieteId, 
          se_identifier: this.appelPrincipal.se_identifier, 
          af_identifier: this.appelIdActuel 
      };
      
      this.http.post('http://51.178.87.234:8085/api/appels-fonds/generer', payload).subscribe({
          next: (res: any) => {
              if(res.success) this.chargerDetails(); // Actualiser l-page
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
      this.http.post('http://51.178.87.234:8085/api/appels-fonds/envoyer', { af_identifier: this.appelIdActuel }).subscribe({
          next: (res: any) => {
              if(res.success) this.chargerDetails(); // Actualiser l-page
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
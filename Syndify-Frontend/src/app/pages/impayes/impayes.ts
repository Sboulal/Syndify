import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { PageHeader } from '../../components/page-header/page-header';

@Component({
  selector: 'app-impayes',
  standalone: true,
  imports: [CommonModule, PageHeader], 
  templateUrl: './impayes.html',
})
export class Impayes implements OnInit {
  isLoading = false;
  isSending: string | null = null; 
  
  impayesList: any[] = [];
  totalImpayes: number = 0;
  
  // 🟢 FIX: Zidna residenceInfo hna
  residenceInfo = { nom: 'Chargement...', adresse: '...' };

  constructor(
    private http: HttpClient,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit() {
    this.chargerImpayes();
  }

  chargerImpayes() {
    this.isLoading = true;
    const proprieteId = localStorage.getItem('active_propriete_id') || 'SP-87248712';

    this.http.post('http://nomade-cloud.com:8085/api/impayes/liste', { propriete_id: proprieteId })
      .subscribe({
        next: (res: any) => {
          if (res.success) {
            this.impayesList = res.data;
            this.totalImpayes = res.total_impayes;
            
            // 🟢 FIX: Ila l-Backend sift l-m3loumat dyal la résidence, kan-stokiwha
            if (res.residence) {
              this.residenceInfo = res.residence;
            } else {
              // Fallback ila l-backend baqi masiftch la résidence
              this.residenceInfo = { nom: 'Résidence', adresse: 'Adresse non définie' };
            }
          }
          this.isLoading = false;
          this.cdr.detectChanges();
        },
        error: (err) => {
          console.error("❌ Erreur chargement impayés:", err);
          this.isLoading = false;
          this.cdr.detectChanges();
        }
      });
  }

  relancer(su_identifier: string) {
    if (!confirm('Voulez-vous vraiment générer et envoyer un rappel à ce copropriétaire ?')) return;

    this.isSending = su_identifier;
    const proprieteId = localStorage.getItem('active_propriete_id') || 'SP-87248712';

    this.http.post('http://nomade-cloud.com:8085/api/impayes/envoyer-rappel', {
        propriete_id: proprieteId,
        su_identifier: su_identifier
    }).subscribe({
        next: (res: any) => {
            if (res.success) {
                alert('✅ Rappel envoyé avec succès !');
            } else {
                alert('❌ Erreur: ' + res.message);
            }
            this.isSending = null;
            this.cdr.detectChanges();
        },
        error: (err) => {
            console.error("❌ Erreur envoi rappel:", err);
            alert('❌ Une erreur est survenue lors de l\'envoi.');
            this.isSending = null;
            this.cdr.detectChanges();
        }
    });
  }

  formatId(id: any): string {
      if (!id) return '';
      const visualId = Number(id) + 845752;
      return visualId.toString().padStart(8, '0');
  }
}
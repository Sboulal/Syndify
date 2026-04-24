import { Component, OnInit, ChangeDetectorRef } from '@angular/core'; // 🟢 Zid ChangeDetectorRef hna
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { RouterModule } from '@angular/router';
import { PageHeader } from '../../components/page-header/page-header';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule, RouterModule, PageHeader],
  templateUrl: './dashboard.html',
})
export class Dashboard implements OnInit {
  isProfileDropdownOpen = false;
  isLoading = false;

  residence = { nom: '...', adresse: '...', exercice: '...', periode: '...', gerant: '...', role: '...' };
  budget = { totalAnnee: '0', depenses: '0', pourcentage: 0 };
  trimestres: any[] = [];
  soldes: any[] = [];
  totalDu: string = '0';
  
  // 🟢 Injecti cdr f l-constructor
  constructor(
    private http: HttpClient,
    private cdr: ChangeDetectorRef 
  ) {}

  ngOnInit() {
    this.chargerDashboard();
  }

  chargerDashboard() {
    this.isLoading = true;
    const proprieteId = localStorage.getItem('active_propriete_id') || 'SP-1775215295';
    const userId = localStorage.getItem('user_id') || '1';
    const userRole = localStorage.getItem('user_role') || 'syndic';

    const payload = {
      propriete_id: proprieteId,
      user_id: userId,
      role: userRole
    };

    this.http.post('http://nomade-cloud.com:8085/api/dashboard/data', payload).subscribe({
      next: (res: any) => {
        if (res.success) {
          this.residence = res.data.residence;
          this.budget = res.data.budget;
          this.trimestres = res.data.trimestres;
          this.soldes = res.data.soldes;
          this.totalDu = res.data.totalDu;
        }
        this.isLoading = false;
        // 🟢 GOUL L-ANGULAR Y-DIR REFRESH L-HTML DABA!
        this.cdr.detectChanges(); 
      },
      error: (err) => {
        console.error("❌ Erreur Dashboard:", err);
        this.isLoading = false;
        // 🟢 7ta f l-erreur, khassna n-tfiw l-loading
        this.cdr.detectChanges(); 
      }
    });
  }
}
import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { RouterModule } from '@angular/router';
import { PageHeader } from '../../components/page-header/page-header'; // 🟢 ZEDNA HAD L-IMPORT

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule, RouterModule, PageHeader], // 🟢 ZIDIH HNAYA
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
  
  constructor(private http: HttpClient) {}

  ngOnInit() {
    this.chargerDashboard();
  }

  chargerDashboard() {
    this.isLoading = true;
    const proprieteId = localStorage.getItem('active_propriete_id') || 'SP-1775215295';
    const userId = localStorage.getItem('user_id') || '1';
    const userRole = localStorage.getItem('user_role') || 'syndic';

    const payload = {
      sp_identifier: proprieteId,
      user_id: userId,
      role: userRole
    };

    this.http.post('http://51.178.87.234:8085/api/dashboard/data', payload).subscribe({
      next: (res: any) => {
        if (res.success) {
          this.residence = res.data.residence;
          this.budget = res.data.budget;
          this.trimestres = res.data.trimestres;
          this.soldes = res.data.soldes;
          this.totalDu = res.data.totalDu;
        }
        this.isLoading = false;
      },
      error: (err) => {
        console.error("❌ Erreur Dashboard:", err);
        this.isLoading = false;
      }
    });
  }
}
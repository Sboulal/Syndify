import { Component, OnInit, ChangeDetectorRef } from '@angular/core'; // 🟢 Zidna ChangeDetectorRef
import { CommonModule } from '@angular/common';
import { RouterModule, Router } from '@angular/router';
import { HttpClient } from '@angular/common/http'; // 🟢 Zidna HttpClient

@Component({
  selector: 'app-main-layout',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './main-layout.html',
  styleUrls: ['./main-layout.css'],
})
export class MainLayout implements OnInit {
  isSidebarCollapsed: boolean = false;
  openMenus: { [key: string]: boolean } = {};
  activeItem: string = ''; 
  
  // 🟢 Variable jadida bash n-7etou fiha s-smiya d-Résidence
  residenceNom: string = 'Chargement...'; 

  constructor(
    private router: Router,
    private http: HttpClient, // 🟢 Injectinah
    private cdr: ChangeDetectorRef // 🟢 Injectinah bash y-refreshi l-Vue
  ) {}

  ngOnInit() {
    this.checkActiveRoute();
    this.chargerInfosResidence(); // 🟢 N-3eytou l-fonction mlli kay-t7el l-Layout
  }

  // 🟢 FONCTION JDIDA BASH T-JIB S-SMIYA D-RESIDENCE
  chargerInfosResidence() {
    const proprieteId = localStorage.getItem('active_propriete_id') || 'SP-1775215295';
    const userId = localStorage.getItem('user_id') || '1';
    const userRole = localStorage.getItem('user_role') || 'syndic';

    const payload = {
      propriete_id: proprieteId,
      user_id: userId,
      role: userRole
    };

    // Kan-3eytou l-nfs l-API dyal Dashboard bash n-jbdou s-smiya
    this.http.post('http://nomade-cloud.com:8085/api/dashboard/data', payload).subscribe({
      next: (res: any) => {
        if (res.success && res.data && res.data.residence) {
          this.residenceNom = res.data.residence.nom; // 🟢 Cheddina s-Smiya
          
          // Sauvegardawha f LocalStorage bash les autres pages y-9drou y-khedmou biha b-zreba (Optionnel)
          localStorage.setItem('residence_nom', this.residenceNom); 
        } else {
          this.residenceNom = 'Ma copropriété';
        }
        this.cdr.detectChanges(); 
      },
      error: (err) => {
        console.error("❌ Erreur chargement infos résidence:", err);
        // Ila w9e3 erreur n-9raw mn l-localstorage awla n-dirou smiya par defaut
        this.residenceNom = localStorage.getItem('residence_nom') || 'Ma copropriété';
        this.cdr.detectChanges(); 
      }
    });
  }

  checkActiveRoute() {
    const currentUrl = this.router.url;

    if (currentUrl.includes('/dashboard')) {
      this.activeItem = 'dashboard';
      this.openMenus = {}; 
    } 
    else if (currentUrl.includes('/gestion-lots') || currentUrl.includes('/liste-coproprietes') || currentUrl.includes('/cles-de-repartition') || currentUrl.includes('/impayes')) {
      if (currentUrl.includes('/gestion-lots')) this.activeItem = 'gestion-lots';
      if (currentUrl.includes('/liste-coproprietes')) this.activeItem = 'liste-copros';
      if (currentUrl.includes('/cles-de-repartition')) this.activeItem = 'cles';
      if (currentUrl.includes('/impayes')) this.activeItem = 'impayes';
      this.openMenus['copropriete'] = true; 
    }
    else if (currentUrl.includes('/budgets-depenses') || currentUrl.includes('/appels-de-fonds') || currentUrl.includes('/simulation-budget')) {
      if (currentUrl.includes('/budgets-depenses')) this.activeItem = 'budgets-depenses';
      if (currentUrl.includes('/appels-de-fonds')) this.activeItem = 'appels-de-fonds';
      if (currentUrl.includes('/simulation-budget')) this.activeItem = 'simulation-budget';
      this.openMenus['financement'] = true; 
    }
    else if (currentUrl.includes('/exercice')  || currentUrl.includes('clotures')) {
      if (currentUrl.includes('/exercice')) this.activeItem = 'exercice';
      if (currentUrl.includes('exercices/cloture')) this.activeItem = 'cloture';
      this.openMenus['Exercice & clôtures'] = true; 
    }
     else if (currentUrl.includes('/documents')) {
    this.activeItem = 'documents';
    this.openMenus['documents'] = true;
  }
  }
 

  toggleSidebar() {
    this.isSidebarCollapsed = !this.isSidebarCollapsed;
  }

// 🟢 FIX: Zedna 'event' bash n-7bssou l-redirection
  toggleMenu(menu: string, event?: Event) {
    if (event) {
      event.preventDefault(); // 🛑 Kat-mne3 l-navigateur y-ddik l-chi blassa khra (b7al Dashboard)
    }
    
    const etaitOuvert = this.openMenus[menu];
    this.openMenus = {}; 
    if (!etaitOuvert) {
      this.openMenus[menu] = true; 
    }
  }

  setActive(item: string) {
    this.activeItem = item;
    if (item === 'dashboard') {
      this.openMenus = {}; 
    }
  }
}
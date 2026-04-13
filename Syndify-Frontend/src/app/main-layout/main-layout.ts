import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Router } from '@angular/router';

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

  constructor(private router: Router) {}

  ngOnInit() {
    const currentUrl = this.router.url;

    // 1. Tableau de bord
    if (currentUrl.includes('/dashboard')) {
      this.activeItem = 'dashboard';
      this.openMenus = {}; // Kan-seddo khtchi
    } 
    // 2. Menu: Ma copropriété
    else if (currentUrl.includes('/gestion-lots') || currentUrl.includes('/liste-coproprietes') || currentUrl.includes('/cles-de-repartition') || currentUrl.includes('/les-impayes')) {
      
      if (currentUrl.includes('/gestion-lots')) this.activeItem = 'gestion-lots';
      if (currentUrl.includes('/liste-coproprietes')) this.activeItem = 'liste-copros';
      if (currentUrl.includes('/cles-de-repartition')) this.activeItem = 'cles';
      if (currentUrl.includes('/les-impayes')) this.activeItem = 'impayes';

      this.openMenus['copropriete'] = true; 
    }
    // 3. Menu: Financement (Zdnaah Hna)
    else if (currentUrl.includes('/budgets-depenses') || currentUrl.includes('/appels-fonds') || currentUrl.includes('/simuler-budget')) {
      
      if (currentUrl.includes('/budgets-depenses')) this.activeItem = 'budgets-depenses';
      if (currentUrl.includes('/appels-fonds')) this.activeItem = 'appels-fonds';
      if (currentUrl.includes('/simuler-budget')) this.activeItem = 'simuler-budget';

      this.openMenus['financement'] = true; 
    }
  }

  toggleSidebar() {
    this.isSidebarCollapsed = !this.isSidebarCollapsed;
  }

  toggleMenu(menu: string) {
    const etaitOuvert = this.openMenus[menu];
    
    // Kantsedo ga3 les menus lekhrin (Accordon style)
    this.openMenus = {}; 
    
    // Ila makanx m7loul kan7louh
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
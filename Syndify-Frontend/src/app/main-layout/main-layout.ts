import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';

@Component({
  selector: 'app-main-layout',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './main-layout.html',
  styleUrls: ['./main-layout.css'],
})
export class MainLayout {
  // L'état dyal l'sidebar (Wach mjmou3a wla m7loula)
  isSidebarCollapsed: boolean = false;

  openMenus: Record<string, boolean> = {
    copropriete: true,
    financement: false,
    assemblees: false,
    communication: false
  };

  activeItem: string = 'gestion-lots'; 

  // La fonction li kat-toggli l'bouton l-7mer
  toggleSidebar() {
    this.isSidebarCollapsed = !this.isSidebarCollapsed;
  }

  toggleMenu(menuName: string) {
    // Ila kan l'menu mjmou3 w klickina 3la sous-menu, n-7ellouh houwa lowel
    if (this.isSidebarCollapsed) {
      this.isSidebarCollapsed = false;
      this.openMenus[menuName] = true;
      return;
    }
    this.openMenus[menuName] = !this.openMenus[menuName];
  }

  setActive(item: string) {
    this.activeItem = item;
  }
}
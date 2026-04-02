import { Routes } from '@angular/router';
import { Login } from './Authentification/login/login';
import { MainLayout } from './main-layout/main-layout';
import { guestGuard } from './core/guards/guest-guard';
import { authGuard } from './core/guards/auth-guard';

export const routes: Routes = [
  { path: 'login', component: Login, canActivate: [guestGuard] },
  { path: 'dashboard', component: MainLayout, canActivate: [authGuard] },

  { path: '', redirectTo: '/login', pathMatch: 'full' },
 // ==========================================
  // ROUTES PRIVÉES (Sécurisées b l'AuthGuard)
  // ==========================================
  {
    path: '', 
    component: MainLayout, 
    canActivate: [authGuard], 
    children: [
      { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
      
      // Hna ghadi t-zidi ga3 les pages dyal l'app li kiy-bano wste l'Layout
      // { path: 'dashboard', component: DashboardComponent },
      // { path: 'gestion-lots', component: GestionLotsComponent },
      // { path: 'documents', component: DocumentsComponent },
    ]
  },

  // ==========================================
  // Erreur 404
  // ==========================================
  
  { path: '**', redirectTo: 'dashboard' } // Wla page 404 ila saybtiha
];
import { Routes } from '@angular/router';
import { Login } from './Authentification/login/login';
import { MainLayout } from './main-layout/main-layout';
import { guestGuard } from './core/guards/guest-guard';
import { authGuard } from './core/guards/auth-guard';
import { GestionLots } from './pages/gestion-lots/gestion-lots';
import { ListeCoproprietes } from './pages/liste-coproprietes/liste-coproprietes';
import { Cle } from './pages/cle/cle';

export const routes: Routes = [
  // ==========================================
  // ROUTES PUBLIQUES (Non connectés)
  // ==========================================
  { path: 'login', component: Login, canActivate: [guestGuard] },

  // ==========================================
  // ROUTES PRIVÉES (Sécurisées avec Layout)
  // ==========================================
  {
    path: '', 
    component: MainLayout, 
    // canActivate: [authGuard], // خليتها كومونطير كيفما درتي ليها، ولكن من بعد غتحتاجي تحيدي الكومونطير باش تحمي الصفحات
    children: [
      { path: '', redirectTo: 'dashboard', pathMatch: 'full' }, // إيلا دخل غير لـ '/' غيمشي لـ dashboard
      
      // Hna ghadi t-zidi ga3 les pages dyal l'app li kiy-bano wste l'Layout
      // { path: 'dashboard', component: DashboardComponent },
      
      { path: 'gestion-lots', component: GestionLots },
      { path: 'gestion-lots/:id', component: GestionLots }, // 🛑 هادي كانت برا الـ Layout، دخلتها لداخل باش يبان ليها الـ Sidebar

      { path: 'liste-coproprietes', component: ListeCoproprietes },
      { path: 'residence/:id/coproprietaires', component: ListeCoproprietes },
      { path: 'cles-de-repartition', component: Cle },
      
      
      // { path: 'documents', component: DocumentsComponent },
    ]
  },

  // ==========================================
  // Erreur 404 (Route non trouvée)
  // ==========================================
  { path: '**', redirectTo: 'login' } // من الأحسن إيلا تلف يرجع لـ login باش يـ checki واش مكونيكطي
];
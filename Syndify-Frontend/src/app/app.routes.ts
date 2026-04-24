import { Routes } from '@angular/router';
import { Login } from './Authentification/login/login';
import { MainLayout } from './main-layout/main-layout';
import { guestGuard } from './core/guards/guest-guard';
import { authGuard } from './core/guards/auth-guard';
import { GestionLots } from './pages/gestion-lots/gestion-lots';
import { ListeCoproprietes } from './pages/liste-coproprietes/liste-coproprietes';
import { Cle } from './pages/cle/cle';
import { Dashboard } from './pages/dashboard/dashboard';
import { HistoriqueCoproprietaire } from './pages/historique-coproprietaire/historique-coproprietaire';
import {  Exercises } from './pages/exercises/exercises';
import { SimulationBudget } from './pages/simulerbudget/simulerbudget';
import { BudgetsOperations } from './pages/budgetsdepenses/budgetsdepenses';
import { Appelsfonds } from './pages/appelsfonds/appelsfonds';
import { AppelsFondsDetails } from './pages/appels-fonds-details/appels-fonds-details';
import { Cloturedetails } from './pages/cloturedetails/cloturedetails';

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
    // canActivate: [authGuard],
    children: [
      { path: '', redirectTo: 'dashboard', pathMatch: 'full' }, // إيلا دخل غير لـ '/' غيمشي لـ dashboard
      
     
      { path: 'dashboard', component: Dashboard },
      
      { path: 'gestion-lots', component: GestionLots },
      { path: 'gestion-lots/:id', component: GestionLots }, // 🛑 هادي كانت برا الـ Layout، دخلتها لداخل باش يبان ليها الـ Sidebar

      { path: 'liste-coproprietes', component: ListeCoproprietes },
      { path: 'residence/:id/coproprietaires', component: ListeCoproprietes },
      { path: 'cles-de-repartition', component: Cle },
      { path: 'coproprietaires/historique/:id', component: HistoriqueCoproprietaire },
      {path : 'exercice', component: Exercises}, 
      {path : 'simulation-budget', component: SimulationBudget},
      {path : 'budgets-depenses', component: BudgetsOperations},
      {path : 'appels-de-fonds', component: Appelsfonds},
      { path: 'financement/appels-fonds/details/:id', component: AppelsFondsDetails },
      { path: 'financement/appels-fonds/details/:id', component: Appelsfonds },
      { path: 'exercices/cloture/:id', component: Cloturedetails },
      
      
      // { path: 'documents', component: DocumentsComponent },
    ]
  },

  // ==========================================
  // Erreur 404 (Route non trouvée)
  // ==========================================
  { path: '**', redirectTo: 'login' } // من الأحسن إيلا تلف يرجع لـ login باش يـ checki واش مكونيكطي
];
import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';

export const authGuard: CanActivateFn = (route, state) => {
  const router = inject(Router);
  
  // N-vèrifiw wach kayn l'Token f LocalStorage
  const token = localStorage.getItem('syndify_token');

  if (token) {
    // Ila kayn l'token, khellih i-douz
    return true;
  } else {
    // Ila makaynch, siyftou l'page dyal Login
    router.navigate(['/login']);
    return false;
  }
};
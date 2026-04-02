import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';

export const guestGuard: CanActivateFn = (route, state) => {
  const router = inject(Router);
  const token = localStorage.getItem('syndify_token');

  if (token) {
    // Ila deja m-connecté, siyftou l'Dashboard, ma-3ndo may-dir f Login
    router.navigate(['/dashboard']);
    return false;
  } else {
    return true;
  }
};
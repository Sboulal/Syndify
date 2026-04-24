import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';

export const guestGuard: CanActivateFn = (route, state) => {
  const router = inject(Router);
  // 🟢 Beddli syndify_token b auth_token
  const token = localStorage.getItem('auth_token'); 

  if (token) {
    router.navigate(['/dashboard']);
    return false;
  } else {
    return true;
  }
};
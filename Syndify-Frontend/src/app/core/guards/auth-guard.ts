import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';

export const authGuard: CanActivateFn = (route, state) => {
  const router = inject(Router);
  // 🟢 Beddli syndify_token b auth_token
  const token = localStorage.getItem('auth_token'); 

  if (token) {
    return true;
  } else {
    router.navigate(['/login']);
    return false;
  }
};
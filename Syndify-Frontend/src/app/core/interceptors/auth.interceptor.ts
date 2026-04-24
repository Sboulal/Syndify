import { HttpInterceptorFn } from '@angular/common/http';

export const authInterceptor: HttpInterceptorFn = (req, next) => {
  // 1. Jbed l-Token mn LocalStorage
  const token = localStorage.getItem('auth_token');

  // 2. Ila l9inah, n-ls9ouh f l-Header
  if (token) {
    const clonedRequest = req.clone({
      setHeaders: {
        Authorization: `Bearer ${token}`
      }
    });
    return next(clonedRequest);
  }

  // 3. Ila mal9inach (b7al f page login), n-ssiftou requête 3adiya
  return next(req);
};
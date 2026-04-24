import { ApplicationConfig, provideZoneChangeDetection, LOCALE_ID } from '@angular/core';
import { provideRouter } from '@angular/router';

// 🟢 1. Zidi withInterceptors hna
import { provideHttpClient, withInterceptors } from '@angular/common/http';

import { registerLocaleData } from '@angular/common';
import localeFr from '@angular/common/locales/fr';

import { routes } from './app.routes';

// 🟢 2. Jibi l-Interceptor lli 9addina (t2kdi mn l-chemin s7i7 3la 7sab fin 7ettitih)
import { authInterceptor } from './core/interceptors/auth.interceptor';

// 🟢 DABA ANGULAR GHADI Y-FHEM L-FRANCAIS
registerLocaleData(localeFr, 'fr'); 

export const appConfig: ApplicationConfig = {
  providers: [
    provideRouter(routes),
    
    // 🟢 3. HNA ZEDNA L-INTERCEPTOR BACH Y-LSE9 L-TOKEN DIMA
    provideHttpClient(withInterceptors([authInterceptor])),
    
    { provide: LOCALE_ID, useValue: 'fr' }
  ]
};
import { Component, inject, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient, HttpClientModule, HttpHeaders } from '@angular/common/http';
import { Router } from '@angular/router';
import * as CryptoJS from 'crypto-js';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, FormsModule, HttpClientModule],
  templateUrl: './login.html',
  styleUrls: ['./login.css']
})
export class Login {
  step: number = 1; 
  identifier: string = '';
  otp: string[] = ['', '', '', '', ''];
  
  isLoading: boolean = false;
  errorMessage: string = '';

  private http = inject(HttpClient);
  private router = inject(Router);
  private cdr = inject(ChangeDetectorRef); 

  private apiUrl = 'http://51.178.87.234:8085/api';

  private getSecurityHeaders(): HttpHeaders {
    const secret = "SyndifySecretKey123";
    const timestamp = Math.floor(Date.now() / 1000).toString();
    const signature = CryptoJS.HmacSHA256(timestamp, secret).toString(CryptoJS.enc.Hex);

    return new HttpHeaders({
      'X-Timestamp': timestamp,
      'X-Signature': signature,
      'X-Source': 'Web'
    });
  }

  // ==========================================
  // CONNEXION DIRECTE (BYPASS OTP)
  // ==========================================
  sendCode() {
    if (!this.identifier) return;

    this.isLoading = true;
    this.errorMessage = '';
    console.log("Envoi de la requête à Laravel (Mode Bypass)...");

    this.http.post(`${this.apiUrl}/login`, 
      { identifier: this.identifier }, 
      { headers: this.getSecurityHeaders() }
    ).subscribe({
      next: (res: any) => {
        console.log("✅ Connexion réussie, Token reçu :", res.token);
        
        // 🛑 N-sajjlou l'Token f LocalStorage nichan
        localStorage.setItem('auth_token', res.token); 
        localStorage.setItem('syndify_user', JSON.stringify(res.user));
        
        this.isLoading = false;
        this.cdr.detectChanges();
        
        // 🛑 N-diro redirection nichan l'page dyal l-Lots (awla Dashboard)
        this.router.navigate(['/gestion-lots']); 
      },
      error: (err) => {
        console.error("Erreur Laravel :", err);
        this.errorMessage = err.error?.message || "Erreur de connexion. L'identifiant est-il correct ?";
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
  }

  // Had l'fonction mabqatch k-tkhdem 7it drna Bypass, walakin n-khlliwha hna l-mn be3d
  verifyCode() {
    // ... Logique OTP jdida mlli t-bghiy t-rddiha mn b3d
  }

  moveFocus(e: any, previous: any, current: any, next: any) {
    let length = current.value.length;
    let maxlength = current.getAttribute('maxlength');
    
    if (length == maxlength && next) {
      next.focus();
    }
    if (e.key === 'Backspace' && previous) {
      previous.focus();
    }
  }
}
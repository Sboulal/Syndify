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

  // 🟢 1. We7edna l-IP hna
  private apiUrl = 'http://nomade-cloud.com:8085/api';

  private getSecurityHeaders(): HttpHeaders {
    const secret = "SyndifySecretKey123";
    const timestamp = Math.floor(Date.now() / 1000).toString();
    const signature = CryptoJS.HmacSHA256(timestamp, secret).toString(CryptoJS.enc.Hex);

    return new HttpHeaders({
      'X-Timestamp': timestamp,
      'X-Signature': signature,
      'X-Source': 'Web',
      'Content-Type': 'application/json'
    });
  }

  // ==========================================
  // ÉTAPE 1: DEMANDER LE CODE OTP
  // ==========================================
  sendCode() {
    console.log("👉 L-Email lli mktoub:", this.identifier);

    if (!this.identifier) return;

    this.isLoading = true;
    this.errorMessage = '';

    const payload = { identifier: this.identifier };
    console.log("📦 L-Payload lli ghadi n-siftou:", payload);

    this.http.post(`${this.apiUrl}/login`, payload, { headers: this.getSecurityHeaders() })
    .subscribe({
      next: (res: any) => {
        this.step = 2; 
        this.isLoading = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        console.error("❌ Erreur API:", err);
        this.errorMessage = err.error?.message || err.error?.errors?.identifier[0] || "Erreur. L'identifiant est-il correct ?";
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
  }

  // ==========================================
  // ÉTAPE 2: VÉRIFIER L'OTP ET SAUVEGARDER
  // ==========================================
  verifyCode() {
    const otpCode = this.otp.join('');
    if (otpCode.length < 5) return;

    this.isLoading = true;
    this.errorMessage = '';

    this.http.post(`${this.apiUrl}/verify-otp`, 
      { identifier: this.identifier, otp_code: otpCode }, 
      { headers: this.getSecurityHeaders() }
    ).subscribe({
      next: (res: any) => {
        
        // 🟢 2. S-smiya dyal l-Token mw7da m3a l-Interceptor
        localStorage.setItem('auth_token', res.token); 
        
        localStorage.setItem('user_id', res.user.id.toString());
        localStorage.setItem('user_role', res.user.role);
        
        this.isLoading = false;
        this.cdr.detectChanges();
        
        this.router.navigate(['/dashboard']); 
      },
      error: (err) => {
        this.errorMessage = err.error?.message || "Code OTP invalide ou expiré.";
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
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
import { Component, inject, ChangeDetectorRef } from '@angular/core'; // 👈 Zidna ChangeDetectorRef
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
  private cdr = inject(ChangeDetectorRef); // 👈 Zidnah hna bach n-forciw l'affichage

  private apiUrl = 'http://localhost:8085/api';

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

  sendCode() {
    if (!this.identifier) return;

    this.isLoading = true;
    this.errorMessage = '';
    console.log("Envoi de la requête à Laravel...");

    this.http.post(`${this.apiUrl}/login`, 
      { identifier: this.identifier }, 
      { headers: this.getSecurityHeaders() }
    ).subscribe({
      next: (res: any) => {
        console.log("Réponse Laravel :", res);
        this.step = 2; // Kat-bdel l'étape
        this.isLoading = false;
        this.cdr.detectChanges(); // 👈 Kan-goulou l Angular: "Fiq w bdel l'écran daba!"
      },
      error: (err) => {
        console.error("Erreur Laravel :", err);
        this.errorMessage = err.error?.message || "Erreur de connexion. L'identifiant est-il correct ?";
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
  }

  verifyCode() {
    const otpCode = this.otp.join(''); 
    
    if (otpCode.length !== 5) {
      this.errorMessage = 'Veuillez entrer les 5 chiffres.';
      return;
    }

    this.isLoading = true;
    this.errorMessage = '';
    console.log("Vérification de l'OTP...");

    this.http.post(`${this.apiUrl}/verify-otp`, 
      { identifier: this.identifier, otp_code: otpCode },
      { headers: this.getSecurityHeaders() }
    ).subscribe({
      next: (res: any) => {
        console.log("Token reçu :", res.token);
        localStorage.setItem('syndify_token', res.token);
        localStorage.setItem('syndify_user', JSON.stringify(res.user));
        this.isLoading = false;
        this.cdr.detectChanges();
        // this.router.navigate(['/dashboard']); 
      },
      error: (err) => {
        console.error("Erreur OTP :", err);
        this.errorMessage = err.error?.message || "Le code OTP est invalide.";
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
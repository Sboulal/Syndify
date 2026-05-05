import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class ClotureService {
  // 🟢 Rddinaha 'clotures' b-l-'s' bash t-matchi m3a api.php
  private apiUrl = 'http://51.178.87.234:8085/api/clotures'; 

  constructor(private http: HttpClient) {}

  // 🟢 Jbedna l-Headers kima drti f Cloturedetails
  private getHeaders() {
    const userId = localStorage.getItem('user_id') || '1';
    return new HttpHeaders({
      'X-User-Id': userId
    });
  }

  // 1. Jbed d-data dyal l-clôture (Rddinaha POST w kat-ssifet se_identifier)
  getClotureData(sp_id: string, se_id: string): Observable<any> {
    return this.http.post(`${this.apiUrl}/charger`, { se_identifier: se_id }, { headers: this.getHeaders() });
  }

  // 2. Enregistrer Brouillon (Rddinaha POST)
  enregistrerBrouillon(payload: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/enregistrer`, payload, { headers: this.getHeaders() });
  }

  // 3. Finaliser (Rddinaha POST)
  finaliserCloture(payload: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/finaliser`, payload, { headers: this.getHeaders() });
  }
}
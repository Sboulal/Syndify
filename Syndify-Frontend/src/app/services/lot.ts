import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class LotService {
  private baseUrl = 'http://51.178.87.234:8085/api'; // IP unifiée
  private apiUrl = `${this.baseUrl}/lots`;

  constructor(private http: HttpClient) {}

  // 1. Liste des lots
  getListe(): Observable<any> {
    return this.http.post(`${this.apiUrl}/liste`, {});
  }

  // 2. Ajouter un lot
  ajouter(data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/ajouter`, data);
  }

  // 3. Modifier un lot
  modifier(data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/modifier`, data);
  }

  // 4. Supprimer un lot
  supprimer(lotId: number): Observable<any> {
    return this.http.post(`${this.apiUrl}/supprimer`, { 
      lot_id: lotId 
    });
  }

  // 5. Détails d'un lot
  getDetails(lotId: number): Observable<any> {
    return this.http.post(`${this.apiUrl}/details`, {
      lot_id: lotId
    });
  }
}
import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class LotService {
 private baseUrl = 'http://51.178.87.234:8085/api';
  private apiUrl = `${this.baseUrl}/lots`;

  constructor(private http: HttpClient) {}

  // 1. Liste des lots
  getListe(proprieteId: string): Observable<any> {
    return this.http.post(`${this.apiUrl}/liste`, { propriete_id: proprieteId });
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
  supprimer(proprieteId: string, lotId: number): Observable<any> {
    return this.http.post(`${this.apiUrl}/supprimer`, { 
      propriete_id: proprieteId, 
      lot_id: lotId 
    });
  }

  // 5. Détails d'un lot (M3a les tantièmes w les propriétaires)
  getDetails(proprieteId: string, lotId: number): Observable<any> {
    return this.http.post(`${this.apiUrl}/details`, {
      propriete_id: proprieteId,
      lot_id: lotId
    });
  }
}
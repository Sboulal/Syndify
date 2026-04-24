import { Injectable } from '@angular/core';
import { HttpClient,  } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root',
})
export class CleRepartitionService {
  
  // 🟢 IP Unifiée b7al l-Services l-khrin
  private baseUrl = 'http://nomade-cloud.com:8085/api';
  private apiUrl = `${this.baseUrl}/cles-repartition`;

  constructor(private http: HttpClient) {}

  // ==========================================
  // 1. Récupérer la liste des clés de répartition
  // ==========================================
  getListe(): Observable<any> {
    // 🔴 7iyedna proprieteId mn l-paramètres w l-payload
    return this.http.post(`${this.apiUrl}/liste`, {});
  }

  // ==========================================
  // 2. Ajouter une nouvelle clé
  // ==========================================
  ajouter(payload: any): Observable<any> {
    // L-Payload hna (jay mn l-Component) aslan mab9ach fih propriete_id 🚀
    return this.http.post(`${this.apiUrl}/ajouter`, payload);
  }

  // ==========================================
  // 3. Modifier une clé existante
  // ==========================================
  modifier(payload: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/modifier`, payload);
  }

  // ==========================================
  // 4. Supprimer une clé
  // ==========================================
  supprimer(cleId: number): Observable<any> {
    // 🔴 7iyedna propriete_id mn l-payload
    return this.http.post(`${this.apiUrl}/supprimer`, { 
      cle_id: cleId 
    });
  }
}
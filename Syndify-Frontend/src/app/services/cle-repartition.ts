import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root',
})
export class CleRepartitionService {
  
  // 🛑 بدلي هاد الـ URL على حساب الـ Backend ديالك (ولا استعملي environment.apiUrl)
  private apiUrl = 'http://51.178.87.234:8085/api/cles-repartition';

  constructor(private http: HttpClient) {}

  // ==========================================
  // 1. Récupérer la liste des clés de répartition
  // ==========================================
  getListe(proprieteId: string): Observable<any> {
    // الأغلبية كديرو POST باش تصيفطو propriete_id، إيلا كنتو كديرو GET بدليها لـ get()
    return this.http.post(`${this.apiUrl}/liste`, { propriete_id: proprieteId });
  }

  // ==========================================
  // 2. Ajouter une nouvelle clé
  // ==========================================
  ajouter(payload: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/ajouter`, payload);
  }

  // ==========================================
  // 3. Modifier une clé existante
  // ==========================================
  modifier(payload: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/modifier`, payload);
  }

  // ==========================================
  // 4. Supprimer une clé (au cas où تحتاجيها من بعد)
  // ==========================================
  supprimer(proprieteId: string, cleId: number): Observable<any> {
    return this.http.post(`${this.apiUrl}/supprimer`, { 
      propriete_id: proprieteId, 
      cle_id: cleId 
    });
  }
}
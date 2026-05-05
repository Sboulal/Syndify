import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class CoproprietaireService {
  
  // N.B: Bddelt lik l-URL l-IP (51.178...) bach y-b9a cohérent m3a les autres pages
  private baseUrl = 'http://51.178.87.234:8085/api'; 
  private apiUrl = `${this.baseUrl}/coproprietaires`;

  constructor(private http: HttpClient) {}

  // 1. Liste
  getListe(typeAffichage: string, lastId?: number): Observable<any> {
    const body = {
      type_affichage: typeAffichage,
      last_id: lastId
    };
    return this.http.post(`${this.apiUrl}/liste`, body);
  }

  // 2. Désactiver
  desactiver(userId: string): Observable<any> {
    return this.http.post(`${this.apiUrl}/desactiver`, {
      user_id: userId
    });
  }

  // 3. Ajouter ou Modifier
  ajouter(coproData: any) {
    return this.http.post(`${this.apiUrl}/ajouter`, {
      nom: coproData.nom,
      email: coproData.email,
      tel: coproData.tel,
      user_id: coproData.user_id, 
      status: coproData.status,
      selectedLots: coproData.selectedLots
    });
  }

  // 4. Supprimer
  supprimer(userId: string): Observable<any> {
    return this.http.post(`${this.apiUrl}/supprimer`, {
      user_id: userId
    });
  }

  getHistorique(userId: string, exerciceId?: string): Observable<any> {
    return this.http.post(`${this.apiUrl}/historique`, {
      user_id: userId,
      se_identifier: exerciceId
    });
  }
}
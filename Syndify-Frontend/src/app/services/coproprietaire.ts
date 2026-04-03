import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class CoproprietaireService {
  
  private baseUrl = 'http://51.178.87.234:8085/api';
  private apiUrl = `${this.baseUrl}/coproprietaires`;

  constructor(private http: HttpClient) {}

  // 1. Njbdou l-liste (POST kima qaddina f Laravel)
  getListe(proprieteId: string, typeAffichage: string, lastId?: number): Observable<any> {
    const body = {
      propriete_id: proprieteId,
      type_affichage: typeAffichage,
      last_id: lastId
    };
    return this.http.post(`${(this.apiUrl)}/liste`, body);
  }

  // 2. Désactiver
  desactiver(proprieteId: string, userId: string): Observable<any> {
    return this.http.post(`${this.apiUrl}/desactiver`, {
      propriete_id: proprieteId,
      user_id: userId
    });
  }

  // 3. Ajouter (Invitation)
  ajouter(proprieteId: string, email: string): Observable<any> {
    return this.http.post(`${this.apiUrl}/ajouter`, {
      propriete_id: proprieteId,
      email: email
    });
  }
}
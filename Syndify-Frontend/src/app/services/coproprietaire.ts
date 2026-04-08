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

ajouter(proprieteId: string, coproData: any) {
    // 🛑 FIX: 7iydna "coproprietaires" mn l-URL hna 7it aslan kayna f this.apiUrl
    return this.http.post(`${this.apiUrl}/ajouter`, {
      propriete_id: proprieteId,
      nom: coproData.nom,
      email: coproData.email,
      tel: coproData.tel,
      user_id: coproData.user_id, // Identifiant personnalisé (optional)
      status: coproData.status
    });
  }
  supprimer(proprieteId: string, userId: string): Observable<any> {
    return this.http.post(`${this.apiUrl}/supprimer`, {
      propriete_id: proprieteId,
      user_id: userId
    });
  }
}
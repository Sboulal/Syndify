import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class ExerciceService {
  
  private apiUrl = 'http://nomade-cloud.com:8085/api/exercices'; // T2kdi mnhoum

  constructor(private http: HttpClient) {}

  // 🟢 Koulchi wella POST w kaysift data f l-body (kima derti f l-lots)
  
  getListe(sp_identifier: string): Observable<any> {
    return this.http.post(`${this.apiUrl}/liste`, { sp_identifier });
  }

  ajouter(data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/ajouter`, data);
  }

  modifier(data: any): Observable<any> {
    return this.http.post(`${this.apiUrl}/modifier`, data);
  }

  supprimer(sp_identifier: string, se_identifier: string): Observable<any> {
    return this.http.post(`${this.apiUrl}/supprimer`, { sp_identifier, se_identifier });
  }
}
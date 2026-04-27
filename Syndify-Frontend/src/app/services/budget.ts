import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';

@Injectable({
  providedIn: 'root'
})
export class BudgetService {
  // 🟢 URL dyal l-API
  private apiUrl = 'http://nomade-cloud.com:8085/api';

  constructor(private http: HttpClient) {}

  chargerDonnees(payload: any) {
    return this.http.post(`${this.apiUrl}/budgets/charger`, payload);
  }

  // 🟢 Zidna l-Encaissement
  ajouterEncaissement(payload: any) {
    return this.http.post(`${this.apiUrl}/encaissements/ajouter`, payload);
  }

  // 🟢 Zidna l-Dépense
  ajouterDepense(payload: any) {
    return this.http.post(`${this.apiUrl}/depenses/ajouter`, payload);
  }

  // 🟢 ZIDNA HADI: Fonction dyal téléchargement l-PDF
  telechargerReleve(payload: any) {
    // ⚠️ responseType: 'blob' hiya s-ser bash Angular y-9der y-telechargé l-fichiers (PDF, Excel...)
    return this.http.post(`${this.apiUrl}/budgets/releve`, payload, { responseType: 'blob' });
  }
}
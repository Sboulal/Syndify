import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';

@Injectable({
  providedIn: 'root'
})
export class BudgetService {
  // 🟢 URL dyal l-API (Bdelih ila kan 3ndk URL akhor)
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
}
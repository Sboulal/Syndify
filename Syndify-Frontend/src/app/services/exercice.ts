import { inject, Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';

@Injectable({
  providedIn: 'root'
})
export class ExerciceService {
  private http = inject(HttpClient);
  private apiUrl = 'http://51.178.87.234:8085/api/exercices';

  getListe() {
    return this.http.post(`${this.apiUrl}/liste`, {});
  }

  ajouter(data: any) {
    // 🟢 Fix: Bach matchi d-data m3a l-Backend
    const payload = {
      ...data,
      period: data.periode // Laravel kay-tsenna 'period'
    };
    return this.http.post(`${this.apiUrl}/ajouter`, payload);
  }

  modifier(data: any) {
    // 🟢 HNA FIN KINE L-MOUCHKIL: T2kdi blli 'se_identifier' kine w s7i7
    const payload = {
      ...data,
      se_identifier: data.se_identifier, // Darouri kheddam f l-modifier
      period: data.periode || data.period 
    };
    return this.http.post(`${this.apiUrl}/modifier`, payload);
  }

  supprimer(se_id: string) {
    // 🟢 L-Backend dyalna daba m-m7tajch sp_identifier
    return this.http.post(`${this.apiUrl}/supprimer`, { se_identifier: se_id });
  }
}
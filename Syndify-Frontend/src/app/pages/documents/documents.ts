import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { PageHeader } from '../../components/page-header/page-header';

@Component({
  selector: 'app-documents',
  standalone: true,
  imports: [CommonModule, PageHeader],
  templateUrl: './documents.html',
})
export class Documents implements OnInit {
  isLoading = false;
  residenceInfo = { nom: 'Chargement...', adresse: 'Veuillez patienter' };
  
  totalSize: string = '0 B';
  totalSizeBytes: number = 0;
  storagePercentage: number = 0;
  
  recentFiles: any[] = [];
  folderContent: any[] = [];
  statistics: any[] = [];

  currentPath: string = ''; 
  pathHistory: string[] = []; 

  private baseUrl = 'http://51.178.87.234:8085/api/documents';

  constructor(private http: HttpClient, private cdr: ChangeDetectorRef) {}

  ngOnInit() { this.chargerDocuments(); }

  private getHeaders() {
    return new HttpHeaders({ 'X-User-Id': localStorage.getItem('user_id') || '1' });
  }

  chargerDocuments() {
    this.isLoading = true;
    this.http.post(`${this.baseUrl}/principal`, {}, { headers: this.getHeaders() }).subscribe({
      next: (res: any) => {
        if (res.success) {
          this.residenceInfo = res.residence;
          this.totalSize = res.data.total_size;
          this.totalSizeBytes = res.data.total_size_bytes;
          this.recentFiles = res.data.recent_files;
          this.folderContent = res.data.content;
          this.currentPath = '';
          this.pathHistory = [];
          this.storagePercentage = Math.min((this.totalSizeBytes / (15 * 1024 * 1024 * 1024)) * 100, 100);
          this.statistics = Object.keys(res.data.statistics).map(key => ({
            name: key, ...res.data.statistics[key],
            width: res.data.total_size_bytes > 0 ? (res.data.statistics[key].size / res.data.total_size_bytes) * 100 : 0
          }));
        }
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
  }

  ouvrirDossier(item: any) {
    if (item.type !== 'folder') return;
    this.isLoading = true;
    this.http.post(`${this.baseUrl}/sous-dossier`, { path: item.path }, { headers: this.getHeaders() }).subscribe({
      next: (res: any) => {
        if (res.success) {
          this.pathHistory.push(this.currentPath);
          this.currentPath = item.path;
          this.folderContent = res.data;
        }
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
  }

  retour() {
    const prev = this.pathHistory.pop();
    if (prev === undefined) return;
    if (prev === '') { this.chargerDocuments(); return; }
    this.ouvrirDossier({ type: 'folder', path: prev });
    this.pathHistory.pop(); // Fix double push
  }

  getBreadcrumbs() {
    if (!this.currentPath) return ['Documents'];
    const parts = this.currentPath.split('/').slice(2); // Skip 'proprietes/SP-ID'
    return ['Documents', ...parts];
  }

  telecharger(path: string, type: string) {
    console.log(`📥 Lancement du téléchargement pour: ${path}`);
    
    // 🟢 1. Kan-tll3ou l-Loading
    this.isLoading = true; 
    this.cdr.detectChanges();

    // 🟢 2. Kan-saybou l-Payload (Zidna propriete_id darori l-sécurité dyal Laravel)
    const proprieteId = localStorage.getItem('active_propriete_id') || 'SP-87248712';
    const payload = {
      path: path,
      propriete_id: proprieteId
    };

    // 🟢 3. Kan-ssiftou Request b- responseType: 'blob'
    this.http.post('http://51.178.87.234:8085/api/documents/telecharger', payload, { 
      responseType: 'blob', 
      headers: this.getHeaders() 
    }).subscribe({
      next: (res: Blob) => {
        console.log('✅ Fichier reçu avec succès !');
        
        // 🟢 4. Kan-9addou l-Format (ZIP awla PDF)
        const mimeType = type === 'folder' ? 'application/zip' : (res.type || 'application/pdf');
        const blob = new Blob([res], { type: mimeType });
        
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        
        a.style.display = 'none';
        a.href = url;
        
        // 🟢 5. Kan-jbdou Smiya dyal l-fichier
        let fileName = path.split('/').pop() || 'document';
        if (type === 'folder' && !fileName.endsWith('.zip')) {
            fileName += '.zip';
        }
        
        a.download = fileName;
        document.body.appendChild(a);
        
        // 🟢 6. Kan-cliquiw 3lih awtomatiki bash y-tle3 l-téléchargement f l-Navigateur
        a.click(); 
        
        // 🟢 7. Kan-ms7ou trace w n-7iydou l-Loading
        setTimeout(() => {
            window.URL.revokeObjectURL(url);
            a.remove();
            this.isLoading = false; 
            this.cdr.detectChanges();
        }, 1000);
      },
      error: (err) => {
        console.error("❌ Erreur de téléchargement:", err);
        
        // 🟢 8. Affichage dyal l-Erreurs lli jayin mn Laravel
        if (err.error instanceof Blob) {
          const reader = new FileReader();
          reader.onload = (e: any) => {
            try {
              const laravelError = JSON.parse(e.target.result);
              alert("Erreur Serveur: " + laravelError.message);
            } catch (e) {
              alert("Impossible de télécharger ce fichier.");
            }
          };
          reader.readAsText(err.error);
        } else {
          alert("Erreur de connexion avec le serveur.");
        }
        
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
  }
}
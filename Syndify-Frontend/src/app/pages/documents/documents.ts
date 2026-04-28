import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient } from '@angular/common/http';
import { PageHeader } from '../../components/page-header/page-header';

@Component({
  selector: 'app-documents',
  standalone: true,
  imports: [CommonModule, PageHeader],
  templateUrl: './documents.html',
})
export class Documents implements OnInit {
  isLoading = false;
  
  // 🟢 FIX: Zidna residenceInfo l-Header
  residenceInfo = { nom: 'Résidence', adresse: 'Chargement...' };

  // Data variables
  totalSize: string = '0 B';
  totalSizeBytes: number = 0;
  maxStorageBytes: number = 16106127360; // 15 GB par défaut
  maxStorageFormatted: string = '15 GB';
  storagePercentage: number = 0;
  
  recentFiles: any[] = [];
  folderContent: any[] = [];
  statistics: any[] = [];

  currentPath: string = ''; 
  pathHistory: string[] = []; 

  constructor(
    private http: HttpClient,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit() {
    this.chargerDocuments();
  }

  chargerDocuments() {
    this.isLoading = true;
    const proprieteId = localStorage.getItem('active_propriete_id') || 'SP-87248712';

    this.http.post('http://nomade-cloud.com:8085/api/documents/principal', { propriete_id: proprieteId })
      .subscribe({
        next: (res: any) => {
          if (res.success) {
            
            // 🟢 FIX: N-qraw residenceInfo mn l-Backend
            if (res.residence) {
               this.residenceInfo = res.residence;
            }

            this.totalSize = res.data.total_size;
            this.totalSizeBytes = res.data.total_size_bytes;
            this.maxStorageBytes = res.data.max_storage_bytes || this.maxStorageBytes;
            this.maxStorageFormatted = res.data.max_storage_formatted || this.maxStorageFormatted;
            
            this.recentFiles = res.data.recent_files;
            this.folderContent = res.data.content;
            
            this.currentPath = ''; 
            this.pathHistory = [];
            
            this.storagePercentage = Math.min((this.totalSizeBytes / this.maxStorageBytes) * 100, 100);

            this.statistics = Object.keys(res.data.statistics).map(key => ({
              name: key,
              ...res.data.statistics[key],
              width: res.data.total_size_bytes > 0 
                     ? (res.data.statistics[key].size / res.data.total_size_bytes) * 100 
                     : 0
            }));
          }
          this.isLoading = false;
          this.cdr.detectChanges();
        },
        error: (err) => {
          console.error("❌ Erreur chargement documents:", err);
          this.isLoading = false;
          this.cdr.detectChanges();
        }
      });
  }

  ouvrirDossier(item: any) {
    if (item.type !== 'folder') return; 

    this.isLoading = true;
    const proprieteId = localStorage.getItem('active_propriete_id') || 'SP-87248712';

    this.http.post('http://nomade-cloud.com:8085/api/documents/sous-dossier', { 
      propriete_id: proprieteId,
      path: item.path 
    }).subscribe({
      next: (res: any) => {
        if (res.success) {
          this.pathHistory.push(this.currentPath); 
          this.currentPath = item.path; 
          this.folderContent = res.data; 
        }
        this.isLoading = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        alert("Erreur lors de l'ouverture du dossier.");
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
  }

  retour() {
    if (this.pathHistory.length === 0) return;
    
    const previousPath = this.pathHistory.pop();
    if (!previousPath || previousPath === '') {
      this.chargerDocuments(); 
      return;
    }

    this.isLoading = true;
    const proprieteId = localStorage.getItem('active_propriete_id') || 'SP-87248712';

    this.http.post('http://nomade-cloud.com:8085/api/documents/sous-dossier', { 
      propriete_id: proprieteId,
      path: previousPath 
    }).subscribe({
      next: (res: any) => {
        if (res.success) {
          this.currentPath = previousPath;
          this.folderContent = res.data;
        }
        this.isLoading = false;
        this.cdr.detectChanges();
      },
      error: (err) => {
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
  }

  telecharger(path: string, type: string) {
    console.log(`📥 Téléchargement de: ${path}`);
    const proprieteId = localStorage.getItem('active_propriete_id') || 'SP-87248712';
    
    this.isLoading = true; 
    this.cdr.detectChanges();

    this.http.post('http://nomade-cloud.com:8085/api/documents/telecharger', { 
      propriete_id: proprieteId,
      path: path 
    }, { responseType: 'blob' }).subscribe({
      next: (res: Blob) => {
        const mimeType = type === 'folder' ? 'application/zip' : (res.type || 'application/pdf');
        const blob = new Blob([res], { type: mimeType });
        
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        
        a.style.display = 'none';
        a.href = url;
        a.target = '_blank'; 
        
        let fileName = path.split('/').pop() || 'document';
        if (type === 'folder') {
            fileName += '.zip';
        }
        
        a.download = fileName;
        document.body.appendChild(a);
        
        a.click(); 
        
        setTimeout(() => {
            window.URL.revokeObjectURL(url);
            a.remove();
            this.isLoading = false; 
            this.cdr.detectChanges();
        }, 3000);

      },
      error: (err) => {
        console.error("❌ Erreur téléchargement:", err);
        
        if (err.error instanceof Blob) {
          const reader = new FileReader();
          reader.onload = (e: any) => {
            try {
              const laravelError = JSON.parse(e.target.result);
              alert("Erreur Serveur: " + laravelError.message);
            } catch (e) {
              alert("Erreur lors du téléchargement.");
            }
          };
          reader.readAsText(err.error);
        } else {
          alert("Erreur lors du téléchargement.");
        }
        
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
  }
}
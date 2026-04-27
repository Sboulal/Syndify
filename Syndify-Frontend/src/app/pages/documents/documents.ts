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
  
  // Data variables
  totalSize: string = '0 B';
  totalSizeBytes: number = 0;
  maxStorageBytes: number = 16106127360; // 15 GB par défaut
  maxStorageFormatted: string = '15 GB';
  storagePercentage: number = 0;
  
  recentFiles: any[] = [];
  folderContent: any[] = [];
  statistics: any[] = [];

  // 🟢 VARIABLES DYAL NAVIGATION
  currentPath: string = ''; 
  pathHistory: string[] = []; // Bash n-3e9lou 3la l-historique dyal rjou3

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
            this.totalSize = res.data.total_size;
            this.totalSizeBytes = res.data.total_size_bytes;
            this.maxStorageBytes = res.data.max_storage_bytes || this.maxStorageBytes;
            this.maxStorageFormatted = res.data.max_storage_formatted || this.maxStorageFormatted;
            
            this.recentFiles = res.data.recent_files;
            this.folderContent = res.data.content;
            
            // 🟢 N-rjj3ou l-racine
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

  // 🟢 FONCTION JDIDA BASH N-DKHLOU L-DOSSIER
  ouvrirDossier(item: any) {
    if (item.type !== 'folder') return; // Ila kan fichier, ma-ndirou walo

    this.isLoading = true;
    const proprieteId = localStorage.getItem('active_propriete_id') || 'SP-87248712';

    this.http.post('http://nomade-cloud.com:8085/api/documents/sous-dossier', { 
      propriete_id: proprieteId,
      path: item.path 
    }).subscribe({
      next: (res: any) => {
        if (res.success) {
          this.pathHistory.push(this.currentPath); // N-khbbiw l-dossier l-9dim
          this.currentPath = item.path; // N-sejjlou l-jdid
          this.folderContent = res.data; // N-beddlou l-liste
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

  // 🟢 FONCTION JDIDA BASH N-RJJ3OU L-LOUR
  retour() {
    if (this.pathHistory.length === 0) return;
    
    const previousPath = this.pathHistory.pop();
    if (!previousPath || previousPath === '') {
      this.chargerDocuments(); // Rje3na l-racine kamla
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
    
    // 🟢 N-biyynou l-Loading bash l-User y-tsenna
    this.isLoading = true; 
    this.cdr.detectChanges();

    this.http.post('http://nomade-cloud.com:8085/api/documents/telecharger', { 
      propriete_id: proprieteId,
      path: path 
    }, { responseType: 'blob' }).subscribe({
      next: (res: Blob) => {
        
        // 🟢 FIX 1: N-3tiw l-MimeType s7i7 bash l-Navigateur ma-y-t-blokash
        const mimeType = type === 'folder' ? 'application/zip' : (res.type || 'application/pdf');
        const blob = new Blob([res], { type: mimeType });
        
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        
        a.style.display = 'none';
        a.href = url;
        a.target = '_blank'; // 🟢 FIX 2: Kat-3awen f l-Bypass dyal l-HTTPS warning
        
        // N-9addou s-smiya dyal l-Fichier
        let fileName = path.split('/').pop() || 'document';
        if (type === 'folder') {
            fileName += '.zip';
        }
        
        a.download = fileName;
        document.body.appendChild(a);
        
        a.click(); // N-wrekou 3lih awtomatiki
        
        // 🟢 FIX 3: N-tsennaw 3 tawani (3000 ms) 3ad n-ms7ou l-lien mn l-mémoire
        setTimeout(() => {
            window.URL.revokeObjectURL(url);
            a.remove();
            this.isLoading = false; // N-7iydou l-Loading
            this.cdr.detectChanges();
        }, 3000);

      },
      error: (err) => {
        console.error("❌ Erreur téléchargement:", err);
        
        // 🟢 N-9raw l-Erreur lli mkhbbya f l-Blob
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
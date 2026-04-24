import { Component, OnInit, ChangeDetectorRef } from '@angular/core'; 
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router'; 
import { PageHeader } from '../../components/page-header/page-header'; 
import { FormsModule } from '@angular/forms';
import { CoproprietaireService } from '../../services/coproprietaire'; // 🟢 Zidna l-Service

@Component({
  selector: 'app-historique-coproprietaire',
  standalone: true,
  imports: [CommonModule, PageHeader, FormsModule],
  templateUrl: './historique-coproprietaire.html',
})
export class HistoriqueCoproprietaire implements OnInit {
  
  userId: string | null = null;
  isLoading: boolean = false;

  coproprietaire: any = null;
  exercicesPasses: any[] = [];
  exerciceSelectionne: string = '';
  
  totalEncaissements: number = 0;
  totalDepenses: number = 0;
  operations: any[] = [];

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private cdr: ChangeDetectorRef,
    private coproprietaireService: CoproprietaireService // 🟢 Injectina l-Service
  ) {}

  ngOnInit() {
    this.userId = this.route.snapshot.paramMap.get('id');
    
    if (this.userId) {
      this.chargerHistorique(); // 🟢 Kan-3eytou l-API nichan l-merra l-lowla
    }
  }

  // 🟢 Fonction wa7da li kat-jbed kolchi mn l-Backend
  chargerHistorique() {
    if (!this.userId) return;

    this.isLoading = true;
    this.cdr.detectChanges(); 
    
    this.coproprietaireService.getHistorique(this.userId, this.exerciceSelectionne).subscribe({
      next: (res: any) => {
        if (res.success) {
          const d = res.data;
          this.coproprietaire = d.coproprietaire;
          this.exercicesPasses = d.exercices;
          this.exerciceSelectionne = d.exercice_selectionne;
          
          this.totalEncaissements = d.total_encaissements;
          this.totalDepenses = d.total_depenses;
          this.operations = d.operations;
        }
        this.isLoading = false;
        this.cdr.detectChanges(); 
      },
      error: (err: any) => {
        console.error("Erreur API:", err);
        alert("Erreur lors du chargement de l'historique.");
        this.isLoading = false;
        this.cdr.detectChanges();
      }
    });
  }

  // 🟢 Mlli l-user kay-beddel l-exercice mn l-Dropdown (Select)
  onExerciceChange() {
    this.chargerHistorique();
  }

  telechargerDoc(url: string) {
    if (!url) {
      alert("Aucun document disponible.");
      return;
    }
    // F l-mousta9bal hna ghadi t-diri lien y-t7el f page jdida
    window.open('http://nomade-cloud.com:8085/storage/' + url, '_blank');
  }

  retour() {
    this.router.navigate(['/liste-coproprietes']); 
  }
}
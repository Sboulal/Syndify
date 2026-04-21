import { Component, OnInit, ChangeDetectorRef } from '@angular/core'; // 🟢 Zdt ChangeDetectorRef hna
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router'; 
import { PageHeader } from '../../components/page-header/page-header'; 
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-historique-coproprietaire',
  standalone: true,
  imports: [CommonModule, PageHeader, FormsModule],
  templateUrl: './historique-coproprietaire.html',
})
export class HistoriqueCoproprietaire implements OnInit {
  
  userId: string | null = null;
  isLoading: boolean = false;

  // Data mockées (Khassk t3ewdihom b l'appel API dyalek l'backend)
  coproprietaire: any = null;
  exercicesPasses: any[] = [];
  exerciceSelectionne: string = '';
  
  totalEncaissements: number = 0;
  totalDepenses: number = 0;
  operations: any[] = [];

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private cdr: ChangeDetectorRef // 🟢 Zdt cdr hna f l'constructor
    // private coproprietaireService: CoproprietaireService // Bach tchargi data mn l'API
  ) {}

  ngOnInit() {
    this.userId = this.route.snapshot.paramMap.get('id');
    
    // Hna ghat3eyti 3la l'API lli ghadjiblik les exercices
    this.chargerExercices();
  }

  chargerExercices() {
    // MOCK DATA: B7al ila jabha l'API
    this.exercicesPasses = [
      { id: '2024', annee: 'Exercice 2024' },
      { id: '2023', annee: 'Exercice 2023' }
    ];
    this.exerciceSelectionne = '2024';
    
    this.chargerHistorique();
  }

  chargerHistorique() {
    this.isLoading = true;
    this.cdr.detectChanges(); // 🟢 N-forciw l'affichage ybeddel isLoading l'true
    
    // MOCK DATA: simulation dyal appel API b l'ID dyal l'user w l'exercice selectionné
    setTimeout(() => {
      this.coproprietaire = {
        nom: 'Salma BOULAL',
        status: 'Actif'
      };

      if (this.exerciceSelectionne === '2024') {
        this.totalEncaissements = 4500.00;
        this.totalDepenses = 6000.00;
        this.operations = [
          { date: new Date('2024-03-15'), type: 'Encaissement', description: 'Paiement cotisation T1', montant: 1500, document_url: 'lien_vers_pdf' },
          { date: new Date('2024-02-01'), type: 'Appel de fonds', description: 'Appel de fonds Trimestre 1', montant: 1500, document_url: 'lien_vers_pdf' },
          { date: new Date('2024-04-10'), type: 'Encaissement', description: 'Paiement avance travaux', montant: 3000, document_url: 'lien_vers_pdf' },
          { date: new Date('2024-04-05'), type: 'Appel de fonds', description: 'Appel exceptionnel (Peinture)', montant: 4500, document_url: null }
        ];
      } else {
        this.totalEncaissements = 12000.00;
        this.totalDepenses = 12000.00;
        this.operations = [
           { date: new Date('2023-12-15'), type: 'Encaissement', description: 'Paiement solde', montant: 12000, document_url: 'lien_vers_pdf' },
           { date: new Date('2023-01-01'), type: 'Appel de fonds', description: 'Appel annuel 2023', montant: 12000, document_url: 'lien_vers_pdf' }
        ];
      }

      this.isLoading = false;
      this.cdr.detectChanges(); // 🟢 N-forciw l'affichage yt-updata mn b3d ma jbna data
    }, 500); // 500ms bach tban lik l'animation dyal chargement
  }

  telechargerDoc(url: string) {
    // Logique bach t-téléchargi l'pdf (window.open(url) wla appel API)
    console.log('Téléchargement du document:', url);
    alert('Téléchargement du document initié.');
  }

  retour() {
    this.router.navigate(['/liste-coproprietes']); 
  }
}
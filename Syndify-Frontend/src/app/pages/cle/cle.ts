import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { PageHeader } from '../../components/page-header/page-header';
import { FormsModule } from '@angular/forms';
// خاصك تكييري service جديد سميتو CleRepartitionService و LotService
import { CleRepartitionService } from '../../services/cle-repartition'; 
import { LotService } from '../../services/lot'; 

@Component({
  selector: 'app-cle',
  standalone: true,
  imports: [CommonModule, PageHeader, FormsModule], 
  templateUrl: './cle.html',
})
export class Cle implements OnInit {
  
  proprieteId: string = 'SP-1775215295'; 
  currentTab: 'lots' | 'coproprietaires' = 'lots';
  isLoading: boolean = false;
 activeDropdown: string | number | null = null;

  // الداتا ديال الطابلو
  clesList: any[] = []; // العناوين الفوقانية (Charges générales, etc)
  lignesTableau: any[] = []; // السطورة ديال الطابلو (Lots)
  
  // الداتا ديال المودال
  isAddModalOpen: boolean = false;
  isSaving: boolean = false;
  tousLesLots: any[] = []; // باش نعرضوهم فالمودال ونكتبو ليهم Tantième
  
  cleForm: any = {
    id: null,
    nom_cle: '',
    tantiemes_total: null,
    notes: '',
    unites: [] // غتكون فيها { id_unite, tantieme_applique }
  };

  constructor(
    private cleService: CleRepartitionService,
    private lotService: LotService,
    private cdr: ChangeDetectorRef
  ) {}

  ngOnInit() {
    this.chargerDonnees();
  }

  changerTab(tab: 'lots' | 'coproprietaires') {
    this.currentTab = tab;
  }

  // ==========================================
  // 1. CHARGEMENT DES DONNÉES
  // ==========================================
  chargerDonnees() {
    this.isLoading = true;
    
    // كنجيبو ليستة الشقق باش نعمرو بيهم المودال
    this.lotService.getListe(this.proprieteId).subscribe(res => {
      if (res.success) this.tousLesLots = res.data;
    });

    // كنجيبو ليستة Les clés
    this.cleService.getListe(this.proprieteId).subscribe({
      next: (res: any) => {
        if (res.success) {
          this.clesList = res.data;
          this.formaterTableau(); // هادي لي كتقاد الطابلو
        }
        this.isLoading = false;
        this.cdr.detectChanges();
      },
      error: () => this.isLoading = false
    });
  }

  // هاد الدالة كتشد الداتا من الباكاند وكتردها على شكل "سطورة" ديال الطابلو
  formaterTableau() {
    const lotsMap = new Map();

    this.clesList.forEach(cle => {
      cle.lots.forEach((lot: any) => {
        if (!lotsMap.has(lot.id)) {
          // إيلا كان مالك، كنجيبو سميتو
          const proprio = lot.owners && lot.owners.length > 0 ? lot.owners[0].full_name : '-- Non affecté --';
          
          lotsMap.set(lot.id, {
            id: lot.id,
            numero_porte: lot.numero_porte,
            details: `${lot.type} ${lot.numero_porte} / ${lot.batiment || ''} / ${lot.etage || ''}`,
            coproprietaire: proprio,
            tantiemes: {} // غنخبيو فيه الحسابات ديال كل Clé
          });
        }
        // كنزيدو Tantième لي مطبق على هاد Clé
        lotsMap.get(lot.id).tantiemes[cle.id] = lot.tantieme_applied;
      });
    });

    this.lignesTableau = Array.from(lotsMap.values());
  }

  // ==========================================
  // 2. GESTION DU MODAL
  // ==========================================
  openAddModal(cleAModifier: any = null) {
    if (cleAModifier) {
      this.cleForm = {
        id: cleAModifier.id,
        nom_cle: cleAModifier.nom,
        tantiemes_total: cleAModifier.tantiemes_total,
        notes: cleAModifier.notes,
        unites: cleAModifier.lots.map((l: any) => ({
          id_unite: l.id,
          tantieme_applique: l.tantieme_applied
        }))
      };
    } else {
      // مودال جديد: كنحطو فيه كاع الشقق بـ Tantieme = 0
      this.cleForm = {
        id: null,
        nom_cle: '',
        tantiemes_total: 1000, // مثال
        notes: '',
        unites: this.tousLesLots.map(l => ({
          id_unite: l.id,
          numero_porte: l.numero_porte, // ghir bach n-affichiwha f html
          tantieme_applique: 0
        }))
      };
    }
    
    this.isAddModalOpen = true;
  }

toggleDropdown(cleId: string | number, event: Event) {
    event.stopPropagation();
    this.activeDropdown = this.activeDropdown === cleId ? null : cleId;
  }
 closeDropdown() {
    this.activeDropdown = null;
  }

  // وزيدي حتى هادي إيلا ماكانتش
  supprimerCle(id: number) {
    this.closeDropdown();
    if (confirm('Voulez-vous vraiment supprimer cette clé ?')) {
       // عيطي للـ service ديالك باش تمسحيها وديري chargerDonnees()
    }
  }

  closeAddModal() {
    this.isAddModalOpen = false;
  }

  enregistrerCle() {
    // Vérification Locale
    const totalSaisi = this.cleForm.unites.reduce((sum: number, u: any) => sum + Number(u.tantieme_applique), 0);
    
    if (totalSaisi != this.cleForm.tantiemes_total) {
      alert(`La somme des tantièmes (${totalSaisi}) ne correspond pas au total (${this.cleForm.tantiemes_total}).`);
      return;
    }

    this.isSaving = true;
    
    const payload = {
      propriete_id: this.proprieteId,
      ...this.cleForm
    };

    // Api Call (Ajouter wla Modifier)
    const action = this.cleForm.id 
      ? this.cleService.modifier(payload) 
      : this.cleService.ajouter(payload);

    action.subscribe({
      next: (res: any) => {
        if (res.success) {
          this.closeAddModal();
          this.chargerDonnees(); // Recharge la liste
        }
        this.isSaving = false;
      },
      error: (err) => {
        alert(err.error?.message || "Erreur.");
        this.isSaving = false;
      }
    });
  }
  // هادي كتحسب المجموع ديال Tantième باش تشوف واش مقاد مع Total
  getSommeTantiemes(): number {
    if (!this.cleForm.unites) return 0;
    return this.cleForm.unites.reduce((sum: number, u: any) => sum + (Number(u.tantieme_applique) || 0), 0);
  }
}
import { Component, OnInit, ChangeDetectorRef } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute } from '@angular/router';
import { PageHeader } from '../../components/page-header/page-header'; 
import { LotService } from '../../services/lot';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-gestion-lots',
  standalone: true,
  imports: [CommonModule, PageHeader, FormsModule],
  templateUrl: './gestion-lots.html',
})
export class GestionLots implements OnInit {
  
  proprieteId: string = '';
  lots: any[] = []; 
  isLoading: boolean = false;

  isModalOpen: boolean = false;
  activeDropdown: string | null = null;
  
  lotForm: any = {
    id: null,
    type: 'Appartement',
    batiment: '',
    etage: '',
    numero_porte: ''
  };

  constructor(
    private lotService: LotService,
    private route: ActivatedRoute,
    private cdr: ChangeDetectorRef // 🛑 Injectit cdr hna
  ) {}

  ngOnInit() {
    console.log('🚀 [INIT] Page "Gestion des Lots" chargée.');
    
    // 🛑 KHTWA MO2AQATA: N-7ettou l'ID b-yeddina bash n-testiw
    this.proprieteId = 'SP-1775215295'; 
    
    console.log('📌 ID forcé pour le test:', this.proprieteId);
    this.chargerLots(); 
  }

  // ==========================================
  // 1. CHARGER LA LISTE DES LOTS
  // ==========================================
  chargerLots() {
    if (!this.proprieteId) return;
    
    console.log(`📡 [API GET] Demande de la liste des lots...`);
    this.isLoading = true;
    this.cdr.detectChanges(); // 🛑 Forcer l'affichage dyal l'état de chargement
    
    this.lotService.getListe(this.proprieteId).subscribe({
      next: (res) => {
        console.log('✅ [API SUCCESS] Réponse reçue:', res);
        if (res.success) {
          this.lots = res.data; 
        }
        this.isLoading = false;
        this.cdr.detectChanges(); // 🛑 Forcer l'Angular i-dessiner l-Tableau j-jdid
      },
      error: (err) => {
        console.error("❌ [API ERROR]:", err);
        this.isLoading = false;
        this.cdr.detectChanges(); // 🛑 Forcer l'Angular i-7eyyd loading
      }
    });
  }

  // ==========================================
  // 2. GESTION DES MENUS DROPDOWN
  // ==========================================
  toggleDropdown(numeroId: string, event: Event) {
    event.stopPropagation();
    this.activeDropdown = this.activeDropdown === numeroId ? null : numeroId;
    this.cdr.detectChanges(); // 🛑 Bash l-Menu i-t-7el wla i-t-sed f l-blassa
  }

  closeDropdown() {
    if (this.activeDropdown !== null) {
      this.activeDropdown = null;
      this.cdr.detectChanges(); // 🛑 Forcer l-fermeture
    }
  }

  // ==========================================
  // 3. GESTION DU MODAL (AJOUT & MODIFICATION)
  // ==========================================
  openModal(lotAModifier: any = null) {
    if (lotAModifier) {
      console.log('✏️ [MODAL] MODIFICATION:', lotAModifier);
      this.lotForm = {
        id: lotAModifier.id,
        type: lotAModifier.type,
        batiment: lotAModifier.batiment,
        etage: lotAModifier.etage,
        numero_porte: lotAModifier.numero_porte
      };
    } else {
      console.log('✨ [MODAL] AJOUT.');
      this.lotForm = { id: null, type: 'Appartement', batiment: '', etage: '', numero_porte: '' };
    }
    
    this.isModalOpen = true;
    this.closeDropdown();
    this.cdr.detectChanges(); // 🛑 Forcer l-Modal bash i-ban
  }

  closeModal() {
    console.log('🚪 [MODAL] Fermeture.');
    this.isModalOpen = false;
    this.cdr.detectChanges(); // 🛑 Forcer l-Modal bash i-gheber
  }

  enregistrerLot() {
    const payload = {
      propriete_id: this.proprieteId,
      type: this.lotForm.type,
      batiment: this.lotForm.batiment,
      etage: this.lotForm.etage,
      numero_porte: this.lotForm.numero_porte,
      ...(this.lotForm.id && { lot_id: this.lotForm.id }) 
    };

    console.log('📤 [API POST] Envoi...', payload);

    const action = this.lotForm.id 
      ? this.lotService.modifier(payload) 
      : this.lotService.ajouter(payload);

    action.subscribe({
      next: (res) => {
        console.log('✅ [API SUCCESS] Enregistré:', res);
        if (res.success) {
          this.closeModal(); // Kat-3iyyet aslan 3la detectChanges l-dakhel
          this.chargerLots(); // Kat-3iyyet 3la detectChanges l-dakhel
        }
      },
      error: (err) => {
        console.error("❌ [API ERROR]:", err);
        alert(err.error?.message || "Une erreur est survenue.");
        this.cdr.detectChanges(); // 🛑 Ila w93at erreur t-ban l'alerte
      }
    });
  }

  // ==========================================
  // 4. SUPPRIMER UN LOT
  // ==========================================
  supprimerLot(lot: any) {
    this.closeDropdown();
    
    if (confirm(`Voulez-vous vraiment supprimer le lot N° ${lot.numero_porte} ?`)) {
      console.log(`📡 [API DELETE] Suppression...`);
      
      this.lotService.supprimer(this.proprieteId, lot.id).subscribe({
        next: (res) => {
          console.log('✅ [API SUCCESS] Supprimé:', res);
          if (res.success) {
            this.chargerLots(); 
          }
        },
        error: (err) => {
          console.error("❌ [API ERROR]:", err);
          alert(err.error?.message || "Erreur suppression");
          this.cdr.detectChanges(); 
        }
      });
    }
  }
}
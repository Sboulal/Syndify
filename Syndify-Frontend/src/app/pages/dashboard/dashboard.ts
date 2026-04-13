import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './dashboard.html',
})
export class Dashboard implements OnInit {
  
  isProfileDropdownOpen = false; // 🛑 زدنا هادي باش يخدم المينو الفوقاني

  // 1. معلومات الإقامة (Header)
  residence = {
    nom: 'Résidence les jardins',
    adresse: "123 Rue de l'Exemple, Casablanca, Maroc",
    exercice: 'Du 01 Jan 2024 au 31 Déc 2024',
    periode: '4ème trimestre',
    gerant: 'Jean Dupont (vous)',
    role: 'Syndic, Copropriétaire'
  };

  // 2. أرصدة الملاك
  soldes = [
    { id: 'SU021767485', nom: 'Hannah Arendt', solde: 1200, isNegatif: false },
    { id: 'SU08856992', nom: 'Aline Marcel', solde: -850, isNegatif: true, action: 'Envoyer un rappel' },
    { id: 'SU04877523', nom: 'Chloé Moreau', solde: 320, isNegatif: false },
    { id: 'SU06985441', nom: 'Patricia Allen', solde: -1950, isNegatif: true, action: 'Envoyer un 2ème rappel' },
    { id: 'SU01558744', nom: 'Jean Dupont', solde: 120, isNegatif: false }
  ];

  totalDu = 2800;

  // 3. الميزانية
  budget = {
    totalAnnee: 13560,
    depenses: 10305,
    pourcentage: 76
  };

  // 4. الاستهلاك
  trimestres = [
    { nom: 'Trimestre 1', montant: 3250, pourcentage: 23.96 },
    { nom: 'Trimestre 2', montant: 3300, pourcentage: 24.33 },
    { nom: 'Trimestre 3', montant: 2100, pourcentage: 15.48 },
    { nom: 'Trimestre 4', montant: 1655, pourcentage: 12.20 }
  ];

  constructor() {}
  ngOnInit() {}
}
import { Component, Input } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-page-header',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './page-header.html',
})
export class PageHeader {
  // Les données li ghadi n-siftohom mn l'page (Dashboard, Lots, etc.)
  @Input() parentPath: string = 'Résidence les jardins';
  @Input() currentPath: string = '';
  @Input() title: string = '';
  @Input() statsText: string = '';
  @Input() statsSubText: string = '';
}
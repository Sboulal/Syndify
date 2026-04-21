import { ComponentFixture, TestBed } from '@angular/core/testing';

import { HistoriqueCoproprietaire } from './historique-coproprietaire';

describe('HistoriqueCoproprietaire', () => {
  let component: HistoriqueCoproprietaire;
  let fixture: ComponentFixture<HistoriqueCoproprietaire>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [HistoriqueCoproprietaire]
    })
    .compileComponents();

    fixture = TestBed.createComponent(HistoriqueCoproprietaire);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

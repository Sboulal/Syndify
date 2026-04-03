import { ComponentFixture, TestBed } from '@angular/core/testing';

import { GestionLots } from './gestion-lots';

describe('GestionLots', () => {
  let component: GestionLots;
  let fixture: ComponentFixture<GestionLots>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [GestionLots]
    })
    .compileComponents();

    fixture = TestBed.createComponent(GestionLots);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Simulerbudget } from './simulerbudget';

describe('Simulerbudget', () => {
  let component: Simulerbudget;
  let fixture: ComponentFixture<Simulerbudget>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [Simulerbudget]
    })
    .compileComponents();

    fixture = TestBed.createComponent(Simulerbudget);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

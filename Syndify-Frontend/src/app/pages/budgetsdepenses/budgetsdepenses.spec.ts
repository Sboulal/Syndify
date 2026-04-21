import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Budgetsdepenses } from './budgetsdepenses';

describe('Budgetsdepenses', () => {
  let component: Budgetsdepenses;
  let fixture: ComponentFixture<Budgetsdepenses>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [Budgetsdepenses]
    })
    .compileComponents();

    fixture = TestBed.createComponent(Budgetsdepenses);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

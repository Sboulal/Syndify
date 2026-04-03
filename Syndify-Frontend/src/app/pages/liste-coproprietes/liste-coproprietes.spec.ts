import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ListeCoproprietes } from './liste-coproprietes';

describe('ListeCoproprietes', () => {
  let component: ListeCoproprietes;
  let fixture: ComponentFixture<ListeCoproprietes>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [ListeCoproprietes]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ListeCoproprietes);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

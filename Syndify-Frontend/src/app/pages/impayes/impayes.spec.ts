import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Impayes } from './impayes';

describe('Impayes', () => {
  let component: Impayes;
  let fixture: ComponentFixture<Impayes>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [Impayes]
    })
    .compileComponents();

    fixture = TestBed.createComponent(Impayes);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

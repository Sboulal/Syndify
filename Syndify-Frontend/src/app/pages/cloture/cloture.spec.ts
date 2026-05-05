import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Cloture } from './cloture';

describe('Cloture', () => {
  let component: Cloture;
  let fixture: ComponentFixture<Cloture>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [Cloture]
    })
    .compileComponents();

    fixture = TestBed.createComponent(Cloture);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Cloturedetails } from './cloturedetails';

describe('Cloturedetails', () => {
  let component: Cloturedetails;
  let fixture: ComponentFixture<Cloturedetails>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [Cloturedetails]
    })
    .compileComponents();

    fixture = TestBed.createComponent(Cloturedetails);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

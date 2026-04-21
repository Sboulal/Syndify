import { ComponentFixture, TestBed } from '@angular/core/testing';

import { Appelsfonds } from './appelsfonds';

describe('Appelsfonds', () => {
  let component: Appelsfonds;
  let fixture: ComponentFixture<Appelsfonds>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [Appelsfonds]
    })
    .compileComponents();

    fixture = TestBed.createComponent(Appelsfonds);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

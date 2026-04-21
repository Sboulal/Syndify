import { ComponentFixture, TestBed } from '@angular/core/testing';

import { AppelsFondsDetails } from './appels-fonds-details';

describe('AppelsFondsDetails', () => {
  let component: AppelsFondsDetails;
  let fixture: ComponentFixture<AppelsFondsDetails>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [AppelsFondsDetails]
    })
    .compileComponents();

    fixture = TestBed.createComponent(AppelsFondsDetails);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});

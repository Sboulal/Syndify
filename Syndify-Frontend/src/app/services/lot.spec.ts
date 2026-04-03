import { TestBed } from '@angular/core/testing';

import { Lot } from './lot';

describe('Lot', () => {
  let service: Lot;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(Lot);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});

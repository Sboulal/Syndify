import { TestBed } from '@angular/core/testing';

import { Coproprietaire } from './coproprietaire';

describe('Coproprietaire', () => {
  let service: Coproprietaire;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(Coproprietaire);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});

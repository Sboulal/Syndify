import { TestBed } from '@angular/core/testing';

import { Exercice } from './exercice';

describe('Exercice', () => {
  let service: Exercice;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(Exercice);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});

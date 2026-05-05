import { TestBed } from '@angular/core/testing';

import { Cloture } from './cloture';

describe('Cloture', () => {
  let service: Cloture;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(Cloture);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});

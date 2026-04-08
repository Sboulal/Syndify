import { TestBed } from '@angular/core/testing';

import { CleRepartition } from './cle-repartition';

describe('CleRepartition', () => {
  let service: CleRepartition;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(CleRepartition);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});

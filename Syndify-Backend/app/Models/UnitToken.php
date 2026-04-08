<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class UnitToKey extends Pivot 
{
    protected $table = 'unit_to_key';

    protected $fillable = [
        'unit_id',
        'key_id',
        'tantieme'
    ];
}
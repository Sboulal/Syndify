<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

// Kndirou extends Pivot machi Model, 7it hadi table intermédiaire
class UserOwnerUnit extends Pivot
{
    protected $table = 'user_owner_unit';

    protected $fillable = [
        'user_id',
        'unit_id',
        'status' // 1: Actif, 2: Inactif
    ];
}
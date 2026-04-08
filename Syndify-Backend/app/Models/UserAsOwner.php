<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAsOwner extends Model
{
    use HasFactory;

    protected $table = 'user_as_owner';

    protected $fillable = [
        'user_id',
        'propriete_id',
        'status' // 0: En attente, 1: Actif, 2: Inactif
    ];

    // Bach njbdou les infos dyal User b-shoula
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Bach njbdou la Propriété
    public function propriete()
    {
        return $this->belongsTo(Copropriete::class, 'propriete_id');
    }
}
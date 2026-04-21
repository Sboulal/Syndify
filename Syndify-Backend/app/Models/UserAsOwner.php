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
        'status', // 0: En attente, 1: Actif, 2: Inactif
        'balance_prev', // 🟢 Zidnahom hna bach l'Update ykheddemhom
        'balance_new'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function propriete()
    {
        return $this->belongsTo(Copropriete::class, 'propriete_id');
    }
}
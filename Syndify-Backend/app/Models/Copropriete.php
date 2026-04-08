<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Copropriete extends Model
{
    use HasFactory;

    // T2kdi wach table m-semyaha proprietes awla coproprietes
    protected $table = 'proprietes';

    // Kima User, 7ta l'Copropriété 3ndha Identifiant m-khesess b7al "SP-time()"
    protected $primaryKey = 'identifier';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'identifier',
        'name',
        'siret',
        'city',
        'country',
    
    ];

    // L'Relation m3a les Lots
    public function lots()
    {
        return $this->hasMany(Lot::class, 'propriete_id');
    }

    // L'Relation Globale m3a les Copropriétaires (Via table user_as_owner)
    public function coproprietaires()
    {
        return $this->belongsToMany(User::class, 'user_as_owner', 'propriete_id', 'user_id')
                    ->withPivot('status')
                    ->withTimestamps();
    }
}
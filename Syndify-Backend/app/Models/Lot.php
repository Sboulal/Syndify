<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lot extends Model
{
    use HasFactory;

    protected $table = 'units'; 

    protected $fillable = [
        'propriete_id',
        'type',
        'batiment',
        'etage',
        'numero_porte',
        'status'
    ];

    public function copropriete()
    {
        return $this->belongsTo(Copropriete::class, 'propriete_id');
    }

    // 🟢 Relation s7i7a b les ID standards
    public function owners()
    {
        return $this->belongsToMany(User::class, 'user_owner_unit', 'unit_id', 'user_id')
                    ->withPivot('status')
                    ->withTimestamps();
    }

    public function clesRepartition()
    {
        return $this->belongsToMany(CleRepartition::class, 'unit_to_key', 'unit_id', 'key_id')
                    ->withPivot('tantieme')
                    ->withTimestamps();
    }
}
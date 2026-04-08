<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lot extends Model
{
    use HasFactory;

    // T2kdi mn smyat l'table li 3ndk f BDD (units awla lots)
    protected $table = 'units'; 

    protected $fillable = [
        'propriete_id',
        'type',
        'batiment',
        'etage',
        'numero_porte',
        'status' // Ila kayn chi status l-lot f l'bdd
    ];

    // L'Relation m3a la Copropriété
    public function copropriete()
    {
        return $this->belongsTo(Copropriete::class, 'propriete_id');
    }

    // L'Relation Many-to-Many m3a les Propriétaires (Users) 
    // kat-douz mn l'table 'user_owner_unit'
    public function owners()
    {
        return $this->belongsToMany(User::class, 'user_owner_unit', 'unit_id', 'user_id')
                    ->withPivot('status') // Bach n-qdro njbdou wach actif wla inactif 3la had l'lot
                    ->withTimestamps();
    }

    // L'Relation Many-to-Many m3a les Clés de Répartition
    public function clesRepartition()
    {
        return $this->belongsToMany(CleRepartition::class, 'unit_to_key', 'unit_id', 'key_id')
                    ->withPivot('tantieme') // Bach njbdou l'Tantième
                    ->withTimestamps();
    }
}
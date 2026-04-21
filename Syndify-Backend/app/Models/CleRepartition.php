<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CleRepartition extends Model
{
    use HasFactory;

    protected $table = 'cle_repartitions';

    protected $fillable = [
        'propriete_id',
        'nom',
        'tantiemes_total',
        'notes'
    ];

    public function copropriete()
    {
        return $this->belongsTo(Copropriete::class, 'propriete_id');
    }

  public function lots()
{
    return $this->belongsToMany(Lot::class, 'unit_to_key', 'cle_repartition_id', 'unit_id')
                ->withPivot('tantieme')
                ->withTimestamps();
}
}
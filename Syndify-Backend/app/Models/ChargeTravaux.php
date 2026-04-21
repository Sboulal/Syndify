<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChargeTravaux extends Model
{
    use HasFactory;

    protected $table = 'charges_travaux';

    // 🟢 Configuration dyal l-Primary Key (String)
    protected $primaryKey = 'sct_identifier';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'sct_identifier',
        'se_identifier',
        'budget',
        'total_encaissements',
        'total_depenses'
    ];

    // ================= Relations =================

    public function exercice()
    {
        return $this->belongsTo(Exercice::class, 'se_identifier', 'se_identifier');
    }

    // Relation Many-to-Many m3a Clé de répartition (via table pivot bt_to_key)
    public function clesRepartition()
    {
        return $this->belongsToMany(CleRepartition::class, 'bt_to_key', 'sct_identifier', 'cle_repartition_id')
                    ->withPivot(['budget', 'depenses'])
                    ->withTimestamps();
    }
}
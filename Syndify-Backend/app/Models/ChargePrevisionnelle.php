<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChargePrevisionnelle extends Model
{
    use HasFactory;

    protected $table = 'charges_previsionnelles';

    // 🟢 Configuration dyal l-Primary Key (String)
    protected $primaryKey = 'scp_identifier';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'scp_identifier',
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

    // Relation Many-to-Many m3a Clé de répartition (via table pivot bp_to_key)
    public function clesRepartition()
    {
        return $this->belongsToMany(CleRepartition::class, 'bp_to_key', 'scp_identifier', 'cle_repartition_id')
                    ->withPivot(['budget', 'depenses'])
                    ->withTimestamps();
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exercice extends Model
{
    use HasFactory;

    protected $table = 'exercices';

    // 🟢 Configuration dyal l-Primary Key (String)
    protected $primaryKey = 'se_identifier';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'se_identifier',
        'sp_identifier',
        'start_date',
        'end_date',
        'period',
        'status'
    ];

    // ================= Relations =================

    // L-Exercice تابع l-Propriété (Résidence)
    public function propriete()
    {
        return $this->belongsTo(Copropriete::class, 'sp_identifier', 'id');
    }

    // L-Exercice 3ndo Budget Prévisionnel wa7ed
    public function chargePrevisionnelle()
    {
        return $this->hasOne(ChargePrevisionnelle::class, 'se_identifier', 'se_identifier');
    }

    // L-Exercice 3ndo Budget Travaux wa7ed
    public function chargeTravaux()
    {
        return $this->hasOne(ChargeTravaux::class, 'se_identifier', 'se_identifier');
    }
}
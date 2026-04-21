<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppfToOwner extends Model
{
    use HasFactory;

    protected $table = 'appf_to_owner';

    protected $fillable = [
        'af_identifier', 'user_id', 'document_id', 'montant_du', 'solde_avant'
    ];
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppelFonds extends Model
{
    use HasFactory;

    protected $table = 'appels_fonds';

    protected $fillable = [
        'af_identifier', 'se_identifier', 'cle_repartition_id', 
        'type_charge', 'sub_type', 'title', 'amount', 'due_date', 
        'is_generated', 'is_sent', 'number_generated', 'number_sent'
    ];
}
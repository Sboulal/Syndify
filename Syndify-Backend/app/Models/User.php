<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // 👈 Darouri l'token

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // 1. Configuration dyal l'identifiant personnalisé
    protected $primaryKey = 'identifier';
    public $incrementing = false;
    protected $keyType = 'string';

    // 2. Les champs li 3ndna f l'cahier des charges
    protected $fillable = [
        'identifier', 
        'full_name', 
        'email', 
        'phone', 
        'password', 
        'activation_code', 
        'otp_expires_at',
        'agreed_on_terms', 
        'mailing_subs', 
        'status'
    ];

    // 3. Les champs li khasshom i-tbeynou (Security)
    protected $hidden = [
        'password', 
        'remember_token', 
        'activation_code'
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'password' => 'hashed',
            'agreed_on_terms' => 'boolean',
            'mailing_subs' => 'boolean',
        ];
    }
}
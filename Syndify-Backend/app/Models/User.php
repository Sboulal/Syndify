<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    // 🟢 7iydna l'override dyal primaryKey, daba ghaykhdem b 'id' oumatiqement

    protected $fillable = [
        'full_name', 
        'email', 
        'tel',
        'password', 
        'activation_code', 
        'otp_expires_at',
        'agreed_on_terms', 
        'mailing_subs', 
        'status'
    ];

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

    public function coproprietes()
    {
        return $this->belongsToMany(Copropriete::class, 'user_as_owner', 'user_id', 'propriete_id')
                    ->withPivot('status', 'balance_prev', 'balance_new')
                    ->withTimestamps();
    }

    // L'Relation m3a Lots
    public function lots()
    {
        return $this->belongsToMany(Lot::class, 'user_owner_unit', 'user_id', 'unit_id')
                    ->withPivot('status')
                    ->withTimestamps();
    }
}
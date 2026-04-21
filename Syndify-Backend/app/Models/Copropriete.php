<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Copropriete extends Model
{
    use HasFactory;

    protected $table = 'proprietes';

    // 🟢 1. L-Primary key smitou 'id' kima f l-migration (machi identifier)
    protected $primaryKey = 'id';
    
    // 🟢 2. Khass ngolo l-Laravel bli had l-ID machi auto-increment (7it fih SP-...)
    public $incrementing = false;
    protected $keyType = 'string';

    // 🟢 3. L-fillable khass ykounou fih Smiyat li kaynin f l-migration b dbt
    protected $fillable = [
        'id',
        'nom',
        'city',
        'address'
    ];

    public function lots()
    {
        return $this->hasMany(Lot::class, 'propriete_id');
    }

    public function coproprietaires()
    {
        return $this->belongsToMany(User::class, 'user_as_owner', 'propriete_id', 'user_id')
                    ->withPivot('status')
                    ->withTimestamps();
    }
}
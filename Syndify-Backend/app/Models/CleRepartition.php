<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CleRepartition extends Model
{
    use HasFactory;

    // سمية الطابلو فـ Base de données
    protected $table = 'cle_repartitions';

    // الخانات اللي مسموح لينا نعمروهم
    protected $fillable = [
        'propriete_id',
        'nom',
        'tantiemes_total',
        'notes'
    ];

    // ==========================================
    // العلاقات (Relationships)
    // ==========================================

    // 1. علاقة مع الإقامة (كل Clé تابع لإقامة وحدة)
    public function copropriete()
    {
        return $this->belongsTo(Copropriete::class, 'propriete_id');
    }

    // 2. علاقة مع الشقق (Lots / Units)
    // هادي Many-to-Many حيت الـ Clé يقدر يطبق على بزاف د الشقق، والشقة يقدر يكونو فيها بزاف د les Clés
    public function lots()
    {
        // 'unit_to_key' هو الطابلو اللي كيجمع بيناتهم
        // 'key_id' هو الـ ID ديال هاد الـ Clé فداك الطابلو
        // 'unit_id' هو الـ ID ديال الشقة فداك الطابلو
        return $this->belongsToMany(Lot::class, 'unit_to_key', 'key_id', 'unit_id')
                    ->withPivot('tantieme') // كنجيبو حتى الـ Tantième اللي فهاد الطابلو
                    ->withTimestamps();
    }
}
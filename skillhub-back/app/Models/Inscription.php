<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_apprenant',
        'id_formation',
        'status',
    ];

    /**
     * Liaison : Une inscription appartient à un utilisateur (l'apprenant).
     */
    public function apprenant()
    {
        return $this->belongsTo(User::class, 'id_apprenant');
    }

    /**
     * Liaison : Une inscription concerne une formation spécifique.
     */
    public function formation()
    {
        return $this->belongsTo(Formation::class, 'id_formation');
    }
}

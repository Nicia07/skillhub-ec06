<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Inscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_apprenant', 
        'id_formation', 
        'status'
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
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Formation extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'price',
        'duration',
        'level',
        'categorie',
        'ville',
        'photo',
        'user_id',
    ];

    // Relation avec le formateur. FK explicite : Eloquent déduit par défaut
    // la clé étrangère du nom de la méthode ("formateur_id"), pas du modèle
    // lié — il faut donc préciser 'user_id' explicitement.
    public function formateur()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject; // Import crucial pour le Token JWT

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public $timestamps = false;

    /**
     * Les attributs qui peuvent être remplis massivement (Mass Assignment).
     *
     * @var array<string>
     */
    protected $fillable = [
        'pseudo',
        'email',
        'password',
        'role',
    ];

    /**
     * Les attributs à cacher lors de la conversion en JSON (Sécurité).
     *
     * @var array<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Casting des attributs (Types de données).
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Liaison : Un utilisateur (formateur) peut posséder plusieurs formations.
     */
    public function formations()
    {
        return $this->hasMany(Formation::class);
    }

    /* =========================================================================
       MÉTHODES REQUISES PAR JWT (Tymon JWTAuth)
       ========================================================================= */

    /**
     * Récupère l'identifiant unique qui sera stocké dans le "subject" du JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Permet d'ajouter des informations personnalisées dans le Token.
     */
    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role, // Ajoute le rôle (apprenant/formateur) dans le payload du token
        ];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Signalement extends Model
{
    protected $fillable = [
        'formation_id',
        'user_id',
        'motif',
        'description',
        'statut',
    ];

    public function formation()
    {
        return $this->belongsTo(Formation::class, 'formation_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

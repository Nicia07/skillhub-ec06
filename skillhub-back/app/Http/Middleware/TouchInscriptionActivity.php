<?php

namespace App\Http\Middleware;

use App\Models\Inscription;
use Closure;
use Illuminate\Http\Request;

class TouchInscriptionActivity
{
    // Met a jour last_activity_at a chaque acces d'un apprenant a une formation
    // qu'il suit, pour detecter l'inactivite (regle des 30 jours).
    public function handle(Request $request, Closure $next)
    {
        try {
            $user = auth('api')->user();
        } catch (\Throwable $e) {
            $user = null;
        }

        if ($user && $user->role === 'apprenant') {
            Inscription::where('id_apprenant', $user->id)
                ->where('id_formation', $request->route('id'))
                ->where('status', 'en cours')
                ->update(['last_activity_at' => now()]);
        }

        return $next($request);
    }
}

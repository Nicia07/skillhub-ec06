<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role)
    {
        if (! $request->user() || $request->user()->role !== $role) {
            return response()->json(['message' => 'Accès non autorisé pour ce rôle'], 403);
        }

        return $next($request);
    }
}

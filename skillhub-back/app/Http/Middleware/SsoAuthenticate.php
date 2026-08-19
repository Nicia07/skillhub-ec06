<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SsoAuthenticate
{
    /**
     * Delegue entierement la validation du token au microservice Spring Boot
     * SSO (GET /api/auth/validate) plutot que de le decoder localement :
     * l'authentification reste la responsabilite exclusive du microservice.
     */
    public function handle(Request $request, Closure $next)
    {
        $authorization = $request->header('Authorization');

        if (! $authorization || ! str_starts_with($authorization, 'Bearer ')) {
            return response()->json(['message' => 'Token SSO absent'], 401);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $authorization,
            ])->timeout(config('services.sso.timeout'))
                ->get(rtrim(config('services.sso.base_url'), '/').'/api/auth/validate');
        } catch (ConnectionException $e) {
            return response()->json(['message' => 'Microservice SSO indisponible'], 503);
        }

        if ($response->failed() || ! ($response->json('valid'))) {
            return response()->json(['message' => 'Token SSO invalide ou expire'], 401);
        }

        $request->attributes->set('sso_user', [
            'email' => $response->json('email'),
            'role' => $response->json('role'),
        ]);

        return $next($request);
    }
}

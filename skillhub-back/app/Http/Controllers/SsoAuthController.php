<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SsoAuthController extends Controller
{
    /**
     * Delegue l'authentification au microservice Spring Boot SSO : Laravel
     * presente la Master Key (secret partage entre services de confiance)
     * et transmet les identifiants de l'utilisateur, puis renvoie tel quel
     * le JWT emis par le microservice au client.
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        try {
            $response = Http::withHeaders([
                'X-Master-Key' => config('services.sso.master_key'),
            ])->timeout(config('services.sso.timeout'))
                ->post(rtrim(config('services.sso.base_url'), '/') . '/api/auth/login', $validated);
        } catch (ConnectionException $e) {
            return response()->json(['message' => 'Microservice SSO indisponible'], 503);
        }

        if ($response->failed()) {
            return response()->json(
                $response->json() ?? ['message' => 'Authentification SSO impossible'],
                $response->status()
            );
        }

        return response()->json($response->json());
    }
}

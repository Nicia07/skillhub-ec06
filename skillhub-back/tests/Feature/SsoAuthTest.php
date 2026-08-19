<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SsoAuthTest extends TestCase
{
    /**
     * Login SSO : Laravel relaie la reponse (JWT) du microservice quand elle est valide.
     */
    public function test_login_sso_relaie_le_jwt_du_microservice()
    {
        Http::fake([
            '*/api/auth/login' => Http::response([
                'tokenType' => 'Bearer',
                'accessToken' => 'fake-jwt-token',
                'expiresInMs' => 3600000,
                'email' => 'apprenant@skillhub.test',
                'role' => 'apprenant',
            ], 200),
        ]);

        $response = $this->postJson('/api/sso/login', [
            'email' => 'apprenant@skillhub.test',
            'password' => 'Apprenant123!',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('accessToken', 'fake-jwt-token');

        Http::assertSent(function ($request) {
            return $request->hasHeader('X-Master-Key')
                && str_contains($request->url(), '/api/auth/login');
        });
    }

    /**
     * Login SSO : une reponse d'erreur du microservice (ex: mauvais mot de passe) est relayee telle quelle.
     */
    public function test_login_sso_relaie_une_erreur_du_microservice()
    {
        Http::fake([
            '*/api/auth/login' => Http::response(['message' => 'Identifiants invalides'], 401),
        ]);

        $response = $this->postJson('/api/sso/login', [
            'email' => 'apprenant@skillhub.test',
            'password' => 'mauvais-mot-de-passe',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Route protegee par le middleware "sso" : accessible seulement si le
     * microservice valide le token transmis dans l'en-tete Authorization.
     */
    public function test_route_protegee_sso_accessible_avec_token_valide()
    {
        Http::fake([
            '*/api/auth/validate' => Http::response([
                'valid' => true,
                'email' => 'formateur@skillhub.test',
                'role' => 'formateur',
            ], 200),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer un-token-valide',
        ])->getJson('/api/sso/profile');

        $response->assertStatus(200);
        $response->assertJsonPath('user.email', 'formateur@skillhub.test');
    }

    /**
     * Route protegee par le middleware "sso" : refusee si le microservice invalide le token.
     */
    public function test_route_protegee_sso_refusee_avec_token_invalide()
    {
        Http::fake([
            '*/api/auth/validate' => Http::response(['message' => 'Token JWT invalide ou expire'], 401),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer un-token-invalide',
        ])->getJson('/api/sso/profile');

        $response->assertStatus(401);
    }

    /**
     * Route protegee par le middleware "sso" : refusee sans en-tete Authorization.
     */
    public function test_route_protegee_sso_refusee_sans_token()
    {
        $response = $this->getJson('/api/sso/profile');

        $response->assertStatus(401);
    }
}

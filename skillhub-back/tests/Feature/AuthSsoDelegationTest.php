<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AuthSsoDelegationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Inscription : le microservice SSO cree le compte credential, Laravel
     * cree ensuite la ligne "users" (donnees metier) et emet son propre JWT.
     */
    public function test_inscription_delegue_la_creation_du_compte_au_microservice_sso()
    {
        Http::fake([
            '*/api/auth/register' => Http::response([
                'accessToken' => 'jwt-sso-inutilise-par-laravel',
                'email' => 'nouveau@skillhub.test',
                'role' => 'apprenant',
            ], 201),
        ]);

        $response = $this->postJson('/api/register', [
            'pseudo' => 'Nouveau',
            'email' => 'nouveau@skillhub.test',
            'password' => 'motdepasse123',
            'role' => 'apprenant',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['user', 'token']);

        $this->assertDatabaseHas('users', ['email' => 'nouveau@skillhub.test']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/api/auth/register')
                && $request->hasHeader('X-Master-Key');
        });
    }

    /**
     * Inscription : si le microservice refuse (ex: email deja pris cote SSO),
     * Laravel ne cree pas d'utilisateur local et relaie l'erreur.
     */
    public function test_inscription_echoue_si_le_microservice_sso_refuse()
    {
        Http::fake([
            '*/api/auth/register' => Http::response(['message' => 'Un compte existe deja avec cet email'], 409),
        ]);

        $response = $this->postJson('/api/register', [
            'pseudo' => 'Nouveau',
            'email' => 'existe-deja@skillhub.test',
            'password' => 'motdepasse123',
            'role' => 'apprenant',
        ]);

        $response->assertStatus(409);
        $this->assertDatabaseMissing('users', ['email' => 'existe-deja@skillhub.test']);
    }

    /**
     * Connexion : le microservice SSO valide les identifiants, Laravel
     * retrouve l'utilisateur local correspondant et emet son propre JWT.
     */
    public function test_connexion_delegue_la_verification_des_identifiants_au_microservice_sso()
    {
        $user = User::factory()->create([
            'email' => 'formateur@skillhub.test',
            'role' => 'formateur',
        ]);

        Http::fake([
            '*/api/auth/login' => Http::response([
                'accessToken' => 'jwt-sso-inutilise-par-laravel',
                'email' => $user->email,
                'role' => 'formateur',
            ], 200),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'peu-importe-verifie-par-le-sso',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('user.id', $user->id);
        $response->assertJsonStructure(['token']);
    }

    /**
     * Connexion : si le microservice rejette les identifiants, Laravel
     * repond 401 sans emettre de token.
     */
    public function test_connexion_echoue_si_le_microservice_sso_rejette_les_identifiants()
    {
        User::factory()->create(['email' => 'formateur@skillhub.test']);

        Http::fake([
            '*/api/auth/login' => Http::response(['message' => 'Identifiants invalides'], 401),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'formateur@skillhub.test',
            'password' => 'mauvais-mot-de-passe',
        ]);

        $response->assertStatus(401);
    }
}

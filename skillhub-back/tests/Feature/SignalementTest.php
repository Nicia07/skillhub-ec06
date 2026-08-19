<?php

namespace Tests\Feature;

use App\Models\Formation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignalementTest extends TestCase
{
    use RefreshDatabase;

    private function creerFormation(): Formation
    {
        $formateur = User::factory()->create([
            'role' => 'formateur',
            'pseudo' => 'FormateurSignalement',
            'email' => 'formateur.signalement@test.com',
        ]);

        return Formation::create([
            'title' => 'Formation à signaler',
            'description' => 'Formation utilisée pour les tests de signalement',
            'price' => 100.00,
            'duration' => 10,
            'level' => 'beginner',
            'user_id' => $formateur->id,
        ]);
    }

    /**
     * Premier Test : Vérifier qu'une requête POST sans token retourne 401.
     */
    public function test_signalement_sans_token_retourne_401()
    {
        $formation = $this->creerFormation();

        $response = $this->postJson("/api/formations/{$formation->id}/signalements", [
            'motif' => 'erreur_technique',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Deuxième Test : Vérifier qu'une requête POST valide avec un utilisateur
     * authentifié crée le signalement et retourne 201.
     */
    public function test_signalement_valide_avec_token_cree_et_retourne_201()
    {
        $formation = $this->creerFormation();

        $apprenant = User::factory()->create([
            'role' => 'apprenant',
            'pseudo' => 'ApprenantSignaleur',
            'email' => 'apprenant.signalement@test.com',
        ]);

        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($apprenant);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson("/api/formations/{$formation->id}/signalements", [
            'motif' => 'erreur_technique',
            'description' => 'le module 2 affiche une erreur 500 lors du chargement.',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('signalements', [
            'formation_id' => $formation->id,
            'user_id' => $apprenant->id,
            'motif' => 'erreur_technique',
        ]);
    }

    /**
     * Troisième Test : Vérifier qu'un second signalement sur la même formation
     * par le même utilisateur retourne 422.
     */
    public function test_second_signalement_meme_formation_meme_utilisateur_retourne_422()
    {
        $formation = $this->creerFormation();

        $apprenant = User::factory()->create([
            'role' => 'apprenant',
            'pseudo' => 'ApprenantRecidiviste',
            'email' => 'apprenant.recidive@test.com',
        ]);

        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($apprenant);

        // Premier signalement : doit réussir.
        $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson("/api/formations/{$formation->id}/signalements", [
            'motif' => 'contenu_inapproprie',
        ])->assertStatus(201);

        // Second signalement, même formation, même utilisateur : doit échouer.
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson("/api/formations/{$formation->id}/signalements", [
            'motif' => 'autre',
        ]);

        $response->assertStatus(422);
    }
}

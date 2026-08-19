<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormationTest extends TestCase
{
    // RefreshDatabase ordonne à Laravel d'exécuter toutes tes migrations 
    // dans une base de données temporaire en mémoire avant de lancer les tests.
    use RefreshDatabase;

    /**
     * Premier Test : Vérifier qu'un visiteur non connecté est rejeté (401)
     */
    public function test_creation_formation_sans_token_echoue()
    {
        // On simule une requête POST sans header d'autorisation
        $response = $this->postJson('/api/formations', [
            'title' => 'Formation pirate',
            'description' => 'Cette requête ne devrait pas passer',
        ]);

        // ICi le statut retourné DOIT être 401 (Non autorisé)
        $response->assertStatus(401);
    }

    /**
     * Deuxime Test : Vérifier qu'un formateur connecté avec un token valide réussit (201)
     */
    public function test_creation_formation_avec_token_reussit()
    {
        // On crée un faux utilisateur dans la base temporaire
        // On force les champs pour correspondre à ta table (pseudo et role)
        $formateur = User::factory()->create([
            'role' => 'formateur',
            'pseudo' => 'FormateurTest',
            'email' => 'test@formateur.com',
        ]);

        // On génère un vrai token JWT pour cet utilisateur
        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($formateur);

        // On prépare un faux formulaire complet
        $payload = [
            'title' => 'Formation React Avancé',
            'description' => 'Maîtrisez les tests automatisés',
            'price' => 1500.00,
            'duration' => 20,
            'level' => 'advanced',
            'categorie' => 'Front-end',
            'ville' => 'En ligne',
        ];

        // On envoie la requête AVEC le token dans l'en-tête
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/formations', $payload);

        // On affirme que la création a réussi (201 Created)
        $response->assertStatus(201);

        // Vérification: que Laravel l'a bien inséré dans la base de données
        $this->assertDatabaseHas('formations', [
            'title' => 'Formation React Avancé',
            'user_id' => $formateur->id
        ]);
    }

    /**
     * Troisième Test : Vérifier qu'un token présent mais invalide est rejeté (403),
     * distinct du cas "token absent" (401) du premier test.
     */
    public function test_creation_formation_avec_token_invalide_echoue_403()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ceci-nest-pas-un-jwt-valide',
        ])->postJson('/api/formations', [
            'title' => 'Formation pirate',
            'description' => 'Token falsifié',
        ]);

        $response->assertStatus(403);
    }
}
<?php

namespace Tests\Feature;

use App\Models\Formation;
use App\Models\Inscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InscriptionLimiteTest extends TestCase
{
    use RefreshDatabase;

    private function creerFormation(User $formateur): Formation
    {
        return Formation::create([
            'title' => 'Formation '.uniqid(),
            'description' => 'Description de test',
            'price' => 100,
            'duration' => 10,
            'level' => 'beginner',
            'user_id' => $formateur->id,
        ]);
    }

    private function tokenPour(User $user): string
    {
        return \Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);
    }

    /**
     * Un apprenant déjà inscrit à 5 formations actives ne peut pas en suivre une 6e.
     */
    public function test_inscription_refusee_quand_la_limite_de_5_est_atteinte()
    {
        $formateur = User::factory()->create(['role' => 'formateur']);
        $apprenant = User::factory()->create(['role' => 'apprenant']);
        $token = $this->tokenPour($apprenant);

        // L'apprenant suit déjà 5 formations actives.
        for ($i = 0; $i < 5; $i++) {
            Inscription::create([
                'id_apprenant' => $apprenant->id,
                'id_formation' => $this->creerFormation($formateur)->id,
                'status' => 'en cours',
            ]);
        }

        $sixiemeFormation = $this->creerFormation($formateur);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/inscriptions', [
            'id_formation' => $sixiemeFormation->id,
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('message', fn ($message) => str_contains($message, '5'));

        $this->assertDatabaseMissing('inscriptions', [
            'id_apprenant' => $apprenant->id,
            'id_formation' => $sixiemeFormation->id,
        ]);
    }

    /**
     * Une formation "terminée" ne compte pas dans la limite des 5 inscriptions actives.
     */
    public function test_inscription_acceptee_si_une_formation_terminee_libere_de_la_place()
    {
        $formateur = User::factory()->create(['role' => 'formateur']);
        $apprenant = User::factory()->create(['role' => 'apprenant']);
        $token = $this->tokenPour($apprenant);

        for ($i = 0; $i < 4; $i++) {
            Inscription::create([
                'id_apprenant' => $apprenant->id,
                'id_formation' => $this->creerFormation($formateur)->id,
                'status' => 'en cours',
            ]);
        }

        // Une 5e formation, déjà terminée : ne doit pas bloquer une nouvelle inscription.
        Inscription::create([
            'id_apprenant' => $apprenant->id,
            'id_formation' => $this->creerFormation($formateur)->id,
            'status' => 'terminée',
        ]);

        $nouvelleFormation = $this->creerFormation($formateur);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson('/api/inscriptions', [
            'id_formation' => $nouvelleFormation->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('inscriptions', [
            'id_apprenant' => $apprenant->id,
            'id_formation' => $nouvelleFormation->id,
        ]);
    }
}

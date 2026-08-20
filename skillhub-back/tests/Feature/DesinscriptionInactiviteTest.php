<?php

namespace Tests\Feature;

use App\Models\Formation;
use App\Models\Inscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesinscriptionInactiviteTest extends TestCase
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

    /**
     * Une inscription inactive depuis 31 jours est desinscrite apres la commande.
     */
    public function test_desinscription_effective_pour_inscription_inactive_depuis_31_jours()
    {
        $formateur = User::factory()->create(['role' => 'formateur']);
        $apprenant = User::factory()->create(['role' => 'apprenant']);
        $formation = $this->creerFormation($formateur);

        $inscription = Inscription::create([
            'id_apprenant' => $apprenant->id,
            'id_formation' => $formation->id,
            'status' => 'en cours',
            'last_activity_at' => now()->subDays(31),
        ]);

        $this->artisan('app:desinscription-inactivite')
            ->expectsOutputToContain('1 desinscription(s)')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('inscriptions', ['id' => $inscription->id]);
    }

    /**
     * Une inscription active recemment (moins de 30 jours) n'est pas desinscrite.
     */
    public function test_inscription_active_recemment_nest_pas_desinscrite()
    {
        $formateur = User::factory()->create(['role' => 'formateur']);
        $apprenant = User::factory()->create(['role' => 'apprenant']);
        $formation = $this->creerFormation($formateur);

        $inscription = Inscription::create([
            'id_apprenant' => $apprenant->id,
            'id_formation' => $formation->id,
            'status' => 'en cours',
            'last_activity_at' => now()->subDays(5),
        ]);

        $this->artisan('app:desinscription-inactivite')->assertExitCode(0);

        $this->assertDatabaseHas('inscriptions', ['id' => $inscription->id]);
    }

    /**
     * Une inscription sans aucune activite enregistree (last_activity_at NULL) est desinscrite.
     */
    public function test_inscription_sans_activite_enregistree_est_desinscrite()
    {
        $formateur = User::factory()->create(['role' => 'formateur']);
        $apprenant = User::factory()->create(['role' => 'apprenant']);
        $formation = $this->creerFormation($formateur);

        $inscription = Inscription::create([
            'id_apprenant' => $apprenant->id,
            'id_formation' => $formation->id,
            'status' => 'en cours',
            'last_activity_at' => null,
        ]);

        $this->artisan('app:desinscription-inactivite')->assertExitCode(0);

        $this->assertDatabaseMissing('inscriptions', ['id' => $inscription->id]);
    }
}

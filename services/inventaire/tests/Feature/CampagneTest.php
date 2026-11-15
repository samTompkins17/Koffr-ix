<?php

namespace Tests\Feature;

use App\Models\Campagne;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\Support\UtilisateurJeton;
use Tests\TestCase;

class CampagneTest extends TestCase
{
    use RefreshDatabase;

    private function jeton(string $role): string
    {
        return JWTAuth::fromSubject(new UtilisateurJeton(role: $role));
    }

    private function donneesCampagne(): array
    {
        return [
            'nom' => 'Inventaire 2026',
            'date_debut' => '2026-01-15',
            'date_fin' => '2026-12-15',
            'structure_id' => '00000000-0000-4000-8000-000000000001',
        ];
    }

    public function test_le_comptable_ouvre_une_campagne(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->jeton('comptable_matieres'))
            ->postJson('/api/campagnes', $this->donneesCampagne())
            ->assertStatus(201)
            ->assertJsonPath('statut', 'ouverte')
            ->assertJsonPath('nom', 'Inventaire 2026');
    }

    public function test_un_responsable_ne_peut_pas_ouvrir_de_campagne(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->jeton('responsable_service'))
            ->postJson('/api/campagnes', $this->donneesCampagne())
            ->assertStatus(403);
    }

    public function test_la_liste_des_ecarts_est_accessible_au_controleur(): void
    {
        $campagne = Campagne::create($this->donneesCampagne() + ['statut' => 'ouverte']);

        $this->withHeader('Authorization', 'Bearer '.$this->jeton('controleur'))
            ->getJson('/api/campagnes/'.$campagne->id.'/ecarts')
            ->assertOk()
            ->assertJsonCount(0);
    }

    public function test_les_ecarts_sont_en_lecture_seule(): void
    {
        $campagne = Campagne::create($this->donneesCampagne() + ['statut' => 'ouverte']);

        // Aucune route d'écriture n'existe sur les écarts.
        $this->withHeader('Authorization', 'Bearer '.$this->jeton('controleur'))
            ->postJson('/api/campagnes/'.$campagne->id.'/ecarts', [])
            ->assertStatus(405);
    }

    public function test_la_fiche_campagne_donne_les_compteurs(): void
    {
        $campagne = Campagne::create($this->donneesCampagne() + ['statut' => 'ouverte']);

        $this->withHeader('Authorization', 'Bearer '.$this->jeton('agent_patrimoine'))
            ->getJson('/api/campagnes/'.$campagne->id)
            ->assertOk()
            ->assertJsonPath('compteurs.controles', 0)
            ->assertJsonPath('compteurs.ecarts', 0);
    }
}

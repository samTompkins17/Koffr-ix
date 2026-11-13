<?php

namespace Tests\Feature;

use App\Models\Campagne;
use App\Models\Controle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\Support\UtilisateurJeton;
use Tests\TestCase;

class TraitementEcartTest extends TestCase
{
    use RefreshDatabase;

    private Controle $controle;

    private Controle $controleSansEcart;

    protected function setUp(): void
    {
        parent::setUp();

        $campagne = Campagne::create([
            'nom' => 'Inventaire de test',
            'date_debut' => '2026-01-01',
            'date_fin' => '2026-12-31',
            'structure_id' => '00000000-0000-4000-8000-000000000001',
            'statut' => 'ouverte',
        ]);

        $this->controle = Controle::create([
            'campagne_id' => $campagne->id,
            'bien_id' => '00000000-0000-4000-8000-000000000301',
            'etat_fiche' => 'bon',
            'etat_constate' => 'hors_service',
            'ecart_detecte' => true,
            'agent_id' => '00000000-0000-4000-8000-000000000103',
            'date' => now(),
        ]);

        $this->controleSansEcart = Controle::create([
            'campagne_id' => $campagne->id,
            'bien_id' => '00000000-0000-4000-8000-000000000302',
            'etat_fiche' => 'bon',
            'etat_constate' => 'bon',
            'ecart_detecte' => false,
            'agent_id' => '00000000-0000-4000-8000-000000000103',
            'date' => now(),
        ]);
    }

    private function jeton(string $role = 'comptable_matieres'): string
    {
        return JWTAuth::fromSubject(new UtilisateurJeton(role: $role));
    }

    public function test_le_comptable_documente_un_ecart(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->postJson('/api/ecarts/'.$this->controle->id.'/traitement', [
                'action' => 'reforme',
                'commentaire' => 'Matériel hors service confirmé, réforme engagée.',
            ])
            ->assertStatus(201)
            ->assertJsonPath('action', 'reforme');

        $this->assertDatabaseCount('traitements_ecart', 1);

        // Le traitement apparaît dans la liste des écarts.
        $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->getJson('/api/ecarts')
            ->assertOk()
            ->assertJsonPath('0.traitement.action', 'reforme');
    }

    public function test_un_ecart_ne_peut_etre_traite_deux_fois(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->postJson('/api/ecarts/'.$this->controle->id.'/traitement', [
                'action' => 'regularise',
                'commentaire' => 'Régularisation effectuée.',
            ])
            ->assertStatus(201);

        $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->postJson('/api/ecarts/'.$this->controle->id.'/traitement', [
                'action' => 'autre',
                'commentaire' => 'Nouvelle tentative.',
            ])
            ->assertStatus(409);
    }

    public function test_le_controleur_ne_peut_pas_traiter_un_ecart(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->jeton('controleur'))
            ->postJson('/api/ecarts/'.$this->controle->id.'/traitement', [
                'action' => 'reforme',
                'commentaire' => 'Tentative.',
            ])
            ->assertStatus(403);
    }

    public function test_un_constat_sans_ecart_ne_se_traite_pas(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->postJson('/api/ecarts/'.$this->controleSansEcart->id.'/traitement', [
                'action' => 'autre',
                'commentaire' => 'Sans objet.',
            ])
            ->assertStatus(422);
    }
}

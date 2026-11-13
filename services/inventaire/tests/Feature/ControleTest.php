<?php

namespace Tests\Feature;

use App\Models\Campagne;
use App\Models\Controle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\Support\UtilisateurJeton;
use Tests\TestCase;

class ControleTest extends TestCase
{
    use RefreshDatabase;

    private string $campagneId;

    private const BIEN_ID = '00000000-0000-4000-8000-000000000301';

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

        $this->campagneId = $campagne->id;

        // Le service patrimoine est simulé : aucun appel réseau réel.
        Http::fake([
            'patrimoine:8080/api/biens/code/*' => function ($requete) {
                // Un code inconnu renvoie 404, comme le vrai service.
                if (str_ends_with($requete->url(), '9999')) {
                    return Http::response(['message' => 'Aucun bien ne correspond à ce code.'], 404);
                }

                return Http::response([
                    'id' => self::BIEN_ID,
                    'code_unique' => 'PAT-SAN-2026-0412',
                    'designation' => 'Ordinateur portable HP 450',
                    'categorie' => 'Matériel informatique',
                    'etat' => 'bon',
                    'structure_id' => '00000000-0000-4000-8000-000000000004',
                ], 200);
            },
            'patrimoine:8080/api/biens/*' => Http::response([
                'id' => self::BIEN_ID,
                'code_unique' => 'PAT-SAN-2026-0412',
                'designation' => 'Ordinateur portable HP 450',
                'etat' => 'bon',
            ], 200),
        ]);
    }

    private function jeton(string $role = 'agent_patrimoine'): string
    {
        return JWTAuth::fromSubject(new UtilisateurJeton(role: $role));
    }

    public function test_un_ecart_est_detecte_quand_letat_differe_de_la_fiche(): void
    {
        $reponse = $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->postJson('/api/controles', [
                'campagne_id' => $this->campagneId,
                'bien_id' => self::BIEN_ID,
                'etat_constate' => 'hors_service',
            ]);

        $reponse->assertStatus(201)
            ->assertJsonPath('etat_fiche', 'bon')
            ->assertJsonPath('etat_constate', 'hors_service')
            ->assertJsonPath('ecart_detecte', true);
    }

    public function test_aucun_ecart_quand_letat_est_identique(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->postJson('/api/controles', [
                'campagne_id' => $this->campagneId,
                'bien_id' => self::BIEN_ID,
                'etat_constate' => 'bon',
            ])
            ->assertStatus(201)
            ->assertJsonPath('ecart_detecte', false);
    }

    public function test_un_bien_introuvable_est_toujours_un_ecart(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->postJson('/api/controles', [
                'campagne_id' => $this->campagneId,
                'bien_id' => self::BIEN_ID,
                'etat_constate' => 'introuvable',
            ])
            ->assertStatus(201)
            ->assertJsonPath('ecart_detecte', true);
    }

    public function test_un_second_controle_du_meme_bien_dans_la_campagne_renvoie_409(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->postJson('/api/controles', [
                'campagne_id' => $this->campagneId,
                'bien_id' => self::BIEN_ID,
                'etat_constate' => 'bon',
            ])->assertStatus(201);

        $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->postJson('/api/controles', [
                'campagne_id' => $this->campagneId,
                'bien_id' => self::BIEN_ID,
                'etat_constate' => 'usage',
            ])->assertStatus(409);
    }

    public function test_le_controleur_ne_peut_pas_enregistrer_de_controle(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->jeton('controleur'))
            ->postJson('/api/controles', [
                'campagne_id' => $this->campagneId,
                'bien_id' => self::BIEN_ID,
                'etat_constate' => 'bon',
            ])
            ->assertStatus(403);
    }

    public function test_le_scan_renvoie_la_fiche_et_le_dernier_controle(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->postJson('/api/controles', [
                'campagne_id' => $this->campagneId,
                'bien_id' => self::BIEN_ID,
                'etat_constate' => 'usage',
            ])->assertStatus(201);

        $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->getJson('/api/controles/bien/PAT-SAN-2026-0412')
            ->assertOk()
            ->assertJsonPath('bien.code_unique', 'PAT-SAN-2026-0412')
            ->assertJsonPath('dernier_controle.etat_constate', 'usage');
    }

    public function test_un_code_inconnu_renvoie_404(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->getJson('/api/controles/bien/PAT-SAN-2026-9999')
            ->assertStatus(404);
    }

    public function test_patrimoine_indisponible_renvoie_503(): void
    {
        Http::fake(fn () => throw new ConnectionException('patrimoine hors ligne'));

        $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->getJson('/api/controles/bien/PAT-SAN-2026-0412')
            ->assertStatus(503);

        // Le reste du service continue de répondre.
        $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->getJson('/api/campagnes')
            ->assertOk();
    }

    public function test_un_controle_ne_peut_pas_etre_modifie(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->postJson('/api/controles', [
                'campagne_id' => $this->campagneId,
                'bien_id' => self::BIEN_ID,
                'etat_constate' => 'bon',
            ]);

        $this->expectException(\RuntimeException::class);
        Controle::firstOrFail()->update(['etat_constate' => 'usage']);
    }
}

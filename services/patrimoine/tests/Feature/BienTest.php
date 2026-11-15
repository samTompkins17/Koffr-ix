<?php

namespace Tests\Feature;

use App\Models\Bien;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\UtilisateurJeton;
use Tests\TestCase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class BienTest extends TestCase
{
    use RefreshDatabase;

    private function jeton(string $role = 'comptable_matieres'): string
    {
        return JWTAuth::fromSubject(new UtilisateurJeton(role: $role));
    }

    private function donneesBien(): array
    {
        return [
            'designation' => 'Ordinateur portable Dell',
            'categorie' => 'Matériel informatique',
            'valeur_acquisition' => 500000,
            'date_acquisition' => '2026-03-01',
            'etat' => 'bon',
            'localisation' => 'Bureau 4',
            'structure_id' => '00000000-0000-4000-8000-000000000004',
        ];
    }

    public function test_sans_jeton_la_liste_est_refusee(): void
    {
        $this->getJson('/api/biens')->assertStatus(401);
    }

    public function test_creation_genere_un_code_unique_au_bon_format(): void
    {
        $reponse = $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->postJson('/api/biens', $this->donneesBien());

        $reponse->assertStatus(201);
        $this->assertMatchesRegularExpression('/^PAT-SAN-\d{4}-0001$/', $reponse->json('code_unique'));
        $this->assertSame('en_service', $reponse->json('statut'));
    }

    public function test_le_code_patrimoine_nest_pas_modifiable(): void
    {
        $creation = $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->postJson('/api/biens', $this->donneesBien());
        $id = $creation->json('id');
        $code = $creation->json('code_unique');

        $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->putJson('/api/biens/'.$id, $this->donneesBien() + ['code_unique' => 'PAT-SAN-2026-9999'])
            ->assertOk()
            ->assertJsonPath('code_unique', $code);

        $this->assertSame($code, Bien::find($id)->code_unique);
    }

    public function test_un_agent_ne_peut_pas_creer_de_bien(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->jeton('agent_patrimoine'))
            ->postJson('/api/biens', $this->donneesBien())
            ->assertStatus(403);
    }

    public function test_recherche_par_code(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->postJson('/api/biens', $this->donneesBien())
            ->assertStatus(201);

        $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->getJson('/api/biens/code/PAT-SAN-2026-0001')
            ->assertOk()
            ->assertJsonPath('designation', 'Ordinateur portable Dell');

        $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->getJson('/api/biens/code/PAT-SAN-2026-0002')
            ->assertStatus(404);
    }

    public function test_la_verification_publique_ne_divulgue_pas_les_donnees_sensibles(): void
    {
        $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->postJson('/api/biens', $this->donneesBien())
            ->assertStatus(201);

        $reponse = $this->getJson('/api/public/biens/PAT-SAN-2026-0001');

        $reponse->assertOk()
            ->assertJsonPath('immatriculation', 'PAT-SAN-2026-0001')
            ->assertJsonPath('bien_de_l_etat', true);

        $this->assertArrayNotHasKey('valeur_acquisition', $reponse->json());
        $this->assertArrayNotHasKey('responsable_id', $reponse->json());
        $this->assertArrayNotHasKey('localisation', $reponse->json());
        $this->assertArrayNotHasKey('latitude', $reponse->json());
    }

    public function test_le_qr_code_et_letiquette_sont_generes(): void
    {
        $creation = $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->postJson('/api/biens', $this->donneesBien());
        $id = $creation->json('id');

        $qr = $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->get('/api/biens/'.$id.'/qrcode');
        $qr->assertOk();
        $this->assertStringContainsString('<svg', $qr->getContent());

        $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->get('/api/biens/'.$id.'/etiquette')
            ->assertOk()
            ->assertSee('République Togolaise')
            ->assertSee($creation->json('code_unique'));
    }
}

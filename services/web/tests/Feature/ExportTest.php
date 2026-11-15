<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request as RequeteApi;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.gateway' => 'http://gateway']);
        Http::preventStrayRequests();
    }

    public function test_pdf_biens_est_genere_avec_les_filtres_et_les_noms_de_structure(): void
    {
        Http::fake(function (RequeteApi $requete) {
            if (str_contains($requete->url(), '/api/patrimoine/biens')) {
                $this->assertSame('ordinateur', $requete['q']);
                $this->assertSame('bon', $requete['etat']);

                return Http::response([
                    'data' => [$this->bien()], 'last_page' => 1, 'current_page' => 1, 'total' => 1,
                ]);
            }

            $this->assertSame('structure-1', $requete['ids']);

            return Http::response([['id' => 'structure-1', 'nom' => 'Santé']]);
        });

        $response = $this->connecter()->get(route('biens.export-pdf', [
            'q' => 'ordinateur', 'etat' => 'bon',
        ]));

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('attachment;', $response->headers->get('Content-Disposition'));
    }

    public function test_pdf_ecarts_est_genere_pour_les_roles_autorises_et_conserve_le_filtre(): void
    {
        Http::fake(function (RequeteApi $requete) {
            if (str_contains($requete->url(), '/api/inventaire/ecarts')) {
                $this->assertSame('campagne-1', $requete['campagne_id']);

                return Http::response([[
                    'bien_id' => 'bien-1', 'agent_id' => 'agent-1',
                    'etat_fiche' => 'bon', 'etat_constate' => 'usage',
                    'date' => '2026-11-14T12:00:00Z', 'traitement' => null,
                ]]);
            }

            if (str_contains($requete->url(), '/api/patrimoine/biens')) {
                $this->assertSame('bien-1', $requete['ids']);

                return Http::response([
                    'data' => [$this->bien('bien-1')], 'last_page' => 1, 'current_page' => 1, 'total' => 1,
                ]);
            }

            $this->assertSame('agent-1', $requete['ids']);

            return Http::response([['id' => 'agent-1', 'name' => 'Yao']]);
        });

        $response = $this->connecter('controleur')->get(route('ecarts.export-pdf', ['campagne' => 'campagne-1']));

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_export_ecarts_refuse_les_roles_non_autorises_avant_tout_appel(): void
    {
        Http::fake();

        foreach (['admin', 'agent_patrimoine', 'responsable_service'] as $role) {
            $this->connecter($role)->get(route('ecarts.export-pdf'))->assertForbidden();
        }

        Http::assertNothingSent();
    }

    public function test_routes_csv_et_excel_ne_sont_plus_exposees_et_restent_des_404(): void
    {
        Http::fake();
        foreach ([
            '/biens/export.csv', '/ecarts/export.csv',
            '/biens/export.xlsx', '/ecarts/export.xlsx',
        ] as $url) {
            $response = $this->connecter()->get($url);
            $this->assertGreaterThanOrEqual(400, $response->status(), $url.' ne doit pas produire un export.');
            $response->assertHeaderMissing('Content-Disposition');
        }
        Http::assertNothingSent();
    }

    public function test_sans_session_aucun_export_pdf_ne_peut_etre_genere(): void
    {
        Http::fake();
        $this->get('/biens/export.pdf')->assertRedirect(route('connexion'));
        Http::assertNothingSent();
    }

    private function connecter(string $role = 'comptable_matieres'): static
    {
        return $this->withSession([
            'jwt' => 'jeton-test',
            'utilisateur' => ['role' => $role, 'name' => 'Ama K.'],
            'expire_le' => now()->addMinutes(15),
        ]);
    }

    private function bien(string $id = 'bien-1'): array
    {
        return [
            'id' => $id,
            'code_unique' => 'PAT-SAN-2026-0001',
            'designation' => 'Ordinateur portable',
            'categorie' => 'Matériel informatique',
            'structure_id' => 'structure-1',
            'etat' => 'bon',
            'statut' => 'en_service',
            'valeur_acquisition' => 650000,
            'date_acquisition' => '2026-11-14',
        ];
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as RequeteApi;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProcesVerbalPdfTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.gateway' => 'http://gateway']);
        Http::preventStrayRequests();
    }

    #[DataProvider('pvEtRoles')]
    public function test_les_deux_pv_sont_de_vrais_pdf_pour_les_roles_autorises(string $type, string $role): void
    {
        $this->apiValide();
        $reponse = $this->connecter($role)->get('/proces-verbaux/'.$type.'/1?format=pdf');
        $reponse->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $reponse->getContent());
        $this->assertStringContainsString('pv-'.$type.'-1.pdf', $reponse->headers->get('Content-Disposition'));
        Http::assertSent(fn (RequeteApi $r) => $r->header('Authorization') === ['Bearer jeton-test']);
        Http::assertNotSent(fn (RequeteApi $r) => $r->method() !== 'GET');
    }

    public function test_inventaire_pdf_conserve_plus_de_cent_biens_et_leurs_noms(): void
    {
        $lots = [];
        $this->apiValide(105, observateur: function (RequeteApi $r) use (&$lots) {
            if (str_contains($r->url(), '/patrimoine/biens?')) {
                $lots[] = count(explode(',', $r['ids']));
                $this->assertEquals(100, $r['per_page']);
            }
        });
        $donneesPdf = [];
        View::composer('proces-verbaux.inventaire-pdf', function ($vue) use (&$donneesPdf) {
            $donneesPdf = $vue->getData();
        });

        $reponse = $this->connecter()->get('/proces-verbaux/inventaire/1?format=pdf');
        $reponse->assertOk();
        $this->assertStringStartsWith('%PDF-', $reponse->getContent());
        $this->assertSame([100, 5], $lots);
        $this->assertCount(105, $donneesPdf['controles']);
        $this->assertCount(105, $donneesPdf['biens']);
        $this->assertSame('Ordinateur 105', $donneesPdf['biens'][105]['designation']);
        $this->assertSame('Yao & Équipe', $donneesPdf['personnes']['agent-1']);
        Http::assertSentCount(6);
    }

    public function test_index_conserve_toutes_les_pages_des_biens_sortis(): void
    {
        $pages = [];
        $this->apiValide(105, observateur: function (RequeteApi $r) use (&$pages) {
            if (str_contains($r->url(), '/patrimoine/biens?')) {
                $this->assertSame('sorti', $r['statut']);
                $pages[] = (int) $r['page'];
            }
        });
        $this->connecter()->get('/proces-verbaux')->assertOk()
            ->assertViewHas('sortis', fn ($biens) => count($biens) === 105)
            ->assertSee('PAT-SAN-2026-0105')->assertSee('Télécharger PDF');
        $this->assertSame([1, 2], $pages);
    }

    #[DataProvider('typesPv')]
    public function test_apercu_et_impression_navigateur_restent_disponibles(string $type): void
    {
        $this->apiValide();
        $reponse = $this->connecter()->get('/proces-verbaux/'.$type.'/1');
        $reponse->assertOk()->assertViewIs('proces-verbaux.'.$type)
            ->assertSee('window.print()', false)->assertSee('Télécharger PDF')
            ->assertSee('/proces-verbaux/'.$type.'/1?format=pdf', false);
        $reponse->assertHeaderMissing('Content-Disposition');
    }

    #[DataProvider('pannes')]
    public function test_echec_amont_ne_produit_jamais_un_faux_pv(string $type, string $etape, int $statut): void
    {
        $this->apiValide(echec: $etape, statut: $statut);
        $url = $type === 'index' ? '/proces-verbaux' : '/proces-verbaux/'.$type.'/1?format=pdf';
        $reponse = $this->connecter()->get($url);
        if ($statut === 401) {
            $reponse->assertRedirect(route('session.expiree'))->assertSessionMissing(['jwt', 'utilisateur', 'expire_le']);
        } else {
            $reponse->assertStatus($statut === 403 ? 403 : 503);
        }
        $reponse->assertHeaderMissing('Content-Disposition');
    }

    public function test_echec_du_deuxieme_lot_de_biens_ne_produit_pas_de_pdf_partiel(): void
    {
        $this->apiValide(105, echec: 'lot2', statut: 503);
        $this->connecter()->get('/proces-verbaux/inventaire/1?format=pdf')
            ->assertStatus(503)->assertHeaderMissing('Content-Disposition');
    }

    public function test_echec_de_la_deuxieme_page_ne_masque_pas_les_sorties(): void
    {
        $this->apiValide(105, echec: 'page2', statut: 503);
        $this->connecter()->get('/proces-verbaux')->assertStatus(503);
    }

    public function test_aucun_pv_si_des_fiches_manquent(): void
    {
        $this->apiValide(2, echec: 'biens', statut: 200, corpsEchec: ['data' => [], 'last_page' => 1, 'total' => 0]);
        $this->connecter()->get('/proces-verbaux/inventaire/1?format=pdf')
            ->assertStatus(503)->assertHeaderMissing('Content-Disposition');
    }

    public function test_controles_incomplets_ne_deviennent_pas_un_pv_vide(): void
    {
        $this->apiValide(echec: 'controles', statut: 200, corpsEchec: []);
        $this->connecter()->get('/proces-verbaux/inventaire/1?format=pdf')
            ->assertStatus(503)->assertHeaderMissing('Content-Disposition');
    }

    public function test_reponse_invalide_ne_devient_pas_un_pv_sans_mouvement(): void
    {
        $this->apiValide(echec: 'mouvements', statut: 200, corpsEchec: ['message' => 'Proxy indisponible']);
        $this->connecter()->get('/proces-verbaux/sortie/1?format=pdf')
            ->assertStatus(503)->assertHeaderMissing('Content-Disposition');
    }

    public function test_absence_reelle_de_sortie_conserve_le_refus_metier(): void
    {
        $this->apiValide(echec: 'mouvements', statut: 200, corpsEchec: []);
        $this->connecter()->get('/proces-verbaux/sortie/1?format=pdf')
            ->assertRedirect(route('proces-verbaux.index'))->assertSessionHas('erreur')
            ->assertHeaderMissing('Content-Disposition');
    }

    #[DataProvider('typesPv')]
    public function test_une_ressource_inexistante_reste_une_404(string $type): void
    {
        $this->apiValide(echec: $type === 'inventaire' ? 'campagne' : 'bien', statut: 404);
        $this->connecter()->get('/proces-verbaux/'.$type.'/1?format=pdf')->assertNotFound();
    }

    public function test_roles_refuses_sur_index_apercus_et_pdf(): void
    {
        Http::fake();
        foreach (['agent_patrimoine', 'controleur'] as $role) {
            foreach (['/proces-verbaux', '/proces-verbaux/inventaire/1', '/proces-verbaux/sortie/1', '/proces-verbaux/inventaire/1?format=pdf', '/proces-verbaux/sortie/1?format=pdf'] as $url) {
                $this->connecter($role)->get($url)->assertForbidden();
            }
        }
        Http::assertNothingSent();
    }

    public function test_sans_session_pas_de_pdf(): void
    {
        Http::fake();
        $this->get('/proces-verbaux/sortie/1?format=pdf')->assertRedirect(route('connexion'));
        Http::assertNothingSent();
    }

    public function test_vues_pdf_echappent_les_champs_et_sont_autonomes(): void
    {
        $texte = '<script>alert("x")</script><img src="https://exemple.invalid/piste"> & État';
        $bien = array_merge($this->bien(1), ['designation' => $texte]);
        $commun = [
            'structures' => ['structure-1' => $texte],
            'personnes' => ['agent-1' => $texte],
        ];
        $vues = [
            'inventaire' => $commun + [
                'campagne' => array_merge($this->campagne(), ['nom' => $texte]),
                'compteurs' => ['controles' => 1, 'ecarts' => 1, 'introuvables' => 0],
                'controles' => [$this->controle(1)], 'biens' => [1 => $bien],
            ],
            'sortie' => $commun + [
                'bien' => $bien, 'sortie' => array_merge($this->sortie(), ['motif' => $texte]),
            ],
        ];
        foreach ($vues as $type => $donnees) {
            $html = view('proces-verbaux.'.$type.'-pdf', array_merge($donnees, ['pdf' => true]))->render();
            $this->assertStringContainsString(e($texte), $html);
            $this->assertStringNotContainsString('<script', $html);
            $this->assertStringNotContainsString('<img src="https://', $html);
            $this->assertStringNotContainsString('tailwind', $html);
            $this->assertStringNotContainsString('fonts.googleapis', $html);
            $this->assertStringContainsString('Document de démonstration, non officiel.', $html);
            $this->assertStringContainsString('Édité par KOFFR-IX', $html);
            $this->assertStringContainsString('Georgia', $html);
        }
    }

    public static function typesPv(): array
    {
        return [['inventaire'], ['sortie']];
    }

    public static function pvEtRoles(): iterable
    {
        foreach (['inventaire', 'sortie'] as $type) {
            foreach (['admin', 'comptable_matieres', 'responsable_service'] as $role) {
                yield "$type-$role" => [$type, $role];
            }
        }
    }

    public static function pannes(): iterable
    {
        foreach ([
            'index' => ['campagnes', 'biens', 'structures'],
            'inventaire' => ['campagne', 'controles', 'biens', 'structures', 'users'],
            'sortie' => ['bien', 'mouvements', 'structures', 'users'],
        ] as $type => $etapes) {
            foreach ($etapes as $etape) {
                foreach ([401, 403, 500, 0] as $statut) {
                    yield "$type-$etape-$statut" => [$type, $etape, $statut];
                }
            }
        }
    }

    private function connecter(string $role = 'comptable_matieres'): static
    {
        return $this->withSession(['jwt' => 'jeton-test', 'utilisateur' => ['role' => $role, 'name' => 'Ama K.'], 'expire_le' => now()->addMinutes(15)]);
    }

    /** Une seule simulation route les appels, sans dépendre de leur ordre. */
    private function apiValide(int $nombre = 1, ?string $echec = null, int $statut = 503, mixed $corpsEchec = null, ?callable $observateur = null): void
    {
        Http::fake(function (RequeteApi $r) use ($nombre, $echec, $statut, $corpsEchec, $observateur) {
            $observateur?->__invoke($r);
            $chemin = parse_url($r->url(), PHP_URL_PATH);
            $etape = match ($chemin) {
                '/api/inventaire/campagnes' => 'campagnes',
                '/api/inventaire/campagnes/1' => 'campagne',
                '/api/inventaire/campagnes/1/controles' => 'controles',
                '/api/patrimoine/biens' => 'biens',
                '/api/patrimoine/biens/1' => 'bien',
                '/api/patrimoine/biens/1/mouvements' => 'mouvements',
                '/api/auth/structures' => 'structures',
                '/api/auth/users' => 'users',
                default => throw new \RuntimeException('Appel inattendu : '.$r->url()),
            };
            $lot2 = $echec === 'lot2' && $etape === 'biens' && str_starts_with((string) $r['ids'], '101,');
            $page2 = $echec === 'page2' && $etape === 'biens' && (int) $r['page'] === 2;
            if ($etape === $echec || $lot2 || $page2) {
                if ($statut === 0) {
                    throw new ConnectionException('Service arrêté');
                }

                return Http::response($corpsEchec ?? ['message' => 'Indisponible'], $statut);
            }

            if ($etape === 'biens') {
                $ids = ($r['ids'] ?? null) ? explode(',', $r['ids']) : range(1, $nombre);
                $page = (int) ($r['page'] ?? 1);

                return Http::response([
                    'data' => array_map(fn ($id) => $this->bien((int) $id), array_slice($ids, ($page - 1) * 100, 100)),
                    'last_page' => max(1, (int) ceil(count($ids) / 100)), 'current_page' => $page, 'total' => count($ids),
                ]);
            }

            return Http::response(match ($etape) {
                'campagnes' => [$this->campagne()],
                'campagne' => ['campagne' => $this->campagne(), 'compteurs' => ['controles' => $nombre, 'ecarts' => $nombre, 'introuvables' => 0]],
                'controles' => array_map(fn ($i) => $this->controle($i), range(1, $nombre)),
                'bien' => $this->bien(1),
                'mouvements' => [$this->sortie()],
                'structures' => [['id' => 'structure-1', 'nom' => 'Santé']],
                'users' => [['id' => 'agent-1', 'name' => 'Yao & Équipe']],
            });
        });
    }

    private function campagne(): array
    {
        return ['id' => '1', 'nom' => 'Inventaire 2026', 'structure_id' => 'structure-1', 'date_debut' => '2026-11-01', 'date_fin' => '2026-11-30', 'statut' => 'ouverte'];
    }

    private function bien(int $i): array
    {
        return [
            'id' => (string) $i, 'code_unique' => sprintf('PAT-SAN-2026-%04d', $i), 'designation' => 'Ordinateur '.$i,
            'categorie' => 'Matériel informatique', 'structure_id' => 'structure-1', 'etat' => 'bon',
            'statut' => 'sorti', 'valeur_acquisition' => 650000, 'date_acquisition' => '2026-01-01',
        ];
    }

    private function controle(int $i): array
    {
        return ['bien_id' => (string) $i, 'agent_id' => 'agent-1', 'etat_fiche' => 'bon', 'etat_constate' => 'usage', 'ecart_detecte' => true, 'date' => '2026-11-14'];
    }

    private function sortie(): array
    {
        return ['type' => 'sortie', 'date' => '2026-11-14', 'structure_origine_id' => 'structure-1', 'structure_destination_id' => null, 'motif' => 'Réforme', 'auteur_id' => 'agent-1'];
    }
}

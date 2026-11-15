<?php

namespace Tests\Feature;

use App\Support\ApiClient;
use Illuminate\Http\Client\Request as RequeteApi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AccesRefuseTest extends TestCase
{
    private const ID = '11111111-1111-4111-8111-111111111111';

    private const MESSAGE_API = 'Votre rôle ne permet pas d’accéder à cette ressource.';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.gateway' => 'http://gateway', 'app.debug' => false]);
        Http::preventStrayRequests();
    }

    #[DataProvider('refusLocaux')]
    public function test_un_role_interdit_recoit_la_page_403_sans_appel_api(
        string $role, string $methode, string $url, string $message
    ): void {
        Http::fake();

        // Même sans données de formulaire, le rôle doit être vérifié avant toute écriture.
        $this->withSession($this->sessionPour($role))->call($methode, $url)
            ->assertForbidden()
            ->assertSee('Accès refusé')
            ->assertSee('Vous n’avez pas accès à cette page')
            ->assertSee(config('koffr.roles')[$role])
            ->assertSee($message)
            ->assertSee('Retour au tableau de bord')
            ->assertSee('href="'.route('tableau').'"', false)
            ->assertDontSee('Cette tentative est enregistrée')
            ->assertSessionHas('jwt', 'jeton-de-test');

        // Les refus locaux ne provoquent ni écriture métier ni faux événement d'audit.
        Http::assertNothingSent();
    }

    public static function refusLocaux(): array
    {
        return [
            'formulaire de bien' => ['agent_patrimoine', 'GET', '/biens/creer', 'Votre rôle ne permet pas de créer un bien.'],
            'création de bien' => ['controleur', 'POST', '/biens', 'Votre rôle ne permet pas de créer un bien.'],
            'formulaire de modification' => ['responsable_service', 'GET', '/biens/'.self::ID.'/modifier', 'Votre rôle ne permet pas de modifier un bien.'],
            'modification de bien' => ['controleur', 'PUT', '/biens/'.self::ID, 'Votre rôle ne permet pas de modifier un bien.'],
            'administration' => ['responsable_service', 'GET', '/administration', 'Cette page est réservée à l’administrateur.'],
            'création de compte' => ['comptable_matieres', 'POST', '/administration/comptes', 'Cette page est réservée à l’administrateur.'],
            'création de structure' => ['controleur', 'POST', '/administration/structures', 'Cette page est réservée à l’administrateur.'],
            'liste des écarts' => ['admin', 'GET', '/ecarts', 'La liste des écarts est réservée au comptable et au contrôleur.'],
            'traitement d’écart' => ['controleur', 'POST', '/ecarts/'.self::ID.'/traitement', 'Seul le comptable des matières peut traiter un écart.'],
            'ouverture de campagne' => ['admin', 'POST', '/campagnes', 'Seul le comptable des matières peut ouvrir une campagne.'],
            'scan' => ['comptable_matieres', 'GET', '/controles/scan', 'Le scan de contrôle est réservé aux agents du patrimoine.'],
            'enregistrement du contrôle' => ['controleur', 'POST', '/controles', 'Le scan de contrôle est réservé aux agents du patrimoine.'],
            'formulaire de mouvement' => ['admin', 'GET', '/transferts', 'Votre rôle ne permet pas de déclarer un mouvement.'],
            'déclaration du mouvement' => ['agent_patrimoine', 'POST', '/transferts', 'Votre rôle ne permet pas de déclarer un mouvement.'],
            'validation du transfert' => ['comptable_matieres', 'POST', '/transferts/'.self::ID.'/valider', 'Seul le responsable de service valide un transfert.'],
            'refus du transfert' => ['controleur', 'POST', '/transferts/'.self::ID.'/refuser', 'Seul le responsable de service valide un transfert.'],
            'journal' => ['comptable_matieres', 'GET', '/journal', 'Le journal d’audit est réservé à l’administrateur et au contrôleur.'],
        ];
    }

    #[DataProvider('appelsApiInterdits')]
    public function test_un_403_api_remonte_la_page_403_avec_un_message_generique(
        string $role, string $methode, string $url, string $cheminApi, array $donnees, bool $avecPhoto = false
    ): void {
        $messageInterne = '<script>alert("detail-interne")</script>';
        Http::fake(['*' => Http::response(['message' => $messageInterne], 403)]);

        if ($avecPhoto) {
            $donnees['photo'] = $this->photo();
        }

        $this->withSession($this->sessionPour($role))->call($methode, $url, $donnees)
            ->assertForbidden()
            ->assertSee('Vous n’avez pas accès à cette page')
            ->assertSee(self::MESSAGE_API)
            ->assertDontSee('detail-interne')
            ->assertSessionHas('jwt', 'jeton-de-test');

        Http::assertSentCount(1);
        Http::assertSent(fn (RequeteApi $requete) => $requete->method() === $methode
            && $requete->url() === 'http://gateway'.$cheminApi
            && $requete->hasHeader('Authorization', 'Bearer jeton-de-test'));
    }

    public static function appelsApiInterdits(): array
    {
        return [
            'GET' => ['admin', 'GET', '/administration', '/api/auth/annuaire', []],
            'POST' => ['comptable_matieres', 'POST', '/campagnes', '/api/inventaire/campagnes', self::campagne()],
            'PUT' => ['admin', 'PUT', '/biens/'.self::ID, '/api/patrimoine/biens/'.self::ID, [
                'designation' => 'Ordinateur de démonstration',
                'categorie' => 'Matériel informatique',
                'valeur_acquisition' => 250000,
                'date_acquisition' => '2026-11-01',
                'etat' => 'bon',
                'structure_id' => self::ID,
            ]],
            'POST multipart' => ['agent_patrimoine', 'POST', '/controles', '/api/inventaire/controles', [
                'campagne_id' => self::ID,
                'bien_id' => self::ID,
                'etat_constate' => 'bon',
                'code' => 'PAT-SAN-2026-0412',
            ], true],
            'fiche de contrôle' => ['controleur', 'GET', '/controles/PAT-SAN-2026-0412', '/api/inventaire/controles/bien/PAT-SAN-2026-0412', []],
        ];
    }

    public function test_un_403_sur_un_appel_secondaire_ne_devient_pas_une_page_vide(): void
    {
        Http::fake([
            'http://gateway/api/inventaire/campagnes' => Http::response([], 200),
            'http://gateway/api/patrimoine/stats' => Http::response([], 403),
        ]);

        $this->withSession($this->sessionPour('comptable_matieres'))->get('/campagnes')
            ->assertForbidden()
            ->assertSee(self::MESSAGE_API);

        Http::assertSentCount(2);
    }

    public function test_le_message_de_lexception_et_le_role_sont_echappes(): void
    {
        $message = '<script>alert("message")</script>';
        $role = '<img src=x onerror=alert("role")>';
        $this->withSession($this->sessionPour($role));

        $this->view('errors.403', [
            'exception' => new HttpException(403, $message),
            'errors' => new ViewErrorBag,
        ])
            ->assertSee($message)
            ->assertDontSee($message, false)
            ->assertSee($role)
            ->assertDontSee($role, false);
    }

    #[DataProvider('statutsConserves')]
    public function test_le_client_conserve_les_reponses_autres_que_403(string $methode, int $statut): void
    {
        $contenu = ['message' => 'Réponse API', 'errors' => ['nom' => ['Nom requis.']]];
        Http::fake(['*' => Http::response($contenu, $statut)]);
        $this->withSession($this->sessionPour('agent_patrimoine'));
        $api = ApiClient::session();

        $reponse = $methode === 'postAvecFichier'
            ? $api->postAvecFichier('/api/test', [], $this->photo())
            : $api->{$methode}('/api/test');

        $this->assertSame($statut, $reponse->status());
        $this->assertSame($contenu, $reponse->json());
        Http::assertSentCount(1);
    }

    public static function statutsConserves(): iterable
    {
        foreach (['get', 'post', 'put', 'postAvecFichier'] as $methode) {
            foreach ([200, 401, 422] as $statut) {
                yield $methode.' '.$statut => [$methode, $statut];
            }
        }
    }

    public function test_un_401_api_garde_la_redirection_vers_la_session_expiree(): void
    {
        Http::fake(['*' => Http::response(['message' => 'Jeton expiré.'], 401)]);

        $this->withSession($this->sessionPour('comptable_matieres'))->post('/campagnes', self::campagne())
            ->assertRedirect(route('session.expiree'))
            ->assertSessionMissing('jwt')
            ->assertSessionMissing('utilisateur')
            ->assertSessionMissing('expire_le');
    }

    public function test_un_422_api_garde_les_erreurs_et_la_saisie(): void
    {
        Http::fake(['*' => Http::response(['errors' => ['nom' => ['Ce nom est déjà utilisé.']]], 422)]);

        $this->withSession($this->sessionPour('comptable_matieres'))->from('/campagnes')
            ->post('/campagnes', self::campagne())
            ->assertRedirect('/campagnes')
            ->assertSessionHasErrors(['nom' => 'Ce nom est déjà utilisé.'])
            ->assertSessionHasInput('nom', 'Inventaire 2026')
            ->assertSessionHas('jwt', 'jeton-de-test');
    }

    public function test_un_role_autorise_peut_toujours_ouvrir_une_campagne(): void
    {
        Http::fake(['*' => Http::response(['id' => self::ID], 201)]);

        $this->withSession($this->sessionPour('comptable_matieres'))->post('/campagnes', self::campagne())
            ->assertRedirect('/campagnes')
            ->assertSessionHas('succes', 'Campagne ouverte.');

        Http::assertSentCount(1);
    }

    public function test_sans_session_la_connexion_reste_obligatoire(): void
    {
        Http::fake();

        $this->get('/administration')->assertRedirect('/connexion');

        Http::assertNothingSent();
    }

    private function sessionPour(string $role): array
    {
        return [
            'jwt' => 'jeton-de-test',
            'utilisateur' => ['id' => self::ID, 'name' => 'Compte de démonstration', 'role' => $role, 'ministere_code' => 'SAN'],
            'expire_le' => now()->addMinutes(15)->toDateTimeString(),
        ];
    }

    private static function campagne(): array
    {
        return [
            'nom' => 'Inventaire 2026',
            'date_debut' => '2026-11-01',
            'date_fin' => '2026-11-30',
            'structure_id' => self::ID,
        ];
    }

    private function photo(): UploadedFile
    {
        // PNG d'un pixel : ne dépend pas de l'extension GD pour fabriquer la photo.
        return UploadedFile::fake()->createWithContent('controle.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='
        ));
    }
}

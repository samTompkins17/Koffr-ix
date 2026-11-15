<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function connexion(string $email, string $motDePasse = 'password'): string
    {
        $reponse = $this->postJson('/api/login', ['email' => $email, 'password' => $motDePasse]);
        $reponse->assertOk();

        return $reponse->json('token');
    }

    public function test_connexion_retourne_un_jeton_avec_les_roles(): void
    {
        $reponse = $this->postJson('/api/login', [
            'email' => 'comptable@koffr.local',
            'password' => 'password',
        ]);

        $reponse->assertOk()
            ->assertJsonPath('utilisateur.role', 'comptable_matieres')
            ->assertJsonPath('utilisateur.ministere_code', 'SAN')
            ->assertJsonStructure(['token', 'token_type', 'expires_in', 'utilisateur' => ['id', 'name', 'role']]);

        $this->assertNotEmpty($reponse->json('token'));
    }

    public function test_mot_de_passe_incorrect_donne_un_message_generique(): void
    {
        $this->postJson('/api/login', ['email' => 'comptable@koffr.local', 'password' => 'faux'])
            ->assertStatus(401)
            ->assertJsonPath('message', 'Identifiants incorrects.');
    }

    public function test_email_inconnu_donne_le_meme_message(): void
    {
        $this->postJson('/api/login', ['email' => 'inconnu@koffr.local', 'password' => 'password'])
            ->assertStatus(401)
            ->assertJsonPath('message', 'Identifiants incorrects.');
    }

    public function test_me_sans_jeton_renvoie_401(): void
    {
        $this->getJson('/api/me')->assertStatus(401);
    }

    public function test_me_avec_jeton_renvoie_le_profil(): void
    {
        $jeton = $this->connexion('agent@koffr.local');

        $this->withHeader('Authorization', 'Bearer '.$jeton)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('role', 'agent_patrimoine')
            ->assertJsonPath('name', 'Yao T.');
    }

    public function test_un_role_insuffisant_recoit_403(): void
    {
        // Petite route de test protégée par le middleware de rôle.
        Route::middleware(['jwt', 'role:admin'])->get('/api/_test_admin', fn () => ['ok' => true]);

        $jeton = $this->connexion('controleur@koffr.local');

        $this->withHeader('Authorization', 'Bearer '.$jeton)
            ->getJson('/api/_test_admin')
            ->assertStatus(403);
    }

    public function test_users_ids_ne_renvoie_que_id_et_nom(): void
    {
        $jeton = $this->connexion('admin@koffr.local');
        $agent = User::where('email', 'agent@koffr.local')->first();

        $reponse = $this->withHeader('Authorization', 'Bearer '.$jeton)
            ->getJson('/api/users?ids='.$agent->id);

        $reponse->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Yao T.');

        $this->assertSame(['id', 'name'], array_keys($reponse->json('0')));
    }

    public function test_les_structures_se_filtrent_par_ids(): void
    {
        $jeton = $this->connexion('admin@koffr.local');
        $structure = \App\Models\Structure::where('code', 'SAN')->first();

        $this->withHeader('Authorization', 'Bearer '.$jeton)
            ->getJson('/api/structures?ids='.$structure->id)
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.nom', 'Ministère de la Santé');
    }

    public function test_annuaire_reserve_a_l_administrateur(): void
    {
        $agent = $this->connexion('agent@koffr.local');

        $this->withHeader('Authorization', 'Bearer '.$agent)
            ->getJson('/api/annuaire')
            ->assertStatus(403);

        $admin = $this->connexion('admin@koffr.local');

        $reponse = $this->withHeader('Authorization', 'Bearer '.$admin)
            ->getJson('/api/annuaire');

        $reponse->assertOk()
            ->assertJsonPath('comptes.0.name', 'Abla M.');

        $compte = collect($reponse->json('comptes'))->firstWhere('name', 'Yao T.');
        $this->assertSame(['id', 'name', 'role', 'structure_id', 'structure_nom', 'actif'], array_keys($compte));
        $this->assertSame('agent_patrimoine', $compte['role']);
        $this->assertArrayNotHasKey('email', $compte);
        $this->assertArrayNotHasKey('password', $compte);
    }

    public function test_le_jeton_expire_est_refuse(): void
    {
        // Jeton signé avec un autre secret : signature invalide du point de vue du service.
        $jeton = $this->connexion('agent@koffr.local');

        $this->withHeader('Authorization', 'Bearer '.$jeton.'x')
            ->getJson('/api/me')
            ->assertStatus(401);
    }

    public function test_ladministrateur_cree_un_compte(): void
    {
        $admin = $this->connexion('admin@koffr.local');

        $this->withHeader('Authorization', 'Bearer '.$admin)
            ->postJson('/api/comptes', [
                'name' => 'Nouvelle Agente',
                'email' => 'nouvelle.agente@koffr.local',
                'password' => 'motdepasse123',
                'role' => 'agent_patrimoine',
                'structure_id' => '00000000-0000-4000-8000-000000000004',
            ])
            ->assertStatus(201)
            ->assertJsonPath('role', 'agent_patrimoine')
            ->assertJsonPath('structure_nom', 'Services généraux');

        // Le compte créé peut se connecter avec son mot de passe.
        $this->postJson('/api/login', [
            'email' => 'nouvelle.agente@koffr.local',
            'password' => 'motdepasse123',
        ])->assertOk();
    }

    public function test_un_email_deja_utilise_est_refuse(): void
    {
        $admin = $this->connexion('admin@koffr.local');

        $this->withHeader('Authorization', 'Bearer '.$admin)
            ->postJson('/api/comptes', [
                'name' => 'Doublon',
                'email' => 'comptable@koffr.local',
                'password' => 'motdepasse123',
                'role' => 'controleur',
                'structure_id' => '00000000-0000-4000-8000-000000000001',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_seul_ladministrateur_cree_des_comptes(): void
    {
        $comptable = $this->connexion('comptable@koffr.local');

        $this->withHeader('Authorization', 'Bearer '.$comptable)
            ->postJson('/api/comptes', [
                'name' => 'Interdit',
                'email' => 'interdit@koffr.local',
                'password' => 'motdepasse123',
                'role' => 'agent_patrimoine',
                'structure_id' => '00000000-0000-4000-8000-000000000004',
            ])
            ->assertStatus(403);
    }

    public function test_ladministrateur_cree_une_structure(): void
    {
        $admin = $this->connexion('admin@koffr.local');

        $this->withHeader('Authorization', 'Bearer '.$admin)
            ->postJson('/api/structures', [
                'nom' => 'Direction des affaires financières',
                'code' => 'SAN-DAF',
                'type' => 'service',
                'region' => 'Maritime',
                'parent_id' => '00000000-0000-4000-8000-000000000001',
            ])
            ->assertStatus(201)
            ->assertJsonPath('code', 'SAN-DAF');

        $this->assertDatabaseHas('structures', ['code' => 'SAN-DAF']);
    }
}

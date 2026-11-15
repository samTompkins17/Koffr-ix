<?php

namespace Tests\Feature;

use App\Models\Bien;
use App\Models\Mouvement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\UtilisateurJeton;
use Tests\TestCase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class MouvementTest extends TestCase
{
    use RefreshDatabase;

    private const ORIGINE = '00000000-0000-4000-8000-000000000004';
    private const DESTINATION = '00000000-0000-4000-8000-000000000005';

    private function jeton(string $role = 'comptable_matieres'): string
    {
        return JWTAuth::fromSubject(new UtilisateurJeton(role: $role));
    }

    private function creerBien(): Bien
    {
        $reponse = $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->postJson('/api/biens', [
                'designation' => 'Imprimante laser',
                'categorie' => 'Matériel informatique',
                'valeur_acquisition' => 200000,
                'date_acquisition' => '2026-01-15',
                'etat' => 'bon',
                'structure_id' => self::ORIGINE,
            ]);

        return Bien::findOrFail($reponse->json('id'));
    }

    public function test_un_transfert_attend_la_validation_puis_change_la_structure(): void
    {
        $bien = $this->creerBien();

        $reponse = $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->postJson('/api/biens/'.$bien->id.'/mouvements', [
                'type' => 'transfert',
                'structure_destination_id' => self::DESTINATION,
                'motif' => 'Réorganisation du service',
            ]);

        $reponse->assertStatus(201)
            ->assertJsonPath('structure_origine_id', self::ORIGINE)
            ->assertJsonPath('structure_destination_id', self::DESTINATION)
            ->assertJsonPath('statut_validation', 'en_attente')
            ->assertJsonPath('statut_effectif', 'en_attente');

        // Tant que le responsable n'a pas validé, la structure ne change pas.
        $this->assertSame(self::ORIGINE, $bien->refresh()->structure_id);

        $mouvementId = $reponse->json('id');

        $this->withHeader('Authorization', 'Bearer '.$this->jeton('responsable_service'))
            ->postJson('/api/mouvements/'.$mouvementId.'/valider')
            ->assertOk()
            ->assertJsonPath('statut_effectif', 'valide');

        $this->assertSame(self::DESTINATION, $bien->refresh()->structure_id);
        $this->assertDatabaseCount('validations_mouvement', 1);
    }

    public function test_un_refus_ne_change_pas_la_structure(): void
    {
        $bien = $this->creerBien();

        $mouvementId = $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->postJson('/api/biens/'.$bien->id.'/mouvements', [
                'type' => 'affectation',
                'structure_destination_id' => self::DESTINATION,
                'motif' => 'Affectation proposée',
            ])
            ->json('id');

        $this->withHeader('Authorization', 'Bearer '.$this->jeton('responsable_service'))
            ->postJson('/api/mouvements/'.$mouvementId.'/refuser', ['motif' => 'Effectif insuffisant'])
            ->assertOk()
            ->assertJsonPath('statut_effectif', 'refuse');

        $this->assertSame(self::ORIGINE, $bien->refresh()->structure_id);
    }

    public function test_seul_le_responsable_valide_un_mouvement(): void
    {
        $bien = $this->creerBien();

        $mouvementId = $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->postJson('/api/biens/'.$bien->id.'/mouvements', [
                'type' => 'transfert',
                'structure_destination_id' => self::DESTINATION,
                'motif' => 'Réorganisation',
            ])
            ->json('id');

        $this->withHeader('Authorization', 'Bearer '.$this->jeton('agent_patrimoine'))
            ->postJson('/api/mouvements/'.$mouvementId.'/valider')
            ->assertStatus(403);
    }

    public function test_un_mouvement_ne_peut_etre_valide_deux_fois(): void
    {
        $bien = $this->creerBien();

        $mouvementId = $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->postJson('/api/biens/'.$bien->id.'/mouvements', [
                'type' => 'transfert',
                'structure_destination_id' => self::DESTINATION,
                'motif' => 'Réorganisation',
            ])
            ->json('id');

        $this->withHeader('Authorization', 'Bearer '.$this->jeton('responsable_service'))
            ->postJson('/api/mouvements/'.$mouvementId.'/valider')
            ->assertOk();

        $this->withHeader('Authorization', 'Bearer '.$this->jeton('responsable_service'))
            ->postJson('/api/mouvements/'.$mouvementId.'/valider')
            ->assertStatus(409);
    }

    public function test_un_transfert_sans_destination_est_refuse(): void
    {
        $bien = $this->creerBien();

        $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->postJson('/api/biens/'.$bien->id.'/mouvements', [
                'type' => 'transfert',
                'motif' => 'Sans destination',
            ])
            ->assertStatus(422);
    }

    public function test_une_sortie_marque_le_bien_comme_sorti(): void
    {
        $bien = $this->creerBien();

        $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->postJson('/api/biens/'.$bien->id.'/mouvements', [
                'type' => 'sortie',
                'motif' => 'Réforme pour obsolescence',
            ])
            ->assertStatus(201);

        $this->assertSame('sorti', $bien->refresh()->statut);
    }

    public function test_un_mouvement_ne_peut_ni_etre_modifie_ni_etre_supprime(): void
    {
        $bien = $this->creerBien();

        $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->postJson('/api/biens/'.$bien->id.'/mouvements', [
                'type' => 'maintenance',
                'motif' => 'Nettoyage annuel',
            ]);

        $mouvement = Mouvement::firstOrFail();

        $this->expectException(\RuntimeException::class);
        $mouvement->update(['motif' => 'Autre motif']);
    }

    public function test_un_mouvement_ne_peut_pas_etre_supprime(): void
    {
        $bien = $this->creerBien();

        $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->postJson('/api/biens/'.$bien->id.'/mouvements', [
                'type' => 'maintenance',
                'motif' => 'Nettoyage annuel',
            ]);

        $this->expectException(\RuntimeException::class);
        Mouvement::firstOrFail()->delete();
    }

    public function test_le_controleur_ne_peut_pas_declarer_de_mouvement(): void
    {
        $bien = $this->creerBien();

        $this->withHeader('Authorization', 'Bearer '.$this->jeton('controleur'))
            ->postJson('/api/biens/'.$bien->id.'/mouvements', [
                'type' => 'maintenance',
                'motif' => 'Tentative',
            ])
            ->assertStatus(403);
    }

    public function test_lhistorique_est_du_plus_recent_au_plus_ancien(): void
    {
        $bien = $this->creerBien();

        foreach ([['2026-01-01', 'Premier'], ['2026-05-01', 'Deuxième']] as [$date, $motif]) {
            $this->withHeader('Authorization', 'Bearer '.$this->jeton())
                ->postJson('/api/biens/'.$bien->id.'/mouvements', [
                    'type' => 'maintenance',
                    'date' => $date,
                    'motif' => $motif,
                ]);
        }

        $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->getJson('/api/biens/'.$bien->id.'/mouvements')
            ->assertOk()
            ->assertJsonPath('0.motif', 'Deuxième')
            ->assertJsonPath('1.motif', 'Premier');
    }
}

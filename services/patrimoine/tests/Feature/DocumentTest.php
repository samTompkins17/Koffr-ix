<?php

namespace Tests\Feature;

use App\Models\Bien;
use App\Models\Document;
use App\Models\JournalAudit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\Support\UtilisateurJeton;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    private const PDF = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n";

    protected function setUp(): void
    {
        parent::setUp();

        // Aucun test ne touche aux documents de démonstration.
        Storage::fake('local');
        Storage::fake('public');
    }

    private function jeton(string $role = 'comptable_matieres'): string
    {
        return JWTAuth::fromSubject(new UtilisateurJeton(role: $role));
    }

    private function creerBien(): Bien
    {
        $bien = new Bien([
            'designation' => 'Ordinateur de test',
            'categorie' => 'Matériel informatique',
            'valeur_acquisition' => 500000,
            'date_acquisition' => '2026-01-01',
            'etat' => 'bon',
            'structure_id' => '00000000-0000-4000-8000-000000000004',
        ]);
        $bien->code_unique = 'PAT-SAN-2026-'.Str::uuid();
        $bien->save();

        return $bien;
    }

    private function ajouterDocument(Bien $bien, string $type = 'facture'): Document
    {
        $reponse = $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->postJson('/api/biens/'.$bien->id.'/documents', [
                'type' => $type,
                'fichier' => UploadedFile::fake()->createWithContent('facture.pdf', self::PDF),
            ])
            ->assertCreated()
            ->assertJsonMissingPath('chemin');

        return Document::findOrFail($reponse->json('id'));
    }

    public function test_les_trois_routes_exigent_un_jeton(): void
    {
        $bien = $this->creerBien();
        $document = $this->ajouterDocument($bien);
        $this->flushHeaders();

        $url = '/api/biens/'.$bien->id.'/documents';
        $this->getJson($url)->assertUnauthorized();
        $this->postJson($url, [
            'type' => 'facture',
            'fichier' => UploadedFile::fake()->createWithContent('facture.pdf', self::PDF),
        ])->assertUnauthorized();
        $this->get($url.'/'.$document->id)->assertUnauthorized();
        $this->assertDatabaseCount('documents', 1);
    }

    public static function ajoutsAutorises(): array
    {
        return [
            ['admin', 'pdf', 'application/pdf'],
            ['admin', 'jpg', 'image/jpeg'],
            ['admin', 'png', 'image/png'],
            ['comptable_matieres', 'pdf', 'application/pdf'],
            ['comptable_matieres', 'jpg', 'image/jpeg'],
            ['comptable_matieres', 'png', 'image/png'],
            ['comptable_matieres', 'jpeg', 'image/jpeg'],
        ];
    }

    #[DataProvider('ajoutsAutorises')]
    public function test_admin_et_comptable_ajoutent_des_fichiers_prives(string $role, string $extension, string $mime): void
    {
        $bien = $this->creerBien();
        $source = $extension === 'pdf'
            ? UploadedFile::fake()->createWithContent('piece.pdf', self::PDF)
            : UploadedFile::fake()->image('piece.'.$extension);
        // Vrai UploadedFile : le MIME est détecté dans le contenu, pas simulé par le nom.
        $fichier = new UploadedFile($source->getPathname(), 'pièce.'.$extension, 'application/octet-stream', null, true);
        $contenu = file_get_contents($source->getPathname());

        $reponse = $this->withHeader('Authorization', 'Bearer '.$this->jeton($role))
            ->postJson('/api/biens/'.$bien->id.'/documents', [
                'type' => 'facture',
                'fichier' => $fichier,
                // Ces métadonnées doivent être ignorées, même si elles sont fournies.
                'bien_id' => (string) Str::uuid(),
                'auteur_id' => (string) Str::uuid(),
                'chemin' => 'public/pirate.php',
                'mime_type' => 'text/html',
                'taille' => 1,
                'nom_original' => 'pirate.php',
            ]);

        $reponse->assertCreated()
            ->assertJsonPath('bien_id', $bien->id)
            ->assertJsonPath('type', 'facture')
            ->assertJsonPath('nom_original', 'pièce.'.$extension)
            ->assertJsonPath('mime_type', $mime)
            ->assertJsonPath('taille', strlen($contenu))
            ->assertJsonPath('auteur_id', '00000000-0000-4000-8000-000000000999')
            ->assertJsonStructure(['id', 'bien_id', 'type', 'nom_original', 'mime_type', 'taille', 'auteur_id', 'created_at', 'updated_at'])
            ->assertJsonMissingPath('chemin');

        $document = Document::findOrFail($reponse->json('id'));
        $this->assertTrue(Str::isUuid($document->id));
        $this->assertMatchesRegularExpression('~^documents/[a-zA-Z0-9]{40}\.(pdf|jpg|jpeg|png)$~', $document->chemin);
        $this->assertArrayNotHasKey('chemin', $document->toArray());
        $this->assertSame($bien->id, $document->bien->id);
        Storage::disk('local')->assertExists($document->chemin);
        $this->assertSame('private', Storage::disk('local')->getVisibility($document->chemin));
        $this->assertSame($contenu, Storage::disk('local')->get($document->chemin));
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertDatabaseHas('journal_audit', [
            'utilisateur_id' => $document->auteur_id,
            'action' => 'document_ajoute',
            'objet' => $bien->id,
            'detail' => 'Document '.$document->id.' (facture).',
        ]);
    }

    public static function rolesSansAjout(): array
    {
        return [['agent_patrimoine'], ['responsable_service'], ['controleur']];
    }

    #[DataProvider('rolesSansAjout')]
    public function test_les_autres_roles_ne_peuvent_pas_ajouter(string $role): void
    {
        $bien = $this->creerBien();
        $this->withHeader('Authorization', 'Bearer '.$this->jeton($role))
            ->postJson('/api/biens/'.$bien->id.'/documents', [
                'type' => 'photo',
                'fichier' => UploadedFile::fake()->image('photo.jpg'),
            ])->assertForbidden();

        $this->assertDatabaseCount('documents', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public static function rolesLecture(): array
    {
        return [['admin'], ['comptable_matieres'], ['agent_patrimoine'], ['responsable_service'], ['controleur']];
    }

    #[DataProvider('rolesLecture')]
    public function test_tous_les_roles_lisent_et_telechargent_sans_chemin_public(string $role): void
    {
        $bien = $this->creerBien();
        $document = $this->ajouterDocument($bien);
        $autre = $this->ajouterDocument($this->creerBien());
        $this->withHeader('Authorization', 'Bearer '.$this->jeton($role));

        $this->getJson('/api/biens/'.$bien->id.'/documents')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $document->id)
            ->assertJsonMissing(['id' => $autre->id])
            ->assertJsonMissingPath('0.chemin');

        $reponse = $this->get('/api/biens/'.$bien->id.'/documents/'.$document->id)
            ->assertOk()
            ->assertDownload('facture.pdf')
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('no-store', $reponse->headers->get('Cache-Control'));
        $this->assertSame(self::PDF, $reponse->streamedContent());

        $this->flushHeaders();
        // La route de stockage Laravel exige une signature : pas d'accès public direct.
        $this->get('/storage/'.$document->chemin)->assertForbidden();
        $this->get('/documents/'.$document->id)->assertNotFound();
    }

    public function test_la_liste_est_vide_puis_triee_du_plus_recent_au_plus_ancien(): void
    {
        $bien = $this->creerBien();
        $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->getJson('/api/biens/'.$bien->id.'/documents')->assertExactJson([]);

        $premier = $this->ajouterDocument($bien);
        $this->travel(1)->minutes();
        $deuxieme = $this->ajouterDocument($bien);
        $this->travelBack();
        // Le jeton créé dans le futur n'est pas encore valide après travelBack().
        $this->withHeader('Authorization', 'Bearer '.$this->jeton());

        $this->getJson('/api/biens/'.$bien->id.'/documents')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.id', $deuxieme->id)
            ->assertJsonPath('1.id', $premier->id);
        $this->assertNotSame($premier->chemin, $deuxieme->chemin);
        Storage::disk('local')->assertExists([$premier->chemin, $deuxieme->chemin]);
    }

    public static function typesAutorises(): array
    {
        return [['facture'], ['acte'], ['titre'], ['photo'], ['autre']];
    }

    #[DataProvider('typesAutorises')]
    public function test_les_cinq_types_metier_sont_acceptes(string $type): void
    {
        $document = $this->ajouterDocument($this->creerBien(), $type);
        $this->assertSame($type, $document->type);
    }

    public function test_le_type_et_le_fichier_sont_obligatoires_et_valides(): void
    {
        $bien = $this->creerBien();
        $this->withHeader('Authorization', 'Bearer '.$this->jeton());
        $url = '/api/biens/'.$bien->id.'/documents';

        $this->postJson($url, [])->assertUnprocessable()->assertJsonValidationErrors(['type', 'fichier']);
        $this->postJson($url, ['type' => 'contrat', 'fichier' => 'pas un fichier'])
            ->assertUnprocessable()->assertJsonValidationErrors(['type', 'fichier']);
        $this->postJson($url, [
            'type' => 'contrat',
            'fichier' => UploadedFile::fake()->createWithContent('piece.pdf', self::PDF),
        ])->assertUnprocessable()->assertJsonValidationErrors('type');
        $this->assertDatabaseCount('documents', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public static function formatsInterdits(): array
    {
        return [
            'svg' => ['dessin.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'],
            'html' => ['page.html', '<html><body>Interdit</body></html>'],
            'script' => ['script.php', '<?php print("interdit");'],
            'faux pdf' => ['piece.pdf', '<html><body>Faux PDF</body></html>'],
            'faux jpg' => ['photo.jpg', '<?php print("interdit");'],
            'faux png' => ['photo.png', 'Ce fichier est du texte.'],
            'pdf extension html' => ['piece.html', self::PDF],
            'pdf extension svg' => ['piece.svg', self::PDF],
            'pdf extension script' => ['piece.php', self::PDF],
            'double extension script' => ['piece.pdf.php', self::PDF],
            'sans extension' => ['piece', self::PDF],
        ];
    }

    #[DataProvider('formatsInterdits')]
    public function test_les_formats_et_extensions_interdits_sont_refuses(string $nom, string $contenu): void
    {
        $bien = $this->creerBien();
        $source = UploadedFile::fake()->createWithContent($nom, $contenu);
        // Le client prétend toujours envoyer du PDF : seul le contenu réel fait foi.
        $fichier = new UploadedFile($source->getPathname(), $nom, 'application/pdf', null, true);

        $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->postJson('/api/biens/'.$bien->id.'/documents', ['type' => 'autre', 'fichier' => $fichier])
            ->assertUnprocessable()->assertJsonValidationErrors('fichier');

        $this->assertDatabaseCount('documents', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_la_limite_est_de_10240_ko_inclus(): void
    {
        $bien = $this->creerBien();
        $this->withHeader('Authorization', 'Bearer '.$this->jeton());
        $url = '/api/biens/'.$bien->id.'/documents';

        $this->postJson($url, [
            'type' => 'acte',
            'fichier' => UploadedFile::fake()->createWithContent('limite.pdf', str_pad(self::PDF, 10240 * 1024, ' ')),
        ])->assertCreated()->assertJsonPath('taille', 10240 * 1024);
        $this->postJson($url, [
            'type' => 'acte',
            'fichier' => UploadedFile::fake()->createWithContent('trop-grand.pdf', str_pad(self::PDF, 10240 * 1024 + 1, ' ')),
        ])->assertUnprocessable()->assertJsonValidationErrors('fichier');

        $this->assertDatabaseCount('documents', 1);
        $this->assertCount(1, Storage::disk('local')->allFiles());
    }

    public function test_le_nom_original_est_nettoye_pour_les_entetes(): void
    {
        $bien = $this->creerBien();
        $source = UploadedFile::fake()->createWithContent('piece.pdf', self::PDF);
        $fichier = new UploadedFile($source->getPathname(), "C:\\secret\\facture\"\r\nX-Test: 1%.pdf", 'application/pdf', null, true);
        $reponse = $this->withHeader('Authorization', 'Bearer '.$this->jeton())
            ->postJson('/api/biens/'.$bien->id.'/documents', ['type' => 'facture', 'fichier' => $fichier])
            ->assertCreated()
            ->assertJsonPath('nom_original', 'factureX-Test 1.pdf');

        $telechargement = $this->get('/api/biens/'.$bien->id.'/documents/'.$reponse->json('id'))
            ->assertOk()->assertDownload('factureX-Test 1.pdf')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertFalse($telechargement->headers->has('X-Test'));
        $this->assertSame(self::PDF, $telechargement->streamedContent());
    }

    public function test_un_document_dun_autre_bien_est_introuvable(): void
    {
        $bien = $this->creerBien();
        $document = $this->ajouterDocument($bien);
        $autreBien = $this->creerBien();

        $this->getJson('/api/biens/'.$autreBien->id.'/documents/'.$document->id)->assertNotFound();
        $this->getJson('/api/biens/'.$bien->id.'/documents/'.Str::uuid())->assertNotFound();
        $this->getJson('/api/biens/'.Str::uuid().'/documents/'.$document->id)->assertNotFound();
        $this->getJson('/api/biens/'.$bien->id.'/documents/invalide')->assertNotFound();
    }

    public function test_un_bien_absent_ne_permet_ni_liste_ni_ajout(): void
    {
        $url = '/api/biens/'.Str::uuid().'/documents';
        $this->withHeader('Authorization', 'Bearer '.$this->jeton());
        $this->getJson($url)->assertNotFound();
        $this->postJson($url, [
            'type' => 'facture',
            'fichier' => UploadedFile::fake()->createWithContent('piece.pdf', self::PDF),
        ])->assertNotFound();
        $this->assertDatabaseCount('documents', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_un_fichier_manquant_retourne_404_sans_chemin(): void
    {
        $bien = $this->creerBien();
        $document = $this->ajouterDocument($bien);
        Storage::disk('local')->delete($document->chemin);

        $this->getJson('/api/biens/'.$bien->id.'/documents/'.$document->id)
            ->assertNotFound()->assertDontSee($document->chemin);
    }

    public function test_un_document_ne_peut_pas_etre_modifie(): void
    {
        $document = $this->ajouterDocument($this->creerBien());
        $this->expectException(RuntimeException::class);
        $document->update(['type' => 'autre']);
    }

    public function test_un_document_ne_peut_pas_etre_supprime(): void
    {
        $document = $this->ajouterDocument($this->creerBien());
        $this->expectException(RuntimeException::class);
        $document->delete();
    }

    public function test_aucune_route_de_modification_ou_suppression_nexiste(): void
    {
        $bien = $this->creerBien();
        $document = $this->ajouterDocument($bien);
        $this->withHeader('Authorization', 'Bearer '.$this->jeton('admin'));
        $url = '/api/biens/'.$bien->id.'/documents';

        foreach ([$url, $url.'/'.$document->id] as $cible) {
            $this->putJson($cible, ['type' => 'autre'])->assertStatus(405);
            $this->patchJson($cible, ['type' => 'autre'])->assertStatus(405);
            $this->deleteJson($cible)->assertStatus(405);
        }
        $this->assertSame('facture', $document->fresh()->type);
        Storage::disk('local')->assertExists($document->chemin);
    }

    public function test_un_echec_du_journal_annule_lajout_et_nettoie_le_fichier(): void
    {
        $bien = $this->creerBien();
        // Simuler une panne après l'insertion du document, dans la même transaction.
        JournalAudit::creating(function () {
            throw new RuntimeException('Panne simulée du journal.');
        });

        try {
            $this->withHeader('Authorization', 'Bearer '.$this->jeton())
                ->postJson('/api/biens/'.$bien->id.'/documents', [
                    'type' => 'facture',
                    'fichier' => UploadedFile::fake()->createWithContent('piece.pdf', self::PDF),
                ])
                ->assertStatus(500)
                ->assertExactJson(['message' => 'Le document n’a pas pu être enregistré.']);
        } finally {
            JournalAudit::flushEventListeners();
            JournalAudit::clearBootedModels();
        }

        $this->assertDatabaseCount('documents', 0);
        $this->assertDatabaseCount('journal_audit', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }
}

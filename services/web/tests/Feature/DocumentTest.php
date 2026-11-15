<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    private string $bien = '00000000-0000-4000-8000-000000000001';

    private string $document = '00000000-0000-4000-8000-000000000002';

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->withSession(['jwt' => 'jeton-test', 'utilisateur' => ['name' => 'Test', 'role' => 'comptable_matieres']]);
    }

    public function test_un_document_est_transmis_en_multipart_avec_le_jeton(): void
    {
        Http::fake(['*/api/patrimoine/biens/*/documents' => Http::response(['id' => $this->document], 201)]);
        $fichier = UploadedFile::fake()->createWithContent('facture.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
        $this->post('/biens/'.$this->bien.'/documents', ['type' => 'facture', 'fichier' => $fichier])
            ->assertRedirect('/biens/'.$this->bien)->assertSessionHas('succes');
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer jeton-test') && $request->hasFile('fichier'));
    }

    public function test_un_format_interdit_ou_trop_gros_est_refuse(): void
    {
        Http::fake();
        $this->post('/biens/'.$this->bien.'/documents', ['type' => 'facture', 'fichier' => UploadedFile::fake()->create('script.html', 1, 'text/html')])
            ->assertSessionHasErrors('fichier');
        $this->post('/biens/'.$this->bien.'/documents', ['type' => 'facture', 'fichier' => UploadedFile::fake()->create('grand.pdf', 10241, 'application/pdf')])
            ->assertSessionHasErrors('fichier');
        Http::assertNothingSent();
    }

    public function test_un_controleur_ne_peut_pas_ajouter_un_document(): void
    {
        Http::fake();
        $this->withSession(['utilisateur' => ['role' => 'controleur']])->post('/biens/'.$this->bien.'/documents')->assertForbidden()->assertSee('Accès refusé');
        Http::assertNothingSent();
    }

    public function test_le_telechargement_est_prive(): void
    {
        Http::fake(['*/documents/'.$this->document => Http::response('%PDF-1.4 contenu', 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename=facture.pdf'])]);
        $this->get('/biens/'.$this->bien.'/documents/'.$this->document)->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Cache-Control', 'no-store, private')->assertSee('%PDF-1.4');
        $this->flushSession();
        $this->get('/biens/'.$this->bien.'/documents/'.$this->document)->assertRedirect('/connexion');
    }

    public function test_expiration_et_document_absent(): void
    {
        Http::fake(['*/documents/*' => Http::sequence()->push([], 404)->push([], 401)]);
        $this->get('/biens/'.$this->bien.'/documents/'.$this->document)->assertNotFound();
        $this->get('/biens/'.$this->bien.'/documents/'.$this->document)->assertRedirect('/session-expiree');
    }
}

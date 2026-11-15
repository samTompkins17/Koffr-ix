<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;
use ZipArchive;

class EtiquetteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->withSession(['jwt' => 'jeton', 'utilisateur' => ['role' => 'comptable_matieres', 'name' => 'Test']]);
    }

    private function preparer(int $nombre): array
    {
        $biens = [];
        for ($i = 1; $i <= $nombre; $i++) {
            $biens[] = ['id' => (string) Str::uuid(), 'code_unique' => 'PAT-SAN-2026-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT), 'designation' => 'Ordinateur '.$i];
        }
        Http::fake([
            '*/api/patrimoine/biens?*' => Http::response(['data' => $biens]),
            '*/qrcode' => Http::response('<svg xmlns="http://www.w3.org/2000/svg" width="320" height="320"><path d="M0 0h320v320H0z"/></svg>', 200, ['Content-Type' => 'image/svg+xml']),
        ]);

        return array_column($biens, 'id');
    }

    public function test_selection_obligatoire_et_uuid_valides(): void
    {
        Http::fake();
        $this->from('/biens')->get('/biens/etiquettes')->assertRedirect('/biens')->assertSessionHasErrors('ids');
        $this->get('/biens/etiquettes?ids[]=invalide')->assertSessionHasErrors('ids.0');
        Http::assertNothingSent();
    }

    public function test_apercu_deux_planches_pour_treize_biens(): void
    {
        $ids = $this->preparer(13);
        $this->get('/biens/etiquettes?'.http_build_query(['ids' => $ids]))->assertOk()
            ->assertSee('Planche 2 sur 2')->assertSee('PAT-SAN-2026-0013')->assertSee('Imprimer la planche (PDF)');
    }

    public function test_pdf_reel_et_vectoriel_avec_tous_les_biens(): void
    {
        $ids = $this->preparer(13);
        $pdf = $this->get('/biens/etiquettes?'.http_build_query(['ids' => $ids, 'format' => 'pdf']))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $pdf->getContent());

        $zipReponse = $this->get('/biens/etiquettes?'.http_build_query(['ids' => $ids, 'format' => 'svg']))
            ->assertOk()->assertHeader('Content-Type', 'application/zip');
        $chemin = tempnam(sys_get_temp_dir(), 'test-planche-');
        try {
            file_put_contents($chemin, $zipReponse->getContent());
            $zip = new ZipArchive;
            $this->assertTrue($zip->open($chemin));
            $this->assertSame(2, $zip->numFiles);
            $this->assertStringContainsString('PAT-SAN-2026-0013', $zip->getFromName('planche-2.svg'));
            $zip->close();
        } finally {
            unlink($chemin);
        }
    }

    public function test_erreur_api_ne_produit_pas_un_document_partiel(): void
    {
        $id = (string) Str::uuid();
        Http::fake(['*' => Http::sequence()->push([], 503)->push([], 401)]);
        $this->get('/biens/etiquettes?'.http_build_query(['ids' => [$id], 'format' => 'pdf']))->assertStatus(503);
        $this->get('/biens/etiquettes?'.http_build_query(['ids' => [$id]]))->assertRedirect('/session-expiree');
    }

    public function test_bien_absent_et_doublons_refuses(): void
    {
        $id = (string) Str::uuid();
        Http::fake(['*' => Http::response(['data' => []])]);
        $this->get('/biens/etiquettes?'.http_build_query(['ids' => [$id]]))->assertNotFound();
        $this->get('/biens/etiquettes?'.http_build_query(['ids' => [$id, $id]]))->assertSessionHasErrors('ids.0');
    }
}

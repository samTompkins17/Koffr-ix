<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BienController;
use App\Http\Controllers\CampagneController;
use App\Http\Controllers\CarteController;
use App\Http\Controllers\ControleController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EcartController;
use App\Http\Controllers\EtiquetteController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\ProcesVerbalController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\TableauDeBordController;
use App\Http\Controllers\TransfertController;
use App\Http\Controllers\VueNationaleController;
use App\Http\Middleware\AssurerSession;
use Illuminate\Support\Facades\Route;

// --- Pages sans connexion ---
Route::get('/verifier', [PublicController::class, 'formulaire'])->name('public.formulaire');
Route::post('/verifier', [PublicController::class, 'rechercher'])->name('public.rechercher');
Route::get('/verifier/{code}', [PublicController::class, 'verifier'])->name('public.verifier');
Route::get('/connexion', [AuthController::class, 'formulaire'])->name('connexion');
Route::post('/connexion', [AuthController::class, 'connexion'])->name('connexion.post');
Route::get('/session-expiree', fn () => view('session-expiree'))->name('session.expiree');

// --- Pages protégées ---
Route::middleware(AssurerSession::class)->group(function () {
    Route::get('/', [TableauDeBordController::class, 'index'])->name('tableau');
    Route::post('/deconnexion', [AuthController::class, 'deconnexion'])->name('deconnexion');

    // Biens
    Route::get('/biens', [BienController::class, 'index'])->name('biens.index');
    Route::get('/biens/export.pdf', [ExportController::class, 'biens'])->name('biens.export-pdf');
    Route::get('/biens/etiquettes', [EtiquetteController::class, 'index'])->name('biens.etiquettes');
    Route::get('/biens/creer', [BienController::class, 'creer'])->name('biens.creer');
    Route::post('/biens', [BienController::class, 'enregistrer'])->name('biens.enregistrer');
    Route::get('/biens/{id}', [BienController::class, 'fiche'])->whereUuid('id')->name('biens.fiche');
    Route::get('/biens/{id}/modifier', [BienController::class, 'modifier'])->name('biens.modifier');
    Route::put('/biens/{id}', [BienController::class, 'mettreAJour'])->name('biens.mettre-a-jour');
    Route::get('/biens/{id}/etiquette', [BienController::class, 'etiquette'])->name('biens.etiquette');
    Route::get('/biens/{id}/qrcode.svg', [BienController::class, 'qrCode'])->name('biens.qrcode');
    Route::post('/biens/{bienId}/documents', [DocumentController::class, 'store'])->whereUuid('bienId')->name('documents.store');
    Route::get('/biens/{bienId}/documents/{documentId}', [DocumentController::class, 'telecharger'])->whereUuid(['bienId', 'documentId'])->name('documents.telecharger');
    // Ancien lien conservé : ouvre la page des mouvements avec le bien choisi.
    Route::get('/biens/{id}/transfert', fn (string $id) => redirect()->route('transferts.index', ['bien' => $id]));

    // Affectations et transferts
    Route::get('/transferts', [TransfertController::class, 'index'])->name('transferts.index');
    Route::post('/transferts', [TransfertController::class, 'store'])->name('transferts.store');
    Route::post('/transferts/{id}/valider', [TransfertController::class, 'valider'])->name('transferts.valider');
    Route::post('/transferts/{id}/refuser', [TransfertController::class, 'refuser'])->name('transferts.refuser');

    // Campagnes d'inventaire
    Route::get('/campagnes', [CampagneController::class, 'index'])->name('campagnes.index');
    Route::post('/campagnes', [CampagneController::class, 'enregistrer'])->name('campagnes.enregistrer');
    Route::get('/campagnes/{id}', [CampagneController::class, 'fiche'])->name('campagnes.fiche');

    // Administration (annuaire, lecture seule)
    Route::get('/administration', [AdminController::class, 'index'])->name('admin.index');
    Route::post('/administration/comptes', [AdminController::class, 'creerCompte'])->name('admin.comptes');
    Route::post('/administration/structures', [AdminController::class, 'creerStructure'])->name('admin.structures');

    // Écarts et rapports
    Route::get('/ecarts', [EcartController::class, 'index'])->name('ecarts.index');
    Route::get('/ecarts/export.pdf', [ExportController::class, 'ecarts'])->name('ecarts.export-pdf');

    // Journal d'audit (lecture seule)
    Route::get('/journal', [JournalController::class, 'index'])->name('journal.index');
    Route::post('/ecarts/{id}/traitement', [EcartController::class, 'traiter'])->name('ecarts.traiter');
    // Carte, vue nationale et procès-verbaux (lecture seule)
    Route::get('/carte', [CarteController::class, 'index'])->name('carte.index');
    Route::get('/vue-nationale', [VueNationaleController::class, 'index'])->name('vue-nationale.index');
    Route::get('/proces-verbaux', [ProcesVerbalController::class, 'index'])->name('proces-verbaux.index');
    Route::get('/proces-verbaux/inventaire/{campagne}', [ProcesVerbalController::class, 'inventaire'])->name('proces-verbaux.inventaire');
    Route::get('/proces-verbaux/sortie/{bien}', [ProcesVerbalController::class, 'sortie'])->name('proces-verbaux.sortie');

    // Contrôle par scan
    Route::get('/controles/scan', [ControleController::class, 'scan'])->name('controles.scan');
    Route::post('/controles/scan', [ControleController::class, 'rechercher'])->name('controles.rechercher');
    Route::get('/controles/{code}', [ControleController::class, 'fiche'])->name('controles.fiche');
    Route::post('/controles', [ControleController::class, 'enregistrer'])->name('controles.enregistrer');
});

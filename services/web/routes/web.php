<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BienController;
use App\Http\Controllers\CampagneController;
use App\Http\Controllers\ControleController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\TableauDeBordController;
use App\Http\Middleware\AssurerSession;
use Illuminate\Support\Facades\Route;

// --- Pages sans connexion ---
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
    Route::get('/biens/export.csv', [BienController::class, 'exportCsv'])->name('biens.export');
    Route::get('/biens/creer', [BienController::class, 'creer'])->name('biens.creer');
    Route::post('/biens', [BienController::class, 'enregistrer'])->name('biens.enregistrer');
    Route::get('/biens/{id}', [BienController::class, 'fiche'])->name('biens.fiche');
    Route::get('/biens/{id}/modifier', [BienController::class, 'modifier'])->name('biens.modifier');
    Route::put('/biens/{id}', [BienController::class, 'mettreAJour'])->name('biens.mettre-a-jour');
    Route::get('/biens/{id}/transfert', [BienController::class, 'formulaireTransfert'])->name('biens.transfert');
    Route::post('/biens/{id}/transfert', [BienController::class, 'transferer'])->name('biens.transfert.post');
    Route::get('/biens/{id}/etiquette', [BienController::class, 'etiquette'])->name('biens.etiquette');
    Route::get('/biens/{id}/qrcode.svg', [BienController::class, 'qrCode'])->name('biens.qrcode');

    // Campagnes d'inventaire
    Route::get('/campagnes', [CampagneController::class, 'index'])->name('campagnes.index');
    Route::get('/campagnes/ouvrir', [CampagneController::class, 'creer'])->name('campagnes.creer');
    Route::post('/campagnes', [CampagneController::class, 'enregistrer'])->name('campagnes.enregistrer');
    Route::get('/campagnes/{id}', [CampagneController::class, 'fiche'])->name('campagnes.fiche');
    Route::get('/campagnes/{id}/ecarts', [CampagneController::class, 'ecarts'])->name('campagnes.ecarts');

    // Contrôle par scan
    Route::get('/controles/scan', [ControleController::class, 'scan'])->name('controles.scan');
    Route::post('/controles/scan', [ControleController::class, 'rechercher'])->name('controles.rechercher');
    Route::get('/controles/{code}', [ControleController::class, 'fiche'])->name('controles.fiche');
    Route::post('/controles', [ControleController::class, 'enregistrer'])->name('controles.enregistrer');
});

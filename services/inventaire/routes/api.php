<?php

use App\Http\Controllers\CampagneController;
use App\Http\Controllers\ControleController;
use App\Http\Controllers\JournalAuditController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => ['service' => 'inventaire', 'ok' => true]);

Route::middleware('jwt')->group(function () {
    Route::get('/campagnes', [CampagneController::class, 'index']);
    Route::get('/journal', [JournalAuditController::class, 'index'])
        ->middleware('role:admin,controleur');
    Route::get('/campagnes/{id}', [CampagneController::class, 'show']);
    Route::get('/campagnes/{id}/controles', [ControleController::class, 'parCampagne']);

    // Ouverture de campagne : comptable des matières uniquement.
    Route::middleware('role:comptable_matieres')->group(function () {
        Route::post('/campagnes', [CampagneController::class, 'store']);
    });

    // Liste des écarts en lecture seule : comptable et contrôleur.
    Route::middleware('role:comptable_matieres,controleur')->group(function () {
        Route::get('/ecarts', [ControleController::class, 'tousLesEcarts']);
        Route::get('/campagnes/{id}/ecarts', [ControleController::class, 'ecarts']);
    });

    // Scan de contrôle et constat : agent du patrimoine uniquement.
    Route::middleware('role:agent_patrimoine')->group(function () {
        Route::get('/controles/bien/{code}', [ControleController::class, 'parCode']);
        Route::post('/controles', [ControleController::class, 'store']);
    });
});

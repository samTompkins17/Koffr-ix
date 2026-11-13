<?php

use App\Http\Controllers\BienController;
use App\Http\Controllers\MouvementController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\QrCodeController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => ['service' => 'patrimoine', 'ok' => true]);

// Vérification publique : pas de JWT.
Route::get('/public/biens/{code}', [PublicController::class, 'bien']);

Route::middleware('jwt')->group(function () {
    Route::get('/biens', [BienController::class, 'index']);
    Route::get('/stats', [BienController::class, 'stats']);
    Route::get('/biens/code/{code}', [BienController::class, 'parCode']);
    Route::get('/biens/{id}', [BienController::class, 'show']);
    Route::get('/biens/{id}/qrcode', [QrCodeController::class, 'svg']);
    Route::get('/biens/{id}/etiquette', [QrCodeController::class, 'etiquette']);
    Route::get('/biens/{id}/mouvements', [MouvementController::class, 'index']);

    // Écriture réservée à l'administrateur et au comptable des matières.
    Route::middleware('role:admin,comptable_matieres')->group(function () {
        Route::post('/biens', [BienController::class, 'store']);
        Route::put('/biens/{id}', [BienController::class, 'update']);
    });

    // Déclaration de mouvement : comptable et responsable de service.
    Route::middleware('role:comptable_matieres,responsable_service')->group(function () {
        Route::post('/biens/{id}/mouvements', [MouvementController::class, 'store']);
    });
});

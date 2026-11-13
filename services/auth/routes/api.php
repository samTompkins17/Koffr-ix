<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\JournalAuditController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => ['service' => 'auth', 'ok' => true]);

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('jwt')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/structures', [AuthController::class, 'structures']);
    Route::get('/users', [AuthController::class, 'users']);
    Route::get('/hierarchie', [AuthController::class, 'hierarchie']);
    Route::get('/annuaire', [AuthController::class, 'annuaire'])->middleware('role:admin');
    Route::get('/journal', [JournalAuditController::class, 'index'])
        ->middleware('role:admin,controleur');
});

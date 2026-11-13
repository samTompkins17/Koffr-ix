<?php

use App\Exceptions\PatrimoineIndisponible;
use App\Http\Middleware\Role;
use App\Http\Middleware\VerifierJwt;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'jwt' => VerifierJwt::class,
            'role' => Role::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // patrimoine ne répond pas : le parcours de scan est dégradé, le reste
        // du service continue de fonctionner.
        $exceptions->render(fn (PatrimoineIndisponible $e) => response()->json([
            'message' => $e->getMessage(),
        ], 503));
    })->create();

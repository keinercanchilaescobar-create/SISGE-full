<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Solo CORS — sin statefulApi() para no activar sesiones/cookies
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        // Reemplazar el Authenticate global por el nuestro (devuelve null en redirectTo)
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // Siempre JSON en rutas /api/*
        $exceptions->shouldRenderJsonWhen(
            function (Request $request, \Throwable $e): bool {
                return $request->is('api/*') || $request->expectsJson();
            }
        );

        // AuthenticationException → 401 JSON, nunca redirect
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error'   => 'No autenticado. Proporciona un token válido.',
                ], 401);
            }
        });

    })->create();
    
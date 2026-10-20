<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Rôles et permissions vérifiés côté serveur (Spatie Permission) : role:admin|moderateur, permission:voir-rapports…
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
        $middleware->web(append: [\App\Http\Middleware\VerifierStatutCompte::class]);
        $middleware->redirectUsersTo(fn ($request) => $request->user()->homeRoute());
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Accès refusé par Spatie : page 403 avec un message en français.
        $exceptions->map(\Spatie\Permission\Exceptions\UnauthorizedException::class,
            fn () => new \Symfony\Component\HttpKernel\Exception\HttpException(403, 'Vous n’avez pas accès à cette page.'));
    })->create();

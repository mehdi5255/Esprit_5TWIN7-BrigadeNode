<?php

use App\Http\Middleware\VerifierStatutCompte;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Rôles et permissions vérifiés côté serveur (Spatie Permission) : role:admin|moderateur, permission:voir-rapports…
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
        $middleware->web(append: [VerifierStatutCompte::class]);
        $middleware->redirectUsersTo(fn ($request) => $request->user()->homeRoute());
        // Hébergement derrière un proxy HTTPS (Render) : liens et formulaires générés en https.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Accès refusé par Spatie : page 403 avec un message en français.
        $exceptions->map(UnauthorizedException::class,
            fn () => new HttpException(403, 'Vous n’avez pas accès à cette page.'));
    })->create();

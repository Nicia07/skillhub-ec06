<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\SsoAuthenticate;
use App\Http\Middleware\TouchInscriptionActivity;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Http\Middleware\Authenticate;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Enregistrement de l'alias pour JWT
        $middleware->alias([
            'jwt.verify' => Authenticate::class,
            'role' => EnsureRole::class,
            'sso' => SsoAuthenticate::class,
            'touch.activity' => TouchInscriptionActivity::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // JWT (tymon/jwt-auth) wraps every auth failure into UnauthorizedHttpException.
        // Distinguish "no token provided" (401) from "token present but invalid/expired" (403)
        // by inspecting the wrapped previous exception, since the middleware sets it only
        // when a JWTException (invalid/expired token) was actually caught.
        $exceptions->render(function (UnauthorizedHttpException $e, $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($e->getPrevious() instanceof JWTException) {
                return response()->json(['message' => 'Token invalide'], 403);
            }

            return response()->json(['message' => 'Token absent'], 401);
        });
    })->create();

<?php

use App\Http\Middleware\ApiKeyAuth;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\ResolveWorkspace;
use App\Http\Middleware\EnsureWorkspaceRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', api: __DIR__.'/../routes/api.php', commands: __DIR__.'/../routes/console.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['admin'=>EnsureAdmin::class,'workspace'=>ResolveWorkspace::class,'workspace.role'=>EnsureWorkspaceRole::class,'api.key'=>ApiKeyAuth::class]);
        $middleware->validateCsrfTokens(except: ['stripe/webhook']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {})
    ->create();

<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'staff'    => \App\Http\Middleware\EnsureIsStaff::class,
            'osc'      => \App\Http\Middleware\EnsureIsOsc::class,
            'readonly' => \App\Http\Middleware\ReadOnlyControleInterno::class,
            'role'       => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        /*
         * Rede de segurança para exclusão barrada por chave estrangeira.
         *
         * Os controllers já perguntam antes (ver Controller::bloqueioDeExclusao
         * e o trait ImpedeExclusaoComVinculos), com mensagem específica. Isto
         * aqui cobre o que escapar: uma FK nova, um vínculo que ninguém mapeou,
         * um caminho de exclusão criado depois. Em vez da tela de erro com SQL,
         * o usuário volta para a listagem com uma explicação.
         *
         * Filtra pelo errno 1451 do MySQL, não pelo texto: o SQLSTATE 23000
         * também cobre chave duplicada (1062), que é outro problema e não pode
         * ser silenciado aqui.
         */
        $exceptions->render(function (QueryException $e, Request $request) {
            if (($e->errorInfo[1] ?? null) !== 1451 || $request->expectsJson()) {
                return null;
            }

            report($e); // continua no log: o ideal é o controller ter avisado antes

            return back()->with(
                'error',
                'Não foi possível excluir: este registro está sendo usado por outros '
                .'no sistema. Remova os vínculos antes de tentar de novo.'
            );
        });
    })->create();

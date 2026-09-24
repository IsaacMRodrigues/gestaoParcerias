<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * A fila dos avisos por e-mail (App\Mail\Aviso). Na hospedagem não há processo
 * rodando o tempo todo: o cron da Hostinger chama `schedule:run` a cada minuto,
 * e este comando esvazia a fila e sai. `--max-time` o encerra antes do próximo
 * minuto, e `withoutOverlapping` impede dois ao mesmo tempo se um e-mail demorar.
 */
Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=50')
    ->everyMinute()
    ->withoutOverlapping();

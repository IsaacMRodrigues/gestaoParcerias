<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Fila dos avisos por e-mail: o cron da hospedagem chama schedule:run a cada minuto, e este
 * comando esvazia a fila e sai (--max-time e withoutOverlapping evitam sobreposição).
 */
Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=50')
    ->everyMinute()
    ->withoutOverlapping();

/* Certidões de "Arquivos da OSC" perto de vencer: a OSC é avisada uma vez, dias antes. */
Schedule::command('osc:avisar-vencimento-certidoes')->dailyAt('07:00');

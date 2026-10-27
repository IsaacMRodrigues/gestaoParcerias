<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Confere no Google o "não sou um robô" (reCAPTCHA v2) do formulário público. Sem chave configurada só passa
 * em desenvolvimento e nos testes; em produção, falta de chave barra o envio em vez de abrir a porta.
 */
class Recaptcha implements ValidationRule
{
    /** Roda mesmo sem o campo no envio: um robô que o omite não passa. */
    public bool $implicit = true;

    public static function configurado(): bool
    {
        return filled(config('services.recaptcha.site_key')) && filled(config('services.recaptcha.secret'));
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!self::configurado()) {
            if (!app()->environment('local', 'testing')) {
                Log::error('reCAPTCHA sem chave configurada: formulário público bloqueado.');
                $fail('A verificação anti-robô está indisponível. Tente mais tarde.');
            }

            return;
        }

        if (blank($value)) {
            $fail('Marque "Não sou um robô".');

            return;
        }

        try {
            $ok = Http::asForm()->timeout(10)->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret'   => config('services.recaptcha.secret'),
                'response' => $value,
                'remoteip' => request()->ip(),
            ])->json('success') === true;
        } catch (\Throwable $e) {
            Log::warning('reCAPTCHA sem resposta', ['erro' => $e->getMessage()]);
            $ok = false;
        }

        if (!$ok) {
            $fail('A verificação "Não sou um robô" não foi confirmada. Marque de novo.');
        }
    }
}

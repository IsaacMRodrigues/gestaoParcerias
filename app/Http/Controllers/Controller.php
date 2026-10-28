<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

abstract class Controller
{
    /**
     * Redirect com a explicação quando há vínculos que impedem a exclusão; null quando dá para apagar.
     *
     *   if ($bloqueio = $this->bloqueioDeExclusao($chamamento)) return $bloqueio;
     */
    protected function bloqueioDeExclusao(Model $registro): ?RedirectResponse
    {
        $motivo = method_exists($registro, 'motivoParaNaoExcluir')
            ? $registro->motivoParaNaoExcluir()
            : null;

        return $motivo ? back()->with('error', $motivo) : null;
    }

    /**
     * Assinar é ato pessoal: a senha é pedida de novo no ato. Devolve por que não assinou, ou null quando a senha
     * confere. Cinco erros seguidos travam a assinatura da conta por 15 minutos.
     *
     *   if ($recusa = $this->senhaDaAssinaturaRecusada($request)) return back()->with('error', $recusa);
     */
    protected function senhaDaAssinaturaRecusada(Request $request): ?string
    {
        $chave = 'assinatura:' . $request->user()->id;
        // O formulário recusado volta aberto, com o motivo (components/assinar).
        $request->session()->flash('assinatura_recusada', $request->url());

        if (RateLimiter::tooManyAttempts($chave, 5)) {
            return 'Muitas tentativas com a senha errada. Tente assinar de novo em '
                . max(1, (int) ceil(RateLimiter::availableIn($chave) / 60)) . ' minuto(s).';
        }

        if (!Hash::check((string) $request->input('senha_assinatura'), $request->user()->password)) {
            RateLimiter::hit($chave, 15 * 60);
            Log::warning('Senha recusada ao assinar', ['user_id' => $request->user()->id, 'ip' => $request->ip()]);

            return 'Senha incorreta: o documento não foi assinado.';
        }

        RateLimiter::clear($chave);
        $request->session()->forget('assinatura_recusada');

        return null;
    }
}

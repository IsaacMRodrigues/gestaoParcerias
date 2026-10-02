<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

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
}

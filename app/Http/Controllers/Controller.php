<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

abstract class Controller
{
    /**
     * Devolve um redirect com a explicação quando o registro tem vínculos que
     * impedem a exclusão; null quando dá para seguir com o delete().
     *
     * Uso:
     *   if ($bloqueio = $this->bloqueioDeExclusao($chamamento)) return $bloqueio;
     *   $chamamento->delete();
     *
     * Existe para a checagem ficar idêntica nos seis controllers que apagam
     * registros protegidos por FK. O banco já barra com RESTRICT — o que
     * faltava era perguntar antes, em vez de deixar estourar um 500 com SQL na
     * tela do usuário.
     */
    protected function bloqueioDeExclusao(Model $registro): ?RedirectResponse
    {
        $motivo = method_exists($registro, 'motivoParaNaoExcluir')
            ? $registro->motivoParaNaoExcluir()
            : null;

        return $motivo ? back()->with('error', $motivo) : null;
    }
}

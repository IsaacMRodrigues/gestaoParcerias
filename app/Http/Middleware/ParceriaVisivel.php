<?php

namespace App\Http\Middleware;

use App\Models\Aditivo;
use App\Models\Alteracao;
use App\Models\Chamamento;
use App\Models\Despesa;
use App\Models\Documento;
use App\Models\Instrumento;
use App\Models\OrdemPagamento;
use App\Models\Peca;
use App\Models\PrestacaoContas;
use App\Models\Proposta;
use App\Models\Recurso;
use App\Models\Repasse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Barra quem abre pelo endereço uma parceria que não vê: sobe de cada model da rota até a
 * proposta e pergunta a Proposta::visivelPara(). O servidor também não abre chamamento (Seleção,
 * peças e recursos) de outra Secretaria. Sem usuário, deixa passar (o auth barra).
 */
class ParceriaVisivel
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $request->route()) {
            foreach ($request->route()->parameters() as $valor) {
                $parceria = self::parceriaDe($valor);

                if ($parceria && !$parceria->visivelPara($user)) {
                    abort(403, $user->ehRepresentanteOsc()
                        ? 'Esta parceria é de outra organização.'
                        : 'Esta parceria pertence a outra Secretaria.');
                }

                // A página pública do chamamento é aberta às OSCs: o recorte vale para o servidor.
                $chamamento = self::chamamentoDe($valor);
                if ($chamamento && $user->temAcessoInterno() && !$chamamento->visivelPara($user)) {
                    abort(403, 'Este chamamento pertence a outra Secretaria.');
                }
            }
        }

        return $next($request);
    }

    /** O chamamento a que o registro pertence diretamente (a Seleção e o que pende dela). */
    private static function chamamentoDe(mixed $valor): ?Chamamento
    {
        return match (true) {
            $valor instanceof Chamamento => $valor,
            $valor instanceof Peca       => $valor->pecaable instanceof Chamamento ? $valor->pecaable : null,
            $valor instanceof Recurso    => $valor->chamamento,
            default                      => null,
        };
    }

    /** A parceria a que o registro pertence — null quando não pertence a nenhuma. */
    public static function parceriaDe(mixed $valor): ?Proposta
    {
        return match (true) {
            $valor instanceof Proposta    => $valor,
            $valor instanceof Instrumento => $valor->proposta,
            // Documento de manifestação de interesse não tem proposta: fica com
            // a checagem do ManifestacaoController, como antes.
            $valor instanceof Documento   => $valor->proposta,
            $valor instanceof Aditivo,
            $valor instanceof OrdemPagamento,
            $valor instanceof Despesa,
            $valor instanceof Repasse,
            $valor instanceof PrestacaoContas,
            $valor instanceof Alteracao   => $valor->instrumento?->proposta,
            // Peça da Seleção pertence ao chamamento, não a uma parceria: segue
            // com Peca::podeVer(). As da Celebração, alteração e prestação sobem.
            $valor instanceof Peca        => self::parceriaDe($valor->pecaable),
            default                       => null,
        };
    }
}

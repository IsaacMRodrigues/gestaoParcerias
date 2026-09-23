<?php

namespace App\Http\Middleware;

use App\Models\Aditivo;
use App\Models\Alteracao;
use App\Models\Despesa;
use App\Models\Diligencia;
use App\Models\Documento;
use App\Models\Instrumento;
use App\Models\OrdemPagamento;
use App\Models\Peca;
use App\Models\PrestacaoContas;
use App\Models\Proposta;
use App\Models\Repasse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Barra quem abre, pelo endereço, uma parceria que não é sua.
 *
 * O recorte por Secretaria vivia só nas listagens (Proposta::visiveisPara):
 * a lista escondia, mas /propostas/19, /instrumentos/4, /prestacao-contas/1
 * e /celebracao/19 abriam para servidor de outra Secretaria — e a Celebração,
 * que não conferia nada, abria até para outra OSC. Cada controller fazia a
 * sua checagem, ou não fazia.
 *
 * Aqui a porta é única. Registrado no grupo `web`, olha os registros que a
 * rota recebe, sobe de cada um até a parceria (proposta) e pergunta a
 * Proposta::visivelPara(). Rota nova que receba instrumento, aditivo, ordem
 * de pagamento etc. já nasce protegida. Rota sem nada disso passa direto.
 *
 * Roda depois do SubstituteBindings (que também é do grupo `web`), então os
 * parâmetros já chegam como models. Sem usuário, deixa passar: quem barra o
 * visitante é o `auth` da própria rota.
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
            }
        }

        return $next($request);
    }

    /** A parceria a que o registro pertence — null quando não pertence a nenhuma. */
    public static function parceriaDe(mixed $valor): ?Proposta
    {
        return match (true) {
            $valor instanceof Proposta    => $valor,
            $valor instanceof Instrumento => $valor->proposta,
            $valor instanceof Diligencia  => $valor->proposta,
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

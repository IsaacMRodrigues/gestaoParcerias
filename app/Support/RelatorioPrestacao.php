<?php

namespace App\Support;

use App\Models\Despesa;
use App\Models\Instrumento;
use App\Models\Lancamento;
use App\Models\PrestacaoContas;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Relatórios da prestação de contas: a execução financeira de cada parceria e a situação de cada
 * prestação. Saem num formato só — tabelas com colunas tipadas, linhas e totais — que a tela, a
 * planilha e o PDF desenham. Cada um vê o que é seu: a Secretaria, o município inteiro ou a própria OSC.
 */
class RelatorioPrestacao
{
    public const RELATORIOS = [
        'execucao'   => 'Execução financeira',
        'prestacoes' => 'Prestações de contas',
    ];

    public const SITUACOES = [
        'andamento' => 'Em andamento',
        'com_osc'   => 'Com a OSC',
        'analise'   => 'Em análise na Prefeitura',
        'encerrada' => 'Encerradas',
    ];

    /**
     * @param array{relatorio?: string, orgao_id?: ?int, osc_id?: ?int, instrumento_id?: ?int, de?: ?string, ate?: ?string, situacao?: ?string} $filtros
     * @return array{titulo: string, filtros: list<string>, tabelas: list<array{titulo: ?string, colunas: array, linhas: array, totais: ?array}>}
     */
    public static function gerar(User $user, array $filtros): array
    {
        $relatorio = array_key_exists($filtros['relatorio'] ?? '', self::RELATORIOS) ? $filtros['relatorio'] : 'execucao';

        return [
            'titulo'  => self::RELATORIOS[$relatorio],
            'filtros' => self::descreverFiltros($filtros),
            'tabelas' => $relatorio === 'execucao' ? self::execucao($user, $filtros) : self::prestacoes($user, $filtros),
        ];
    }

    /** As parcerias que a pessoa pode ver, já com os filtros de Secretaria, OSC e parceria. */
    public static function parcerias(User $user, array $filtros = []): Builder
    {
        $oscPropria = $user->ehRepresentanteOsc() ? $user->osc?->id : null;

        return Instrumento::query()
            ->whereIn('status', ['assinado', 'vigente', 'encerrado'])
            ->when($oscPropria !== null, fn ($q) => $q->whereHas('proposta', fn ($p) => $p->where('osc_id', $oscPropria)))
            ->when($oscPropria === null, fn ($q) => $q->visiveisPara($user))
            ->when($filtros['orgao_id'] ?? null, fn ($q, $id) => $q->whereHas('proposta.chamamento.programa', fn ($p) => $p->where('orgao_id', $id)))
            ->when($filtros['osc_id'] ?? null, fn ($q, $id) => $q->whereHas('proposta', fn ($p) => $p->where('osc_id', $id)))
            ->when($filtros['instrumento_id'] ?? null, fn ($q, $id) => $q->whereKey($id))
            ->orderBy('numero');
    }

    // ------------------------------------------------------- execução financeira

    private static function execucao(User $user, array $filtros): array
    {
        [$de, $ate] = self::periodo($filtros);
        $noPeriodo = fn (string $coluna) => fn ($q) => $q
            ->when($de, fn ($q) => $q->whereDate($coluna, '>=', $de))
            ->when($ate, fn ($q) => $q->whereDate($coluna, '<=', $ate));

        $parcerias = self::parcerias($user, $filtros)
            ->with(['proposta.osc', 'proposta.chamamento.programa.orgao', 'proposta.planoItens',
                'repasses' => $noPeriodo('data_repasse'), 'despesas' => $noPeriodo('data_despesa')])
            ->get();

        $linhas = $parcerias->map(function (Instrumento $i) {
            $repassado = (float) $i->repasses->sum('valor');
            $gasto = (float) $i->despesas->sum('valor');
            $porBloco = self::porBloco($i->despesas->groupBy('natureza')->map->sum('valor')->all());

            return [
                'parceria'     => $i->numero,
                'osc'          => $i->proposta?->osc?->name,
                'secretaria'   => $i->proposta?->chamamento?->programa?->orgao?->name,
                'valor_termo'  => (float) $i->valor_repasse,
                'repassado'    => $repassado,
                'gasto'        => $gasto,
                'saldo'        => $repassado - $gasto,
                'executado'    => $repassado > 0 ? $gasto / $repassado * 100 : null,
                'pessoal'      => $porBloco['pessoal'],
                'encargos'     => $porBloco['encargos'],
                'manutencao'   => $porBloco['manutencao'],
                'equipamentos' => $porBloco['equipamentos'],
                'a_conferir'   => (float) $i->despesas->where('situacao', 'pendente')->sum('valor'),
                'devolvidas'   => $i->despesas->where('situacao', 'devolvido')->count(),
            ];
        })->values()->all();

        $somar = fn (string $c) => array_sum(array_column($linhas, $c));
        $tabelas = [[
            'titulo'  => null,
            'colunas' => [
                'parceria' => ['Parceria', 'texto'], 'osc' => ['OSC', 'texto'], 'secretaria' => ['Secretaria', 'texto'],
                'valor_termo' => ['Valor do termo', 'dinheiro'], 'repassado' => ['Repassado', 'dinheiro'], 'gasto' => ['Despesas', 'dinheiro'],
                'saldo' => ['Saldo', 'dinheiro'], 'executado' => ['Executado', 'pct'],
                'pessoal' => ['Pessoal', 'dinheiro'], 'encargos' => ['Encargos', 'dinheiro'],
                'manutencao' => ['Manutenção', 'dinheiro'], 'equipamentos' => ['Equipamentos', 'dinheiro'],
                'a_conferir' => ['Despesas a conferir', 'dinheiro'], 'devolvidas' => ['Devolvidas', 'numero'],
            ],
            'linhas'  => $linhas,
            'totais'  => $linhas ? [
                'parceria' => count($linhas) . ' parceria(s)', 'valor_termo' => $somar('valor_termo'), 'repassado' => $somar('repassado'),
                'gasto' => $somar('gasto'), 'saldo' => $somar('saldo'),
                'executado' => $somar('repassado') > 0 ? $somar('gasto') / $somar('repassado') * 100 : null,
                'pessoal' => $somar('pessoal'), 'encargos' => $somar('encargos'), 'manutencao' => $somar('manutencao'),
                'equipamentos' => $somar('equipamentos'), 'a_conferir' => $somar('a_conferir'), 'devolvidas' => $somar('devolvidas'),
            ] : null,
        ]];

        // Uma parceria só: o plano de aplicação aprovado contra o executado, natureza por natureza.
        if ($parcerias->count() === 1) {
            $tabelas[] = self::aprovadoExecutado($parcerias->first());
        }

        return $tabelas;
    }

    private static function aprovadoExecutado(Instrumento $i): array
    {
        $aprovado = $i->proposta?->planoPorNatureza() ?? [];
        $executado = $i->despesas->groupBy('natureza')->map->sum('valor')->all();

        $linhas = collect(array_keys($aprovado + $executado))
            ->map(fn ($nat) => [
                'natureza'  => Despesa::rotuloNatureza($nat),
                'aprovado'  => (float) ($aprovado[$nat] ?? 0),
                'executado' => (float) ($executado[$nat] ?? 0),
                'saldo'     => (float) ($aprovado[$nat] ?? 0) - (float) ($executado[$nat] ?? 0),
                'executado_pct' => ($aprovado[$nat] ?? 0) > 0 ? ($executado[$nat] ?? 0) / $aprovado[$nat] * 100 : null,
            ])->sortBy('natureza')->values()->all();

        $a = array_sum(array_column($linhas, 'aprovado'));
        $e = array_sum(array_column($linhas, 'executado'));

        return [
            'titulo'  => 'Plano de aplicação aprovado × executado — parceria ' . $i->numero,
            'colunas' => ['natureza' => ['Natureza de despesa', 'texto'], 'aprovado' => ['Aprovado no plano', 'dinheiro'],
                'executado' => ['Executado', 'dinheiro'], 'saldo' => ['Saldo', 'dinheiro'], 'executado_pct' => ['Executado', 'pct']],
            'linhas'  => $linhas,
            'totais'  => $linhas ? ['natureza' => 'Total', 'aprovado' => $a, 'executado' => $e, 'saldo' => $a - $e,
                'executado_pct' => $a > 0 ? $e / $a * 100 : null] : null,
        ];
    }

    /** Valores por natureza somados nos quatro blocos do Anexo VI. */
    private static function porBloco(array $porNatureza): array
    {
        $blocos = array_fill_keys(array_keys(PrestacaoContas::BLOCOS), 0.0);
        foreach ($porNatureza as $natureza => $valor) {
            $bloco = collect(PrestacaoContas::BLOCOS)->search(fn ($b) => in_array($natureza, $b['naturezas'], true)) ?: 'manutencao';
            $blocos[$bloco] += (float) $valor;
        }

        return $blocos;
    }

    // ---------------------------------------------------- prestações de contas

    private static function prestacoes(User $user, array $filtros): array
    {
        [$de, $ate] = self::periodo($filtros);

        $prestacoes = PrestacaoContas::with(['instrumento.proposta.osc', 'instrumento.proposta.chamamento.programa.orgao', 'tramitacoes'])
            ->whereIn('instrumento_id', self::parcerias($user, $filtros)->reorder()->select('id'))
            ->when($de, fn ($q) => $q->whereDate('periodo_fim', '>=', $de))
            ->when($ate, fn ($q) => $q->whereDate('periodo_inicio', '<=', $ate))
            ->when($filtros['situacao'] ?? null, fn ($q, $s) => match ($s) {
                'andamento' => $q->whereNull('concluida_em'),
                'com_osc'   => $q->whereNull('concluida_em')->where('setor', 'osc'),
                'analise'   => $q->whereNull('concluida_em')->where('setor', '!=', 'osc'),
                'encerrada' => $q->whereNotNull('concluida_em'),
                default     => $q,
            })
            ->orderByDesc('periodo_fim')
            ->get();

        // Situação dos lançamentos do período de cada prestação, numa consulta por tabela.
        $lancamentos = Despesa::whereIn('instrumento_id', $prestacoes->pluck('instrumento_id'))->get(['instrumento_id', 'data_despesa', 'valor', 'situacao'])
            ->map(fn ($d) => ['i' => $d->instrumento_id, 'data' => $d->data_despesa->toDateString(), 'situacao' => $d->situacao])
            ->concat(Lancamento::whereIn('instrumento_id', $prestacoes->pluck('instrumento_id'))->get(['instrumento_id', 'competencia', 'situacao'])
                ->map(fn ($l) => ['i' => $l->instrumento_id, 'data' => $l->competencia->toDateString(), 'situacao' => $l->situacao]));

        $linhas = $prestacoes->map(function (PrestacaoContas $pc) use ($lancamentos) {
            $doPeriodo = $lancamentos->filter(fn ($l) => (int) $l['i'] === (int) $pc->instrumento_id
                && $l['data'] >= $pc->periodo_inicio->toDateString() && $l['data'] <= $pc->periodo_fim->toDateString());
            $ultimo = $pc->tramitacoes->last();

            return [
                'parceria'   => $pc->instrumento?->numero,
                'osc'        => $pc->instrumento?->proposta?->osc?->name,
                'secretaria' => $pc->instrumento?->proposta?->chamamento?->programa?->orgao?->name,
                'prestacao'  => $pc->tipo === 'final' ? 'Final' : 'Parcial nº ' . ($pc->numero ?: 1),
                'periodo'    => $pc->periodo_inicio->format('d/m/Y') . ' a ' . $pc->periodo_fim->format('d/m/Y'),
                'situacao'   => $pc->concluida()
                    ? 'Encerrada em ' . $pc->concluida_em->format('d/m/Y')
                    : 'Etapa ' . ($pc->etapa + 1) . ' — ' . (PrestacaoContas::SETORES[$pc->setor] ?? $pc->setor),
                'desde'      => ($pc->concluida() ? null : ($ultimo?->enviado_em ?? $pc->iniciada_em))?->format('d/m/Y'),
                'repassado'  => $pc->totalRepassado(),
                'gasto'      => $pc->totalGasto(),
                'saldo'      => $pc->totalRepassado() - $pc->totalGasto(),
                'a_conferir' => $doPeriodo->where('situacao', 'pendente')->count(),
                'devolvidos' => $doPeriodo->where('situacao', 'devolvido')->count(),
            ];
        })->values()->all();

        $somar = fn (string $c) => array_sum(array_column($linhas, $c));

        return [[
            'titulo'  => null,
            'colunas' => [
                'parceria' => ['Parceria', 'texto'], 'osc' => ['OSC', 'texto'], 'secretaria' => ['Secretaria', 'texto'],
                'prestacao' => ['Prestação', 'texto'], 'periodo' => ['Período', 'texto'], 'situacao' => ['Situação', 'texto'],
                'desde' => ['Desde', 'texto'], 'repassado' => ['Repassado no período', 'dinheiro'],
                'gasto' => ['Despesas no período', 'dinheiro'], 'saldo' => ['Saldo do período', 'dinheiro'],
                'a_conferir' => ['Lançamentos a conferir', 'numero'], 'devolvidos' => ['Devolvidos', 'numero'],
            ],
            'linhas'  => $linhas,
            'totais'  => $linhas ? [
                'parceria' => count($linhas) . ' prestação(ões)', 'repassado' => $somar('repassado'), 'gasto' => $somar('gasto'),
                'saldo' => $somar('saldo'), 'a_conferir' => $somar('a_conferir'), 'devolvidos' => $somar('devolvidos'),
            ] : null,
        ]];
    }

    // ------------------------------------------------------------------ apoio

    /** @return array{0: ?string, 1: ?string} */
    private static function periodo(array $filtros): array
    {
        $data = fn ($v) => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;

        return [$data($filtros['de'] ?? null), $data($filtros['ate'] ?? null)];
    }

    /** Os filtros em frases, para o cabeçalho do PDF e da planilha. */
    private static function descreverFiltros(array $filtros): array
    {
        [$de, $ate] = self::periodo($filtros);
        $fmt = fn ($d) => Carbon::parse($d)->format('d/m/Y');

        return array_values(array_filter([
            ($filtros['orgao_id'] ?? null) ? 'Secretaria: ' . \App\Models\Orgao::find($filtros['orgao_id'])?->name : null,
            ($filtros['osc_id'] ?? null) ? 'OSC: ' . \App\Models\Osc::find($filtros['osc_id'])?->name : null,
            ($filtros['instrumento_id'] ?? null) ? 'Parceria: ' . Instrumento::find($filtros['instrumento_id'])?->numero : null,
            $de || $ate ? 'Período: ' . ($de ? $fmt($de) : 'início') . ' a ' . ($ate ? $fmt($ate) : 'hoje') : 'Período: toda a parceria',
            ($filtros['situacao'] ?? null) && ($filtros['relatorio'] ?? '') === 'prestacoes' ? 'Situação: ' . (self::SITUACOES[$filtros['situacao']] ?? '') : null,
        ]));
    }

    /** Valor de uma célula como texto, no formato brasileiro. */
    public static function formatar(mixed $valor, string $tipo): string
    {
        if ($valor === null || $valor === '') {
            return '—';
        }

        return match ($tipo) {
            'dinheiro' => 'R$ ' . number_format((float) $valor, 2, ',', '.'),
            'pct'      => number_format((float) $valor, 1, ',', '.') . '%',
            'numero'   => number_format((float) $valor, 0, ',', '.'),
            default    => (string) $valor,
        };
    }

    /** CSV para o Excel brasileiro: UTF-8 com BOM, ponto e vírgula, números com vírgula decimal. */
    public static function csv(array $relatorio): string
    {
        $saida = fopen('php://temp', 'r+');
        fwrite($saida, "\xEF\xBB\xBF");
        fputcsv($saida, [$relatorio['titulo']], ';', '"', '');
        foreach ($relatorio['filtros'] as $f) {
            fputcsv($saida, [$f], ';', '"', '');
        }
        fputcsv($saida, ['Gerado em ' . now()->format('d/m/Y H:i')], ';', '"', '');

        $numero = fn ($v, $tipo) => $v === null || $v === '' ? ''
            : ($tipo === 'texto' ? (string) $v : number_format((float) $v, $tipo === 'numero' ? 0 : 2, ',', ''));

        foreach ($relatorio['tabelas'] as $t) {
            fputcsv($saida, [], ';', '"', '');
            if ($t['titulo']) {
                fputcsv($saida, [$t['titulo']], ';', '"', '');
            }
            fputcsv($saida, array_map(fn ($c) => $c[0] . ($c[1] === 'dinheiro' ? ' (R$)' : ($c[1] === 'pct' ? ' (%)' : '')), $t['colunas']), ';', '"', '');
            foreach (array_merge($t['linhas'], $t['totais'] ? [$t['totais']] : []) as $linha) {
                fputcsv($saida, array_map(fn ($chave, $c) => $numero($linha[$chave] ?? null, $c[1]), array_keys($t['colunas']), $t['colunas']), ';', '"', '');
            }
        }

        rewind($saida);
        $csv = stream_get_contents($saida);
        fclose($saida);

        return $csv;
    }

    public static function pdf(array $relatorio): string
    {
        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true, 'defaultFont' => 'Helvetica']);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->loadHtml(view('relatorios.pdf', ['r' => $relatorio])->render(), 'UTF-8');
        $dompdf->render();

        return $dompdf->output();
    }
}

<?php

namespace App\Support;

use App\Models\Despesa;
use App\Models\Meta;
use App\Models\Osc;
use App\Models\PlanoDesembolso;
use App\Models\PlanoItem;
use App\Models\Proposta;

/**
 * O Plano de Trabalho como documento: o que a OSC lançou no Portal, impresso para assinar, na
 * estrutura do modelo da cliente (os 13 itens, o pedido de avaliação e a planilha anexa).
 */
class PlanoDocumento
{
    private const TABELA = '<table style="width:100%;border-collapse:collapse" border="1" cellpadding="6">';

    public static function render(Proposta $p): string
    {
        $p->loadMissing(['metas.etapas', 'planoItens', 'desembolsos', 'contrapartidas', 'equipe', 'osc',
            'chamamento.programa.orgao']);

        return '<p style="text-align:center"><strong>PLANO DE TRABALHO</strong></p>'
            . self::identificacao($p)
            . self::identificacaoDoProjeto($p)
            . self::pedidoDeAvaliacao($p)
            . self::texto('3 - Descrição da realidade (por que o projeto deve ser implementado?).', $p->descricao_realidade)
            . self::objetivos($p)
            . self::texto('5 - Metodologia (Como o projeto vai alcançar seus objetivos? Nesse sentido, deve descrever as estratégias e técnicas que serão empregadas)', $p->metodologia)
            . self::texto('6 – Diagnóstico/Justificativa (Por que se propõe o projeto diante do diagnóstico da realidade, e sua importância para os beneficiários do projeto, devendo ser demonstrado o nexo entre essa realidade e a atividade e metas a serem atingidas).', $p->justificativa)
            . self::metas($p)
            . self::contrapartidas($p)
            . self::naturezas($p)
            . self::cronogramaFisicoFinanceiro($p)
            . self::desembolso($p)
            . self::equipe($p)
            . self::planoDeAplicacao($p);
    }

    // ------------------------------------------------------------------ itens

    private static function identificacao(Proposta $p): string
    {
        /** @var Osc|null $osc */
        $osc = $p->osc;
        $endereco = fn (?string $logradouro, ?string $numero, ?string $complemento, ?string $bairro) => collect([
            trim(($logradouro ?? '') . ($numero ? ', ' . $numero : '')), $complemento, $bairro,
        ])->filter()->implode(' — ');
        $cidade = fn (?string $cidade, ?string $uf) => trim(($cidade ?? '') . ($uf ? '/' . $uf : ''));

        $linhas = [
            'Razão social'      => $osc?->name,
            'CNPJ'              => $osc?->cnpj,
            'CEP'               => $osc?->cep,
            'Endereço'          => $osc ? $endereco($osc->logradouro, $osc->numero, $osc->complemento, $osc->bairro) : null,
            'Cidade'            => $osc ? $cidade($osc->cidade, $osc->estado) : null,
            'DDD Telefone'      => $osc?->phone,
            'E-mail'            => $osc?->email,
            'Responsável legal' => $osc?->resp_nome,
            'CPF'               => $osc?->resp_cpf,
            'RG'                => trim(($osc?->resp_rg ?? '') . ($osc?->resp_rg_orgao ? ' ' . $osc->resp_rg_orgao : '')),
            'Endereço '         => $osc ? $endereco($osc->resp_logradouro, $osc->resp_numero, $osc->resp_complemento, $osc->resp_bairro) : null,
            'Cidade '           => $osc ? $cidade($osc->resp_cidade, $osc->resp_estado) : null,
            'DDD Telefone '     => $osc?->resp_phone,
            'CEP '              => $osc?->resp_cep,
            'E-mail '           => $osc?->resp_email,
        ];

        return '<p><strong>1 - Identificação Órgão/Entidade Proponente (enviar comprovantes anexo)</strong></p>'
            . self::quadro($linhas);
    }

    private static function identificacaoDoProjeto(Proposta $p): string
    {
        $duracao = collect([
            $p->vigencia_dias ? $p->vigencia_dias . ' dias corridos' : null,
            ($p->data_inicio_prevista || $p->data_fim_prevista)
                ? ($p->data_inicio_prevista?->format('d/m/Y') ?? '—') . ' a ' . ($p->data_fim_prevista?->format('d/m/Y') ?? '—')
                : null,
        ])->filter()->implode(' — ');

        return '<p><strong>2 - Identificação do projeto</strong></p>'
            . self::quadro([
                'Nome do projeto'    => $p->titulo,
                'Objeto de execução' => $p->objeto,
                'Público Alvo'       => $p->publico_alvo,
                'Duração execução'   => $duracao,
                'Valor pleiteado'    => self::moeda($p->valor_solicitado),
            ]);
    }

    private static function pedidoDeAvaliacao(Proposta $p): string
    {
        return '<p style="text-align:center"><strong>PEDIDO DE AVALIAÇÃO</strong></p>'
            . '<p>Solicitamos que o presente Plano de Trabalho seja analisado e aprovado, nos termos Lei Federal 13.019/2014.</p>'
            . self::cidadeDataEPresidente($p);
    }

    private static function objetivos(Proposta $p): string
    {
        return '<p><strong>4 - Objetivos (Apresentar de forma clara e objetiva o que se pretende alcançar).</strong></p>'
            . '<p><strong>Geral</strong></p><p>' . self::paragrafo($p->objetivos) . '</p>'
            . '<p><strong>Específicos</strong></p><p>' . self::paragrafo($p->objetivos_especificos) . '</p>';
    }

    private static function metas(Proposta $p): string
    {
        $html = '<p><strong>7 – Metas, indicadores e resultados (preencher conforme orientação abaixo)</strong></p>'
            . self::TABELA . '<thead><tr>'
            . '<th>Objetivos específicos<br>(conforme já descrito no item 4)</th>'
            . '<th>Metas</th><th>Atividades</th><th>Indicadores<br>Qualitativas - Quantitativas</th>'
            . '<th>Resultados esperados</th><th>Meios de verificação</th>'
            . '</tr></thead><tbody>';

        foreach ($p->metas as $meta) {
            /** @var Meta $meta */
            $atividades = $meta->etapas->isNotEmpty()
                ? $meta->etapas->map(fn ($a) => e($a->descricao))->implode('<br>')
                : e($meta->atividades ?: '—');
            $indicadores = collect([
                $meta->indicador ? 'Qualitativos: ' . e($meta->indicador) : null,
                $meta->meta_quantitativa ? 'Quantitativos: ' . e($meta->meta_quantitativa) : null,
            ])->filter()->implode('<br>') ?: '—';

            $html .= '<tr>'
                . '<td>' . e($meta->objetivo_especifico ?: '—') . '</td>'
                . '<td>' . $meta->numero . '. ' . e($meta->descricao) . '</td>'
                . '<td>' . $atividades . '</td>'
                . '<td>' . $indicadores . '</td>'
                . '<td>' . e($meta->resultados_esperados ?: '—') . '</td>'
                . '<td>' . e($meta->meios_verificacao ?: '—') . '</td>'
                . '</tr>';
        }

        if ($p->metas->isEmpty()) {
            $html .= '<tr><td colspan="6">—</td></tr>';
        }

        return $html . '</tbody></table>';
    }

    private static function contrapartidas(Proposta $p): string
    {
        $html = '<p><strong>8 – Descrição da contrapartida não financeira, quando houver</strong></p>'
            . self::TABELA . '<thead><tr><th>Contrapartida Nº</th><th>Descrição</th><th>Quantidade</th></tr></thead><tbody>';

        foreach ($p->contrapartidas as $cp) {
            $html .= '<tr><td>' . $cp->numero . '</td><td>' . e($cp->descricao) . '</td><td>' . e($cp->quantidade ?: '—') . '</td></tr>';
        }

        if ($p->contrapartidas->isEmpty()) {
            $html .= '<tr><td colspan="3">—</td></tr>';
        }

        return $html . '</tbody></table>';
    }

    private static function naturezas(Proposta $p): string
    {
        $valores = $p->naturezasDaDespesa();

        $html = '<p><strong>9 – Descrição da natureza da despesa (Campo reservado ao ordenador de despesa - PMSGRA)</strong></p>'
            . self::TABELA . '<thead><tr><th>Natureza</th><th>Valor</th></tr></thead><tbody>';

        foreach (Despesa::NATUREZAS as $chave => $rotulo) {
            $html .= '<tr><td>' . e($rotulo) . '</td><td style="text-align:right">'
                . ($valores[$chave] > 0 ? self::moeda($valores[$chave]) : '') . '</td></tr>';
        }

        return $html . '<tr><td><strong>TOTAL</strong></td><td style="text-align:right"><strong>'
            . self::moeda(array_sum($valores)) . '</strong></td></tr></tbody></table>';
    }

    private static function cronogramaFisicoFinanceiro(Proposta $p): string
    {
        $html = '<p><strong>10 – Cronograma de execução física e financeira</strong></p>'
            . self::TABELA . '<thead>'
            . '<tr><th colspan="3">ATIVIDADE(S)</th><th colspan="2">Período de execução</th></tr>'
            . '<tr><th>Meta nº</th><th>Atividades</th><th>Estimado (R$)</th><th>Início</th><th>Fim</th></tr>'
            . '</thead><tbody>';

        foreach ($p->metas as $meta) {
            if ($meta->etapas->isEmpty()) {
                // Meta antiga, lançada antes de haver atividade com período e valor.
                $html .= self::linhaDeAtividade($meta->numero, $meta->atividades ?: $meta->descricao,
                    (float) $meta->valor > 0 ? (float) $meta->valor : null, $meta->data_inicio, $meta->data_fim);

                continue;
            }

            foreach ($meta->etapas as $atividade) {
                $html .= self::linhaDeAtividade($meta->numero, $atividade->descricao,
                    $atividade->valor !== null ? (float) $atividade->valor : null, $atividade->data_inicio, $atividade->data_fim);
            }
        }

        if ($p->metas->isEmpty()) {
            $html .= '<tr><td colspan="5">—</td></tr>';
        }

        return $html . '<tr><td colspan="2"><strong>TOTAL</strong></td><td style="text-align:right"><strong>'
            . self::moeda($p->totalDasMetas()) . '</strong></td><td colspan="2"></td></tr></tbody></table>'
            . '<p>(*) As metas/ações aqui descritas deverão estar relacionadas ao Plano de Aplicação dos Recursos</p>';
    }

    private static function linhaDeAtividade(int $meta, string $atividade, ?float $valor, $inicio, $fim): string
    {
        return '<tr><td>' . $meta . '</td><td>' . e($atividade) . '</td>'
            . '<td style="text-align:right">' . ($valor !== null ? self::moeda($valor) : '—') . '</td>'
            . '<td>' . ($inicio?->format('d/m/Y') ?? '—') . '</td><td>' . ($fim?->format('d/m/Y') ?? '—') . '</td></tr>';
    }

    private static function desembolso(Proposta $p): string
    {
        $parcelas = $p->desembolsos;
        $colunas  = max(12, (int) $parcelas->max('parcela'));

        $html = '<p><strong>11 – Cronograma de desembolso</strong></p>'
            . self::TABELA . '<thead><tr><th>Meta nº</th>';
        for ($n = 1; $n <= $colunas; $n++) {
            $html .= '<th>' . PlanoDesembolso::rotuloParcela($n) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        $linhas = $p->metas->map(fn (Meta $m) => [(string) $m->numero, $parcelas->where('meta_id', $m->id)]);
        if (($semMeta = $parcelas->whereNull('meta_id'))->isNotEmpty()) {
            $linhas->push(['—', $semMeta]);
        }

        foreach ($linhas as [$rotulo, $daMeta]) {
            $html .= '<tr><td>' . e($rotulo) . '</td>';
            for ($n = 1; $n <= $colunas; $n++) {
                $valor = (float) $daMeta->where('parcela', $n)->sum('valor');
                $html .= '<td style="text-align:right">' . ($valor > 0 ? self::numero($valor) : '') . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '<tr><td><strong>Total</strong></td>';
        for ($n = 1; $n <= $colunas; $n++) {
            $valor = (float) $parcelas->where('parcela', $n)->sum('valor');
            $html .= '<td style="text-align:right"><strong>' . ($valor > 0 ? self::numero($valor) : '') . '</strong></td>';
        }

        return $html . '</tr></tbody></table>';
    }

    private static function equipe(Proposta $p): string
    {
        $html = '<p><strong>12 - Relação da equipe contratada ou da equipe própria da OSC a serviço da parceria:</strong></p>'
            . self::TABELA . '<thead><tr><th>Cargo/função</th><th>Formação profissional</th><th>Carga horária mensal</th>'
            . '<th>Natureza do vínculo (CLT, contratado, voluntariado)</th></tr></thead><tbody>';

        foreach ($p->equipe as $membro) {
            $html .= '<tr><td>' . e($membro->cargo_funcao) . '</td><td>' . e($membro->formacao ?: '—') . '</td>'
                . '<td>' . e($membro->carga_horaria_mensal ?: '—') . '</td><td>' . e($membro->vinculoLabel()) . '</td></tr>';
        }

        if ($p->equipe->isEmpty()) {
            $html .= '<tr><td colspan="4">—</td></tr>';
        }

        return $html . '</tbody></table>';
    }

    /** Item 13: título, campo do ordenador de despesa, ressalva e assinatura, e depois a planilha anexa. */
    private static function planoDeAplicacao(Proposta $p): string
    {
        $html = '<p><strong>13 – Plano de aplicação dos recursos (Planilha anexa)</strong></p>'
            . (filled($p->plano_aplicacao) ? '<p>' . self::paragrafo($p->plano_aplicacao) . '</p>' : '')
            . '<p><strong>Campo reservado ao ordenador de despesa (PMSGRA)</strong></p>'
            . self::quadro([
                'Secretaria municipal' => $p->chamamento?->programa?->orgao?->name,
                'Analisado em'         => null,
            ])
            . '<p>A Secretaria Gestora poderá exigir documentos complementares pertinentes ao objeto pleiteado</p>'
            . self::cidadeDataEPresidente($p);

        $html .= '<p><br></p><p style="text-align:center"><strong>ANEXO — PLANILHA DO PLANO DE APLICAÇÃO DOS RECURSOS</strong></p>'
            . self::TABELA . '<thead><tr>'
            . '<th>Item</th><th>Descrição</th><th>Natureza da despesa</th><th>Unid.</th>'
            . '<th>Qtd.</th><th>Valor unitário</th><th>Valor total</th><th>Atividades vinculadas</th>'
            . '</tr></thead><tbody>';

        foreach ($p->planoItens as $item) {
            /** @var PlanoItem $item */
            $html .= '<tr>'
                . '<td>' . $item->numero . '</td>'
                . '<td>' . e($item->descricao) . '</td>'
                . '<td>' . e($item->tipoLabel()) . '</td>'
                . '<td>' . e($item->unidade ?: '—') . '</td>'
                . '<td style="text-align:right">' . rtrim(rtrim(number_format((float) $item->quantidade, 2, ',', '.'), '0'), ',') . '</td>'
                . '<td style="text-align:right">' . self::moeda($item->valor_unitario) . '</td>'
                . '<td style="text-align:right">' . self::moeda($item->total()) . '</td>'
                . '<td>' . e($item->atividades_vinculadas ?: '—') . '</td>'
                . '</tr>';
        }

        if ($p->planoItens->isEmpty()) {
            $html .= '<tr><td colspan="8">Sem itens lançados.</td></tr>';
        } else {
            $html .= '<tr><td colspan="6" style="text-align:right"><strong>Total</strong></td>'
                . '<td style="text-align:right"><strong>' . self::moeda($p->totalPlanoAplicacao()) . '</strong></td><td></td></tr>';
        }

        return $html . '</tbody></table>';
    }

    // ------------------------------------------------------------------ apoio

    /** "Cidade/data" e "Presidente (nome e assinatura)", como o modelo os traz duas vezes. */
    private static function cidadeDataEPresidente(Proposta $p): string
    {
        return '<p>São Gonçalo do Rio Abaixo, ' . now()->locale('pt_BR')->translatedFormat('j \d\e F \d\e Y') . '.</p>'
            . '<p style="text-align:center"><br>' . e($p->osc?->resp_nome ?: '—')
            . '<br>Presidente (nome e assinatura)</p>';
    }

    private static function texto(string $titulo, ?string $conteudo): string
    {
        return '<p><strong>' . e($titulo) . '</strong></p><p>' . self::paragrafo($conteudo) . '</p>';
    }

    private static function paragrafo(?string $conteudo): string
    {
        return filled($conteudo) ? nl2br(e($conteudo)) : '—';
    }

    /** Tabela de rótulo e valor, como os quadros de identificação do modelo. */
    private static function quadro(array $linhas): string
    {
        $html = self::TABELA . '<tbody>';
        foreach ($linhas as $rotulo => $valor) {
            $html .= '<tr><td style="width:30%"><strong>' . e(trim($rotulo)) . ':</strong></td><td>' . e(filled($valor) ? $valor : '—') . '</td></tr>';
        }

        return $html . '</tbody></table>';
    }

    private static function moeda($v): string
    {
        return 'R$ ' . self::numero((float) $v);
    }

    private static function numero(float $v): string
    {
        return number_format($v, 2, ',', '.');
    }
}

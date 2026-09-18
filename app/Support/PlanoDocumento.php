<?php

namespace App\Support;

use App\Models\PlanoDesembolso;
use App\Models\PlanoEndereco;
use App\Models\PlanoItem;
use App\Models\Proposta;

/**
 * O Plano de Trabalho como documento.
 *
 * O item 1 do checklist da habilitação diz "a partir do preenchido": o plano
 * não é um arquivo que a OSC redige à parte e sobe, é o que ela lançou no
 * Portal, impresso para assinar. Assim o documento assinado e os dados que o
 * sistema usa nas análises são a mesma coisa — não há como divergirem.
 */
class PlanoDocumento
{
    public static function render(Proposta $p): string
    {
        $p->loadMissing(['metas.etapas', 'planoItens', 'desembolsos', 'enderecosExecucao', 'osc',
            'chamamento.programa.orgao']);

        $html = '<p style="text-align:center"><strong>PLANO DE TRABALHO</strong></p>';
        $html .= self::identificacao($p);
        $html .= self::descritivo($p);
        $html .= self::enderecos($p);
        $html .= self::cronogramaExecucao($p);
        $html .= self::planoAplicacao($p);
        $html .= self::quadroDeFontes($p);
        $html .= self::desembolso($p);
        $html .= self::assinatura($p);

        return $html;
    }

    // ------------------------------------------------------------------ peças

    private static function identificacao(Proposta $p): string
    {
        $osc   = $p->osc;
        $orgao = $p->chamamento?->programa?->orgao?->name;

        $linhas = [
            'Organização da Sociedade Civil' => $osc?->name,
            'CNPJ'                           => $osc?->cnpj,
            'Endereço'                       => $osc?->endereco,
            'Representante legal'            => $osc?->resp_nome,
            'Unidade Gestora'                => $orgao,
            'Chamamento'                     => trim(($p->chamamento?->numero ? $p->chamamento->numero . ' — ' : '') . $p->chamamento?->titulo),
        ];

        $html = '<p><strong>1. Identificação</strong></p><table style="width:100%;border-collapse:collapse" border="1" cellpadding="6"><tbody>';
        foreach ($linhas as $rotulo => $valor) {
            $html .= '<tr><td style="width:30%"><strong>' . $rotulo . '</strong></td><td>' . e($valor ?: '—') . '</td></tr>';
        }

        return $html . '</tbody></table>';
    }

    private static function descritivo(Proposta $p): string
    {
        $html = '<p><strong>2. Objeto e justificativa</strong></p>';

        foreach ([
            'Título'                     => $p->titulo,
            'Objeto'                     => $p->objeto,
            'Descrição da realidade'     => $p->descricao_realidade,
            'Justificativa'              => $p->justificativa,
            'Público-alvo'               => $p->publico_alvo,
            'Objetivos'                  => $p->objetivos,
        ] as $rotulo => $valor) {
            if (filled($valor)) {
                $html .= '<p><strong>' . $rotulo . ':</strong> ' . nl2br(e($valor)) . '</p>';
            }
        }

        $vigencia = [];
        if ($p->data_inicio_prevista) {
            $vigencia[] = 'início previsto em ' . $p->data_inicio_prevista->format('d/m/Y');
        }
        if ($p->data_fim_prevista) {
            $vigencia[] = 'término previsto em ' . $p->data_fim_prevista->format('d/m/Y');
        }
        if ($p->vigencia_dias) {
            $vigencia[] = $p->vigencia_dias . ' dias corridos';
        }
        if ($vigencia) {
            $html .= '<p><strong>Vigência proposta:</strong> ' . e(implode('; ', $vigencia)) . '.</p>';
        }

        if ($p->atuacao_rede) {
            $html .= '<p><strong>Atuação em rede:</strong> ' . e($p->rede_razao_social) . ', CNPJ ' . e($p->rede_cnpj)
                . ($p->rede_municipio ? ', ' . e($p->rede_municipio) : '')
                . ($p->rede_data_termo ? '. Termo de Atuação em Rede assinado em ' . $p->rede_data_termo->format('d/m/Y') : '')
                . '.</p>';
        }

        return $html;
    }

    private static function enderecos(Proposta $p): string
    {
        if ($p->enderecosExecucao->isEmpty()) {
            return '';
        }

        $html = '<p><strong>3. Endereços de execução</strong></p><ul>';
        foreach ($p->enderecosExecucao as $end) {
            /** @var PlanoEndereco $end */
            $html .= '<li>' . e($end->endereco) . ($end->descricao ? ' — ' . e($end->descricao) : '') . '</li>';
        }

        return $html . '</ul>';
    }

    private static function cronogramaExecucao(Proposta $p): string
    {
        $html = '<p><strong>4. Cronograma de execução (metas)</strong></p>'
            . '<table style="width:100%;border-collapse:collapse" border="1" cellpadding="6"><thead><tr>'
            . '<th>Nº</th><th>Meta</th><th>Atividades</th><th>Indicadores</th>'
            . '<th>Meios de verificação</th><th>Resultados esperados</th><th>Valor</th><th>Período</th>'
            . '</tr></thead><tbody>';

        foreach ($p->metas as $meta) {
            $periodo = trim(($meta->data_inicio?->format('d/m/Y') ?? '—') . ' a ' . ($meta->data_fim?->format('d/m/Y') ?? '—'));
            $html .= '<tr>'
                . '<td>' . $meta->numero . '</td>'
                . '<td>' . e($meta->descricao) . '</td>'
                . '<td>' . e($meta->atividades ?: '—') . '</td>'
                . '<td>' . e($meta->indicador ?: '—') . '</td>'
                . '<td>' . e($meta->meios_verificacao ?: '—') . '</td>'
                . '<td>' . e($meta->resultados_esperados ?: '—') . '</td>'
                . '<td style="text-align:right">' . self::moeda($meta->valor) . '</td>'
                . '<td>' . e($periodo) . '</td>'
                . '</tr>';

            foreach ($meta->etapas as $etapa) {
                $html .= '<tr><td></td><td colspan="7">Etapa ' . $etapa->numero . ': ' . e($etapa->descricao)
                    . ($etapa->responsavel ? ' — responsável: ' . e($etapa->responsavel) : '') . '</td></tr>';
            }
        }

        if ($p->metas->isEmpty()) {
            $html .= '<tr><td colspan="8">Sem metas cadastradas.</td></tr>';
        } else {
            $html .= '<tr><td colspan="6" style="text-align:right"><strong>Total</strong></td>'
                . '<td style="text-align:right"><strong>' . self::moeda($p->totalDasMetas()) . '</strong></td><td></td></tr>';
        }

        return $html . '</tbody></table>';
    }

    private static function planoAplicacao(Proposta $p): string
    {
        $html = '<p><strong>5. Plano de aplicação dos recursos (I — Demonstrativo de recursos)</strong></p>'
            . '<table style="width:100%;border-collapse:collapse" border="1" cellpadding="6"><thead><tr>'
            . '<th>Item</th><th>Descrição</th><th>Tipo de despesa</th><th>Unid.</th>'
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

    private static function quadroDeFontes(Proposta $p): string
    {
        $quadro = $p->quadroDeFontes();

        $html = '<p><strong>6. Valor total da proposta e contrapartida (II)</strong></p>'
            . '<table style="width:100%;border-collapse:collapse" border="1" cellpadding="6"><thead><tr>'
            . '<th>Especificação</th><th>Valor</th><th>% do total</th></tr></thead><tbody>';

        foreach ($quadro['linhas'] as $linha) {
            $html .= '<tr><td>' . e($linha['nome']) . '</td>'
                . '<td style="text-align:right">' . self::moeda($linha['valor']) . '</td>'
                . '<td style="text-align:right">' . number_format($linha['percentual'], 2, ',', '.') . '%</td></tr>';
        }

        return $html . '<tr><td><strong>Total</strong></td>'
            . '<td style="text-align:right"><strong>' . self::moeda($quadro['total']) . '</strong></td>'
            . '<td style="text-align:right">' . ($quadro['total'] > 0 ? '100,00%' : '0,00%') . '</td></tr>'
            . '</tbody></table>';
    }

    private static function desembolso(Proposta $p): string
    {
        $html = '<p><strong>7. Cronograma de desembolso</strong></p>'
            . '<table style="width:100%;border-collapse:collapse" border="1" cellpadding="6"><thead><tr>'
            . '<th>Ano</th><th>Mês</th><th>Valor</th></tr></thead><tbody>';

        foreach ($p->desembolsos as $parcela) {
            /** @var PlanoDesembolso $parcela */
            $html .= '<tr><td>' . $parcela->ano . '</td><td>' . e($parcela->mesLabel()) . '</td>'
                . '<td style="text-align:right">' . self::moeda($parcela->valor) . '</td></tr>';
        }

        if ($p->desembolsos->isEmpty()) {
            $html .= '<tr><td colspan="3">Sem parcelas lançadas.</td></tr>';
        } else {
            $html .= '<tr><td colspan="2" style="text-align:right"><strong>Total</strong></td>'
                . '<td style="text-align:right"><strong>' . self::moeda($p->totalDesembolso()) . '</strong></td></tr>';
        }

        return $html . '</tbody></table>';
    }

    private static function assinatura(Proposta $p): string
    {
        return '<p style="text-align:right">São Gonçalo do Rio Abaixo, '
            . now()->locale('pt_BR')->translatedFormat('j \d\e F \d\e Y') . '.</p>'
            . '<p style="text-align:center"><br>' . e($p->osc?->resp_nome ?: '—')
            . '<br>Representante legal — ' . e($p->osc?->name ?: '—') . '</p>';
    }

    private static function moeda($v): string
    {
        return 'R$ ' . number_format((float) $v, 2, ',', '.');
    }
}

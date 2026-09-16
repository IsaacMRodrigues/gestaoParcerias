<?php

namespace App\Support;

use App\Models\Despesa;
use App\Models\PrestacaoContas;

/**
 * Monta os documentos da prestação de contas a partir dos campos preenchidos.
 *
 * A cliente pediu duas coisas: que estes modelos fossem "para eles preencherem
 * como campo" e que as planilhas "colocassem fórmula". É isto: a OSC preenche
 * campos, e o documento que vai à assinatura é gerado com as somas já feitas —
 * ninguém digita um total, e nenhum total sai errado.
 *
 * O texto é regerado a cada gravação, enquanto o documento não estiver
 * assinado. Depois de assinado, nada mais o altera: é o que a assinatura
 * eletrônica garante a quem valida o documento pelo código.
 */
class PrestacaoDocumento
{
    public static function dinheiro(float|string|null $v): string
    {
        return 'R$ ' . number_format((float) $v, 2, ',', '.');
    }

    private static function data(?\DateTimeInterface $d): string
    {
        return $d ? $d->format('d/m/Y') : '—';
    }

    private static function cabecalho(PrestacaoContas $pc): string
    {
        $osc = $pc->osc();
        $ins = $pc->instrumento;

        return '<table style="width:100%;border-collapse:collapse" border="1" cellpadding="6"><tbody>'
            . '<tr><td><strong>OSC PARCEIRA:</strong> ' . e($osc?->name) . '</td>'
            . '<td><strong>CNPJ:</strong> ' . e($osc?->cnpj) . '</td></tr>'
            . '<tr><td><strong>TIPO E Nº DO TERMO:</strong> ' . e(\App\Models\Instrumento::TIPOS[$ins?->tipo] ?? '') . ' nº ' . e($ins?->numero) . '</td>'
            . '<td><strong>PRESTAÇÃO DE CONTAS:</strong> ' . e($pc->tipo === 'final' ? 'Final' : 'Parcial nº ' . ($pc->numero ?: 1)) . '</td></tr>'
            . '<tr><td><strong>PERÍODO:</strong> ' . self::data($pc->periodo_inicio) . ' a ' . self::data($pc->periodo_fim) . '</td>'
            . '<td><strong>VALOR TOTAL DA PARCERIA:</strong> ' . self::dinheiro($ins?->valor_repasse) . '</td></tr>'
            . '<tr><td colspan="2"><strong>OBJETO:</strong> ' . e($ins?->objeto) . '</td></tr>'
            . '</tbody></table>';
    }

    /** Anexo I — ofício de encaminhamento. */
    public static function oficio(PrestacaoContas $pc): string
    {
        $anexos = ['Relatório de Execução do Objeto — REO', 'Documentos que comprovam o REO',
            'Relatório de Conciliação Bancária', 'Relatório de Comprovantes de Despesas',
            'Relatório de Metas Financeiras', 'Relação de Bens Móveis',
            'Extratos bancários do período (conta corrente e aplicação)',
            'Comprovantes de despesas, em ordem cronológica', 'Termo de compromisso'];

        return '<p style="text-align:center"><strong>OFÍCIO DE ENCAMINHAMENTO DA PRESTAÇÃO DE CONTAS</strong></p>'
            . self::cabecalho($pc)
            . '<p><br></p><p>Ao(À) Sr(a). Gestor(a) de Convênio/Parceria<br>Prefeitura Municipal de São Gonçalo do Rio Abaixo</p>'
            . '<p>Encaminhamos a V. Sª. a prestação de contas referente ao termo acima identificado, composta de '
            . '<strong>' . ($pc->folhas ?: '___') . '</strong> folhas numeradas, contendo a documentação comprobatória e os seguintes anexos:</p>'
            . '<ul><li>' . implode('</li><li>', $anexos) . '</li></ul>'
            . '<p>Foram recebidas <strong>' . ($pc->parcelas_recebidas ?: '___') . '</strong> parcela(s) do termo de parceria no período.</p>'
            . '<p>Informamos que o responsável pela prestação de contas é o(a) Sr(a). <strong>' . e($pc->responsavel_nome ?: '___') . '</strong>'
            . ', e-mail: ' . e($pc->responsavel_email ?: '___') . ', telefone: ' . e($pc->responsavel_telefone ?: '___') . '.</p>'
            . '<p>Colocamo-nos à disposição de V. Sa. para quaisquer informações adicionais.</p>'
            . self::assinatura($pc);
    }

    /** Anexo III + Anexos IV a VII — o relatório que a OSC assina. */
    public static function relatorio(PrestacaoContas $pc): string
    {
        return '<p style="text-align:center"><strong>RELATÓRIO DE EXECUÇÃO DO OBJETO E DE EXECUÇÃO FINANCEIRA</strong></p>'
            . self::cabecalho($pc)
            . self::descricao($pc)
            . self::monitoramentoMetas($pc)
            . self::conciliacaoBancaria($pc)
            . self::comprovantesDespesas($pc)
            . self::metasFinanceiras($pc)
            . self::bensMoveis($pc)
            . '<p><br></p><p><strong>4 — JUSTIFICATIVAS, INFORMAÇÕES COMPLEMENTARES E CONCLUSÃO</strong></p>'
            . '<p>' . nl2br(e($pc->conclusao ?: '—')) . '</p>'
            . self::assinatura($pc, true);
    }

    private static function descricao(PrestacaoContas $pc): string
    {
        return '<p><br></p><p><strong>1 — DESCRIÇÃO DA PARCERIA</strong></p>'
            . '<p><strong>Objetivo geral:</strong><br>' . nl2br(e($pc->objetivo_geral ?: '—')) . '</p>'
            . '<p><strong>Objetivos específicos:</strong><br>' . nl2br(e($pc->objetivos_especificos ?: '—')) . '</p>';
    }

    private static function monitoramentoMetas(PrestacaoContas $pc): string
    {
        $linhas = '';
        foreach ($pc->metas as $m) {
            $linhas .= '<tr><td>' . e($m->descricao) . '</td><td>' . e($m->quantidade_prevista ?: '—') . '</td>'
                . '<td>' . e($m->quantidade_atendida ?: '—') . '</td><td>' . ($m->cumpriu ? 'Sim' : 'Não') . '</td>'
                . '<td>' . nl2br(e($m->justificativa ?: '')) . '</td></tr>';
        }

        if ($linhas === '') {
            $linhas = '<tr><td colspan="5">Nenhuma meta registrada.</td></tr>';
        }

        return '<p><br></p><p><strong>2 — MONITORAMENTO DAS METAS</strong></p>'
            . '<table style="width:100%;border-collapse:collapse" border="1" cellpadding="6"><thead><tr>'
            . '<th>Meta</th><th>Quantidade prevista</th><th>Quantidade atendida</th>'
            . '<th>Cumprimento das ações programadas</th><th>Justificativa</th></tr></thead>'
            . '<tbody>' . $linhas . '</tbody></table>';
    }

    /** Anexo IV — o saldo sai da conta, não do teclado. */
    private static function conciliacaoBancaria(PrestacaoContas $pc): string
    {
        $linha = fn (string $r, float $v, bool $forte = false) => '<tr><td>' . ($forte ? "<strong>$r</strong>" : $r) . '</td>'
            . '<td style="text-align:right">' . ($forte ? '<strong>' . self::dinheiro($v) . '</strong>' : self::dinheiro($v)) . '</td></tr>';

        return '<p><br></p><p><strong>ANEXO IV — CONCILIAÇÃO BANCÁRIA</strong></p>'
            . '<p>Banco: ' . e($pc->banco ?: '—') . ' · Agência: ' . e($pc->agencia ?: '—')
            . ' · Conta específica: ' . e($pc->conta_corrente ?: '—') . '</p>'
            . '<table style="width:100%;border-collapse:collapse" border="1" cellpadding="6"><tbody>'
            . $linha('Saldo do período anterior', (float) $pc->saldo_anterior)
            . $linha('Repasses recebidos no período', $pc->totalRepassado())
            . $linha('Outros créditos em conta/aplicação', (float) $pc->outros_creditos)
            . $linha('Recursos próprios/terceiros', (float) $pc->recursos_proprios)
            . $linha('TOTAL DE CRÉDITOS', $pc->totalCreditos(), true)
            . $linha('Despesas pagas no período', $pc->totalGasto())
            . $linha('Despesas bancárias', (float) $pc->despesas_bancarias)
            . $linha('TOTAL DE DÉBITOS', $pc->totalDebitos(), true)
            . $linha('Valor ressarcido aos cofres públicos', (float) $pc->valor_ressarcido)
            . $linha('SALDO ATUAL', $pc->saldoAtual(), true)
            . '</tbody></table>';
    }

    /** Anexo V — as despesas já lançadas na Execução, somadas por grupo. */
    private static function comprovantesDespesas(PrestacaoContas $pc): string
    {
        $linhas = '';
        foreach ($pc->despesas() as $d) {
            $linhas .= '<tr><td>' . self::data($d->data_despesa) . '</td><td>' . e($d->fornecedor ?: '—') . '</td>'
                . '<td>' . e(Despesa::NATUREZAS[$d->natureza] ?? $d->natureza) . '</td>'
                . '<td>' . e($d->nota_fiscal_numero ?: '—') . '</td>'
                . '<td style="text-align:right">' . self::dinheiro($d->valor) . '</td></tr>';
        }

        if ($linhas === '') {
            $linhas = '<tr><td colspan="5">Nenhuma despesa lançada no período.</td></tr>';
        }

        $total = fn (string $r, float $v) => '<tr><td colspan="4"><strong>' . $r . '</strong></td>'
            . '<td style="text-align:right"><strong>' . self::dinheiro($v) . '</strong></td></tr>';

        return '<p><br></p><p><strong>ANEXO V — COMPROVANTES DE DESPESAS</strong></p>'
            . '<table style="width:100%;border-collapse:collapse" border="1" cellpadding="6"><thead><tr>'
            . '<th>Data</th><th>Fornecedor/credor</th><th>Natureza</th><th>Documento</th><th>Valor</th>'
            . '</tr></thead><tbody>' . $linhas
            . $total('Total — despesas com pessoal (folha e encargos)', $pc->totalComPessoal())
            . $total('Total — demais despesas', $pc->totalDemaisDespesas())
            . $total('TOTAL GERAL', $pc->totalGasto())
            . '</tbody></table>';
    }

    /** Anexo VI — aprovado no plano, executado, glosado e o que sobra. */
    private static function metasFinanceiras(PrestacaoContas $pc): string
    {
        $linhas = '';
        $somaAprovado = $somaExecutado = $somaGlosado = $somaSaldo = 0.0;

        foreach (PrestacaoContas::BLOCOS as $chave => $bloco) {
            $aprovado  = (float) ($pc->glosas->firstWhere('natureza', $chave)?->valor_aprovado ?? 0);
            $executado = $pc->totalDoBloco($chave);
            $glosado   = $pc->glosaDoBloco($chave);
            $saldo     = $pc->saldoDoBloco($chave);
            $somaAprovado += $aprovado; $somaExecutado += $executado;
            $somaGlosado += $glosado;   $somaSaldo += $saldo;

            $linhas .= '<tr><td>' . $bloco['rotulo'] . '</td>'
                . '<td style="text-align:right">' . self::dinheiro($aprovado) . '</td>'
                . '<td style="text-align:right">' . self::dinheiro($executado) . '</td>'
                . '<td style="text-align:right">' . self::dinheiro($glosado) . '</td>'
                . '<td style="text-align:right">' . self::dinheiro($saldo) . '</td></tr>';
        }

        return '<p><br></p><p><strong>ANEXO VI — RELATÓRIO DE METAS FINANCEIRAS</strong></p>'
            . '<table style="width:100%;border-collapse:collapse" border="1" cellpadding="6"><thead><tr>'
            . '<th>Natureza da despesa</th><th>Aprovado no Plano de Trabalho</th><th>Executado</th>'
            . '<th>Glosado</th><th>Saldo</th></tr></thead><tbody>' . $linhas
            . '<tr><td><strong>TOTAL GERAL</strong></td>'
            . '<td style="text-align:right"><strong>' . self::dinheiro($somaAprovado) . '</strong></td>'
            . '<td style="text-align:right"><strong>' . self::dinheiro($somaExecutado) . '</strong></td>'
            . '<td style="text-align:right"><strong>' . self::dinheiro($somaGlosado) . '</strong></td>'
            . '<td style="text-align:right"><strong>' . self::dinheiro($somaSaldo) . '</strong></td></tr>'
            . '</tbody></table>';
    }

    /** Anexo VII — bens móveis adquiridos com o recurso. */
    private static function bensMoveis(PrestacaoContas $pc): string
    {
        $linhas = '';
        foreach ($pc->bens as $b) {
            $linhas .= '<tr><td>' . e($b->especificacao) . '</td>'
                . '<td style="text-align:right">' . number_format((float) $b->quantidade, 2, ',', '.') . '</td>'
                . '<td style="text-align:right">' . self::dinheiro($b->valor_unitario) . '</td>'
                . '<td>' . e($b->documento ?: '—') . '</td>'
                . '<td style="text-align:right">' . self::dinheiro($b->total()) . '</td></tr>';
        }

        if ($linhas === '') {
            $linhas = '<tr><td colspan="5">Nenhum bem móvel adquirido no período.</td></tr>';
        }

        return '<p><br></p><p><strong>ANEXO VII — RELAÇÃO DE BENS MÓVEIS</strong></p>'
            . '<table style="width:100%;border-collapse:collapse" border="1" cellpadding="6"><thead><tr>'
            . '<th>Especificação do bem</th><th>Quantidade</th><th>Valor unitário</th><th>Documento</th><th>Valor total</th>'
            . '</tr></thead><tbody>' . $linhas
            . '<tr><td colspan="4"><strong>TOTAL</strong></td>'
            . '<td style="text-align:right"><strong>' . self::dinheiro($pc->totalBens()) . '</strong></td></tr>'
            . '</tbody></table>';
    }

    /**
     * Anexo 16 — resumo da folha.
     *
     * É o modelo que veio sem nenhuma fórmula: proventos, descontos, líquido e
     * encargos estavam todos digitados. Aqui saem calculados dos lançamentos.
     */
    public static function resumoFolha(PrestacaoContas $pc): string
    {
        $l = fn (string $r, float $v, bool $forte = false) => '<tr><td>' . ($forte ? "<strong>$r</strong>" : $r) . '</td>'
            . '<td style="text-align:right">' . ($forte ? '<strong>' . self::dinheiro($v) . '</strong>' : self::dinheiro($v)) . '</td></tr>';

        return '<p style="text-align:center"><strong>RESUMO DA FOLHA DE PAGAMENTO</strong></p>'
            . self::cabecalho($pc)
            . '<p><br></p><p>Total de funcionários: <strong>' . (int) $pc->folha_funcionarios . '</strong></p>'
            . '<table style="width:100%;border-collapse:collapse" border="1" cellpadding="6"><tbody>'
            . $l('Total do salário', (float) $pc->folha_salarios)
            . $l('Total das vantagens', (float) $pc->folha_vantagens)
            . $l('Total dos adicionais', (float) $pc->folha_adicionais)
            . $l('TOTAL DE PROVENTOS', $pc->folhaProventos(), true)
            . $l('INSS', (float) $pc->folha_inss)
            . $l('IRRF', (float) $pc->folha_irrf)
            . $l('Plano de saúde', (float) $pc->folha_plano_saude)
            . $l('TOTAL DE RETENÇÃO (DESCONTOS)', $pc->folhaDescontos(), true)
            . $l('TOTAL LÍQUIDO', $pc->folhaLiquido(), true)
            . $l('FGTS', (float) $pc->folha_fgts)
            . $l('Férias', (float) $pc->folha_ferias)
            . $l('Rescisão', (float) $pc->folha_rescisao)
            . $l('TOTAL DE ENCARGOS PATRONAIS', $pc->folhaEncargos(), true)
            . '</tbody></table>'
            . self::assinatura($pc, true);
    }

    /**
     * Fecho. As planilhas pedem a assinatura do representante legal e a do
     * profissional de contabilidade; o ofício, só a do representante.
     */
    private static function assinatura(PrestacaoContas $pc, bool $comContador = false): string
    {
        $osc = $pc->osc();
        $html = '<p style="text-align:right">São Gonçalo do Rio Abaixo, {{data_extenso}}.</p>'
            . '<p style="text-align:center"><br>' . e($osc?->resp_nome) . '<br>Representante legal — ' . e($osc?->name) . '</p>';

        if ($comContador) {
            $html .= '<p style="text-align:center"><br>_______________________________<br>'
                . 'Profissional de contabilidade (nome, assinatura e registro no CRC)</p>';
        }

        return $html;
    }
}

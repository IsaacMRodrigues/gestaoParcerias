<?php

namespace App\Http\Controllers;

use App\Models\Chamamento;
use App\Models\OrdemPagamento;
use App\Models\Peca;
use App\Models\ProcessoPeca;
use App\Models\Processo;
use App\Models\Proposta;
use Illuminate\View\View;

/**
 * Catálogo dos modelos padrão do sistema — tela de apoio do TI
 * (perfil Administrador Setorial) para conferir, num lugar só, todos os
 * textos-modelo que alimentam as peças dos trâmites.
 */
class ModeloController extends Controller
{
    /**
     * De onde vem cada grupo de modelos, com o rótulo mostrado na tela.
     * A chave é usada na URL (`/modelos/{origem}/{chave}`).
     */
    public const ORIGENS = [
        'processo'                 => 'Planejamento — trâmite do Processo',
        'chamamento_publico'       => 'Seleção — Chamamento Público',
        'celebracao'               => 'Celebração da Parceria',
        'dispensa_inexigibilidade' => 'Dispensa / Inexigibilidade',
        'aditivo'                  => 'Termo Aditivo',
        'apostilamento'            => 'Apostilamento',
        'ordem_pagamento'          => 'Ordem de Pagamento',
    ];

    public function index(): View
    {
        $grupos = [];

        foreach (array_keys(self::ORIGENS) as $origem) {
            $grupos[$origem] = $this->modelosDe($origem);
        }

        $resumo = [
            'total'     => collect($grupos)->flatten(1)->count(),
            'com_texto' => collect($grupos)->flatten(1)->where('tem_texto', true)->count(),
        ];

        return view('modelos.index', compact('grupos', 'resumo'));
    }

    public function show(string $origem, string $chave): View
    {
        abort_unless(isset(self::ORIGENS[$origem]), 404);

        $modelo = collect($this->modelosDe($origem))->firstWhere('chave', $chave);
        abort_unless($modelo, 404, 'Modelo não encontrado.');

        $conteudo = $this->texto($origem, $chave);
        abort_unless($conteudo !== null, 404, 'Este item ainda não tem texto-modelo.');

        return view('modelos.show', compact('modelo', 'origem', 'conteudo'));
    }

    /**
     * Lista os modelos de uma origem, já com setor/etapa responsáveis.
     */
    private function modelosDe(string $origem): array
    {
        if ($origem === 'processo') {
            return collect(ProcessoPeca::TIPOS)
                ->map(fn ($rotulo, $chave) => [
                    'chave'     => $chave,
                    'rotulo'    => $rotulo,
                    'setor'     => ProcessoPeca::SETOR_RESPONSAVEL[$chave] ?? null,
                    'etapa'     => ProcessoPeca::ETAPA[$chave] ?? null,
                    'tem_texto' => isset(ProcessoPeca::MODELO[$chave]),
                    'arquivo'   => in_array($chave, ProcessoPeca::ARQUIVO, true),
                ])
                // peças ARQUIVO são anexos, não têm texto-modelo
                ->reject(fn ($m) => $m['arquivo'])
                ->values()->all();
        }

        if ($origem === 'ordem_pagamento') {
            return [
                ['chave' => 'global',   'rotulo' => 'Ordem de Pagamento Global (empenho do exercício)',
                 'setor' => 'scp', 'etapa' => null, 'tem_texto' => true, 'arquivo' => false],
                ['chave' => 'parcial',  'rotulo' => 'Ordem de Pagamento Parcial (subempenho da parcela)',
                 'setor' => 'scp', 'etapa' => null, 'tem_texto' => true, 'arquivo' => false],
                ['chave' => 'generico', 'rotulo' => 'Ordem de Pagamento (modelo genérico)',
                 'setor' => 'ug',  'etapa' => null, 'tem_texto' => true, 'arquivo' => false],
            ];
        }

        // Demais origens são categorias do motor de peças (Peca).
        $setores = match ($origem) {
            'chamamento_publico' => Peca::SELECAO_SETOR,
            'celebracao'         => Peca::CELEBRACAO_SETOR,
            default              => [],
        };
        $etapas = match ($origem) {
            'chamamento_publico' => Peca::SELECAO_ETAPA,
            'celebracao'         => Peca::CELEBRACAO_ETAPA,
            default              => [],
        };

        return collect(Peca::TEMPLATES[$origem] ?? [])
            ->where('tipo', 'modelo')
            ->map(fn ($item) => [
                'chave'     => $item['chave'],
                'rotulo'    => $item['rotulo'],
                'setor'     => $setores[$item['chave']] ?? null,
                'etapa'     => $etapas[$item['chave']] ?? null,
                'tem_texto' => Peca::modeloTexto($origem, $item['chave']) !== null,
                'arquivo'   => false,
            ])
            ->values()->all();
    }

    /** Texto HTML do modelo, conforme a origem. */
    private function texto(string $origem, string $chave): ?string
    {
        return match ($origem) {
            'processo'        => ProcessoPeca::MODELO[$chave] ?? null,
            'ordem_pagamento' => match ($chave) {
                'global'   => OrdemPagamento::MODELO_GLOBAL,
                'parcial'  => OrdemPagamento::MODELO_PARCIAL,
                'generico' => OrdemPagamento::MODELO,
                default    => null,
            },
            default => Peca::modeloTexto($origem, $chave),
        };
    }

    /**
     * Rótulo do setor conforme o trâmite da origem (cada um tem o seu mapa).
     */
    public static function setorLabel(string $origem, ?string $setor): ?string
    {
        if (!$setor) {
            return null;
        }

        return match ($origem) {
            'celebracao'         => Proposta::SETORES_CELEBRACAO[$setor] ?? $setor,
            'chamamento_publico' => Chamamento::SETORES_SELECAO[$setor] ?? $setor,
            default              => Processo::SETORES[$setor] ?? strtoupper($setor),
        };
    }
}

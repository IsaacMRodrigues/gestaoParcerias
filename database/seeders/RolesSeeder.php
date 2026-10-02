<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesSeeder extends Seeder
{
    /**
     * Permissões por área do sistema.
     */
    public const PERMISSOES = [
        'cadastros'         => 'Cadastros (usuários, órgãos, OSCs)',
        'planejamento'      => 'Planejamento (processos, termo de referência, trâmite)',
        'chamamentos'       => 'Chamamentos',
        'propostas'         => 'Propostas e Plano de Trabalho',
        'pareceres_tecnico' => 'Parecer Técnico',
        'pareceres_juridico'=> 'Parecer Jurídico',
        'pareceres_decisao' => 'Decisão / Seleção',
        'formalizacao'      => 'Formalização (instrumentos e aditivos)',
        'monitoramento'     => 'Monitoramento e Fiscalização',
        'prestacao_contas'  => 'Prestação de Contas',
        'ordem_pagamento'   => 'Ordem de Pagamento (execução do concedente)',
        'execucao'          => 'Execução financeira (repasses, despesas, notas fiscais)',
        'usuarios_setor'    => 'Cadastrar usuários do próprio setor (o administrador aprova)',
        'suporte'           => 'Atender o suporte (ver e responder os chamados de todos)',
        // Só as contas de OSC. Quem tem `cadastros` já aprova todas, inclusive
        // estas; esta permissão existe para a SCP decidir sobre a organização
        // que ela atende sem ganhar a mesa de cadastros da Prefeitura.
        'aprovar_contas_osc' => 'Aprovar contas de integrantes de OSC',

        // Funções da equipe da OSC: prefixo osc_ para nunca valerem dentro da Administração.
        'osc_propostas'     => 'Propostas e plano de trabalho',
        'osc_documentos'    => 'Documentos da organização',
        'osc_manifestacoes' => 'Manifestações de interesse',
        'osc_celebracao'    => 'Celebração da parceria',
    ];

    /** As quatro funções que o responsável legal distribui na equipe. */
    public const FUNCOES_OSC = [
        'osc_propostas',
        'osc_documentos',
        'osc_manifestacoes',
        'osc_celebracao',
    ];

    /**
     * Matriz perfil => permissões. '*' = todas; auditores recebem todas, só leitura (middleware readonly).
     */
    public const MATRIZ = [
        'administrador_setorial'           => ['*'],
        'auditor_externo'                  => ['*'], // somente leitura
        'auditor_geral'                    => ['*'], // somente leitura
        'responsavel_unidade_gestora'      => ['planejamento', 'chamamentos', 'propostas', 'pareceres_decisao', 'formalizacao', 'ordem_pagamento', 'execucao', 'prestacao_contas', 'usuarios_setor'],
        // Chefia: só a porta de cadastrar a equipe; acumula-se com o perfil técnico da pessoa.
        'chefe_setor'                      => ['usuarios_setor'],
        // Prefeito: assina o Termo de Adjudicação e Homologação que encerra a Seleção.
        'prefeito_municipal'               => ['chamamentos', 'formalizacao'],
        // A SCP conduz a parceria do edital ao empenho e segue na execução e na prestação de contas.
        'analista_tecnico_scp'             => ['planejamento', 'chamamentos', 'execucao', 'prestacao_contas', 'suporte', 'aprovar_contas_osc'],
        'responsavel_publicacao'           => ['chamamentos'],
        // Quem responde pela SEPLAN: o único que assina o Parecer Financeiro
        // (Peca::ASSINATURA_RESERVADA; elaborar é de qualquer um da SEPLAN) e
        // quem cadastra a equipe do setor — ver User::RESPONSAVEL_DO_SETOR.
        'responsavel_seplan'               => ['planejamento', 'usuarios_setor'],
        'analista_orcamentario_financeiro' => ['planejamento'],
        'analista_juridico'                => ['pareceres_juridico', 'planejamento'],
        'analista_viabilidade_tecnica'     => ['pareceres_tecnico'],
        'analista_aditivo_apostilamento'   => ['formalizacao'],
        'analista_prestacao_contas_previa' => ['prestacao_contas'],
        'comissao_selecao'                 => ['propostas', 'pareceres_tecnico', 'pareceres_decisao'],
        // Comissões separadas: quem acompanha não é quem avalia.
        'comissao_monitoramento'           => ['monitoramento'],
        'comissao_avaliacao'               => ['prestacao_contas'],
        'gestor_parceria'                  => ['planejamento', 'monitoramento', 'execucao', 'prestacao_contas'],
        'cadastrador'                      => ['chamamentos', 'propostas', 'formalizacao'],
        'contador'                         => ['prestacao_contas'],
        'encaminhador'                     => ['formalizacao'],
        'operador_ordem_pagamento'         => ['ordem_pagamento'],
        'aprovador_assinatura_eletronica'  => [], // módulo de assinatura (futuro)
        'analista'                         => [], // acesso básico, em descontinuação
        // Responde pela entidade: faz tudo no portal, e só ele submete, recorre
        // e contra-assina (isso não é permissão marcável — ver ehResponsavelLegalOsc).
        'responsavel_legal'                => self::FUNCOES_OSC,
        // O papel é a identidade "sou da equipe desta OSC"; o que cada um faz
        // vem marcado por pessoa, no cadastro, e não pelo papel.
        'membro_osc'                       => [], // equipe da OSC: portal, sem submeter/recorrer

        // Perfis do convenente: sem permissão; declaram o papel na OSC e saem na assinatura.
        // Quem abre porta são as funções osc_*.
        'cadastrador_proposta'               => [],
        'cadastrador_prestacao_contas'       => [],
        'cadastrador_usuario_entidade'       => [],
        'contador_osc'                     => [],
        'responsavel_execucao_osc'         => [],
    ];

    public function run(): void
    {
        foreach (array_keys(self::PERMISSOES) as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $todas = array_keys(self::PERMISSOES);

        foreach (self::MATRIZ as $role => $perms) {
            $r = Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
            $r->syncPermissions($perms === ['*'] ? $todas : $perms);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

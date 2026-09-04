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

        /*
         * Funções da equipe da OSC.
         *
         * Prefixadas `osc_` de propósito: são de outro mundo que as permissões
         * acima, que abrem módulos da Prefeitura. Assim o responsável legal
         * escolhe o que cada integrante faz sem que nada do que ele marque
         * possa, por descuido de nomenclatura, valer dentro da Administração.
         */
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
     * Matriz perfil => permissões (perfis do Módulo 1).
     * '*' = todas. Auditores recebem todas porém apenas leitura (middleware readonly).
     */
    public const MATRIZ = [
        'administrador_setorial'           => ['*'],
        'auditor_externo'                  => ['*'], // somente leitura
        'auditor_geral'                    => ['*'], // somente leitura
        'responsavel_unidade_gestora'      => ['planejamento', 'chamamentos', 'propostas', 'pareceres_decisao', 'formalizacao', 'ordem_pagamento', 'execucao', 'usuarios_setor'],
        // Chefia de setor: não abre módulo nenhum, só a porta de cadastrar a
        // própria equipe. Acumula-se com o perfil técnico da pessoa (o chefe da
        // PJ é 'analista_juridico' + 'chefe_setor'), para que a chefia não vire
        // atalho para permissões que o setor não tem.
        'chefe_setor'                      => ['usuarios_setor'],
        // Prefeito: assina o Termo de Adjudicação e Homologação que encerra a Seleção.
        'prefeito_municipal'               => ['chamamentos', 'formalizacao'],
        'analista_tecnico_scp'             => ['planejamento', 'chamamentos'],
        'responsavel_publicacao'           => ['chamamentos'],
        'analista_orcamentario_financeiro' => ['planejamento'],
        'analista_juridico'                => ['pareceres_juridico', 'planejamento'],
        'analista_viabilidade_tecnica'     => ['pareceres_tecnico'],
        'analista_aditivo_apostilamento'   => ['formalizacao'],
        'analista_prestacao_contas_previa' => ['prestacao_contas'],
        'comissao_selecao'                 => ['propostas', 'pareceres_tecnico', 'pareceres_decisao'],
        'comissao_monitoramento_avaliacao' => ['monitoramento'],
        'gestor_parceria'                  => ['planejamento', 'monitoramento', 'execucao'],
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

<?php

/**
 * Dez dos treze perfis do convenente saem do sistema.
 *
 * Vieram inteiros da tela de referência do módulo 1, e a maior parte descreve
 * trabalho que esta parceria não tem: licitação da organização, órgão de
 * controle próprio, relatoria de agenda, ordenação de despesa. Numa lista de
 * quinze caixas, os que importam se escondem entre os que nunca serão
 * marcados. Ficam quatro — Membro da OSC, Cadastrador de Proposta, de
 * Prestação de Contas e de Usuário do Ente/Entidade.
 *
 * Só apaga o que ninguém tem. Papel atribuído explica assinatura já dada, e
 * some com ela: se algum estiver em uso, fica onde está e a lista da tela é
 * que deixa de oferecê-lo.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const REMOVIDOS = [
        'cadastrador_usuario_orgao_controle',
        'comissao_licitacao',
        'consultas_basicas_proponente',
        'fiscal_convenente',
        'gestor_instrumento_convenente',
        'gestor_financeiro_convenente',
        'operador_financeiro_convenente',
        'ordenador_despesa_convenente',
        'orgao_controle_convenente',
        'relator_agenda',
    ];

    public function up(): void
    {
        foreach (self::REMOVIDOS as $nome) {
            $papel = Role::where('name', $nome)->first();

            if ($papel && DB::table('model_has_roles')->where('role_id', $papel->id)->doesntExist()) {
                $papel->delete();
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Sem volta: quem os quiser de novo recria pelo RolesSeeder, com a
        // lista que estiver valendo então.
    }
};

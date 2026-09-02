<?php

/**
 * O e-mail institucional da Secretaria é o mesmo da conta do seu responsável:
 * educacao@ é a caixa da Educação, e é com ela que o gestor entra no sistema.
 * Os órgãos foram cadastrados antes das contas e ficaram todos sem e-mail —
 * esta migração recupera o dado de quem já o tem, em vez de pedir de novo.
 *
 * Só copia endereço institucional: as Unidades Gestoras abertas por nome de
 * usuário (Saúde, Fazenda, Trabalho) usam e-mail provisório, e propagá-lo
 * espalharia um endereço que não existe.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const DOMINIO = '@saogoncalo.mg.gov.br';

    public function up(): void
    {
        foreach (DB::table('orgaos')->whereNull('email')->get() as $orgao) {
            if ($email = $this->emailInstitucional($orgao->id)) {
                DB::table('orgaos')->where('id', $orgao->id)->update(['email' => $email]);
            }
        }
    }

    /**
     * O responsável da Unidade Gestora responde pela Secretaria e vem primeiro.
     * Onde não há um — o Planejamento abriga a SCP e a SEPLAN, ambas setores
     * que servem o Município inteiro — vale a única conta institucional lotada
     * ali. Duas ou mais sem responsável: fica em branco, para não escolher no
     * lugar de quem sabe.
     */
    private function emailInstitucional(int $orgaoId): ?string
    {
        $contas = DB::table('users')
            ->where('orgao_id', $orgaoId)
            ->whereNull('osc_id')
            ->where('email', 'like', '%'.self::DOMINIO)
            ->get(['email', 'setor']);

        $responsaveis = $contas->where('setor', 'ug');

        return $responsaveis->count() === 1 ? $responsaveis->first()->email
            : ($contas->count() === 1 ? $contas->first()->email : null);
    }

    public function down(): void
    {
        // Sem volta: o campo estava vazio por falta de cadastro, não por escolha.
    }
};

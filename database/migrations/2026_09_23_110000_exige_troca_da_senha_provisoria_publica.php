<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Quem ainda entra com a senha provisória do quadro da Prefeitura troca no
 * próximo acesso.
 *
 * As contas do quadro nasceram com uma senha comum, gravada na migração
 * `cria_quadro_de_usuarios_da_prefeitura` — e o repositório é público: a
 * senha de todas elas está à vista. Em vez de trocar conta por conta, marca
 * `deve_trocar_senha` em quem ainda a usa (ver ExigeTrocaDeSenha): no próximo
 * acesso, cada pessoa escolhe a sua. Quem já trocou não é tocado.
 *
 * A senha é lida da própria migração de origem, por reflexão, para não
 * aparecer uma segunda vez no repositório.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->contasComASenhaProvisoria() as $id) {
            DB::table('users')->where('id', $id)->update(['deve_trocar_senha' => true]);
        }
    }

    /** Desmarca quem continua com a provisória — os que esta migração marcou e ainda não trocaram. */
    public function down(): void
    {
        foreach ($this->contasComASenhaProvisoria() as $id) {
            DB::table('users')->where('id', $id)->update(['deve_trocar_senha' => false]);
        }
    }

    private function contasComASenhaProvisoria(): array
    {
        $origem = require __DIR__ . '/2026_08_27_120000_cria_quadro_de_usuarios_da_prefeitura.php';
        $senha  = (new ReflectionClass($origem))->getConstant('SENHA');

        if (!is_string($senha) || $senha === '') {
            return [];
        }

        return DB::table('users')->whereNotNull('password')->get(['id', 'password'])
            ->filter(fn ($u) => Hash::check($senha, $u->password))
            ->pluck('id')
            ->all();
    }
};

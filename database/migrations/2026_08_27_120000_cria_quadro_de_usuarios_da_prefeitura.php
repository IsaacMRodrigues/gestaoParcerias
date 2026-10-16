<?php

/**
 * As contas que todo ambiente novo traz: o administrador e a SCP, que aprova os demais cadastros.

 *
 * Não sobrescreve nada: quem já existe (comparando pelo e-mail) fica como
 * está, com a sua senha e os seus perfis. Rodar de novo não muda nada, e num
 * banco que já tem gente — produção — só entra o que estiver faltando.
 *
 * A senha é a mesma para todas, e é provisória. Serve a um ambiente de
 * desenvolvimento; em produção, cada pessoa deve trocar a sua no primeiro
 * acesso, porque estas contas enxergam documentos, CPFs e dados bancários
 * das organizações.
 */

use App\Models\Orgao;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Database\Seeders\UnidadesGestorasSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    private const SENHA = '123456';

    /**
     * nome, e-mail, nome de usuário, setor, código do órgão, perfil.
     *
     * Setor e órgão são coisas diferentes e as duas fazem falta: o setor diz
     * por onde o processo tramita, o órgão diz de qual Secretaria a pessoa
     * enxerga os processos. Quem atende o Município inteiro (SCP, SEPLAN,
     * Procuradoria, Prefeito, TI) está em User::SETORES_TRANSVERSAIS e vê
     * todas as Secretarias, tenha ou não lotação registrada.
     */
    private const QUADRO = [
        // Só o administrador e a SCP: os demais servidores, secretários inclusive, fazem o próprio
        // cadastro e a SCP aprova (ver ContasPreCadastradas).
        ['Pedro Henrique',      'pedrohenrique62669@gmail.com', 'admin_parcerias', 'ti',    null,   'administrador_setorial'],
        ['SCP Raquel',          'scp@gmail.com',                 null,             'scp',   '0214', 'analista_tecnico_scp'],
    ];

    public function up(): void
    {
        // Perfis e Secretarias antes das pessoas: sem eles não há a que
        // vincular. Os dois seeders só criam o que falta, então chamá-los
        // aqui não atrapalha quem já rodou `db:seed`.
        (new RolesSeeder)->run();
        (new UnidadesGestorasSeeder)->run();

        // A Secretaria de Ciência e Tecnologia é posterior ao quadro original
        // e ocupou o lugar do antigo órgão "TI".
        Orgao::firstOrCreate(['codigo' => '0227'], ['name' => 'Ciência e Tecnologia', 'status' => true]);

        foreach (self::QUADRO as [$nome, $email, $login, $setor, $codigoOrgao, $perfil]) {
            if (User::where('email', $email)->exists()) {
                continue;
            }

            // O nome de usuário também é único, e pode já estar com outra
            // conta: em produção a Saúde foi aberta com outro e-mail e este
            // mesmo login. Insistir quebraria a migração no meio — e a
            // Secretaria já tem quem responda por ela, que era o ponto.
            if ($login && User::where('login', $login)->exists()) {
                continue;
            }

            $usuario = User::create([
                'name'            => $nome,
                'email'           => $email,
                'login'           => $login,
                'setor'           => $setor,
                'orgao_id'        => $codigoOrgao ? Orgao::where('codigo', $codigoOrgao)->value('id') : null,
                'password'        => Hash::make(self::SENHA),
                'status'          => true,
                'approval_status' => 'aprovado',
                'approved_at'     => now(),
            ]);

            // O perfil pode não existir num banco antigo; sem esta guarda a
            // migração morreria no meio e deixaria o quadro pela metade.
            if (Role::where('name', $perfil)->exists()) {
                $usuario->assignRole($perfil);
            }
        }
    }

    public function down(): void
    {
        // Sem volta de propósito. Desfazer apagaria contas que, a esta altura,
        // podem ter processos assinados no nome delas — e a assinatura precisa
        // continuar apontando para quem assinou.
    }
};

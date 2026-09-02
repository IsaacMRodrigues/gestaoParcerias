<?php

/**
 * O quadro de contas da Prefeitura, para que todo ambiente novo nasça igual.
 *
 * Estas contas foram abertas uma a uma, à mão, no banco de quem desenvolve.
 * Quem clonasse o repositório recebia o sistema sem ninguém dentro dele: sem
 * gestor de Secretaria, sem Procuradoria, sem quem assina — e não havia como
 * percorrer um processo do começo ao fim para testar. Aqui elas passam a ser
 * parte do código, como já eram as Secretarias e os perfis.
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
        // Administração do sistema e quem serve o Município inteiro.
        ['Pedro Henrique',      'pedrohenrique62669@gmail.com', 'admin_parcerias', 'ti',    null,   'administrador_setorial'],
        ['Prefeito Municipal',  'prefeito@gmail.com',            null,             'pm',    null,   'prefeito_municipal'],
        ['SCP Raquel',          'scp@gmail.com',                 null,             'scp',   '0214', 'analista_tecnico_scp'],
        ['SEPLAN Jesqueline',   'seplan@gmail.com',              null,             'seplan', null,  'analista_orcamentario_financeiro'],
        ['Gestor Planejamento', 'planejamento@saogoncalo.mg.gov.br', null,          'seplan', '0214', 'analista_orcamentario_financeiro'],
        ['Procuradoria Jurídica', 'procurador@saogoncalo.mg.gov.br', null,          'pj',    '0202', 'analista_juridico'],

        // Um responsável por Unidade Gestora, na caixa institucional da Secretaria.
        ['Gestor Governo',                 'governo@saogoncalo.mg.gov.br',            null, 'ug', '0201', 'responsavel_unidade_gestora'],
        ['Gestor Jurídico',                'juridico@saogoncalo.mg.gov.br',           null, 'ug', '0202', 'responsavel_unidade_gestora'],
        ['Gestor Administração',           'administracao@saogoncalo.mg.gov.br',      null, 'ug', '0203', 'responsavel_unidade_gestora'],
        ['Gestor Obras',                   'obras@saogoncalo.mg.gov.br',              null, 'ug', '0205', 'responsavel_unidade_gestora'],
        ['Gestor Educação',                'educacao@saogoncalo.mg.gov.br',           null, 'ug', '0207', 'responsavel_unidade_gestora'],
        ['Gestor Esportes e Lazer',        'esportes@saogoncalo.mg.gov.br',           null, 'ug', '0208', 'responsavel_unidade_gestora'],
        ['Gestor Meio Ambiente',           'meioambiente@saogoncalo.mg.gov.br',       null, 'ug', '0210', 'responsavel_unidade_gestora'],
        ['Gestor Agricultura',             'agricultura@saogoncalo.mg.gov.br',        null, 'ug', '0211', 'responsavel_unidade_gestora'],
        ['Gestor Serviços Urbanos',        'servicosurbanos@saogoncalo.mg.gov.br',    null, 'ug', '0215', 'responsavel_unidade_gestora'],
        ['Gestor Cultura e Turismo',       'cultura@saogoncalo.mg.gov.br',            null, 'ug', '0216', 'responsavel_unidade_gestora'],
        ['Gestor Desen. Econômico',        'desenvolvimento@saogoncalo.mg.gov.br',    null, 'ug', '0217', 'responsavel_unidade_gestora'],
        ['Gestor Gestão de Pessoa',        'gestaodepessoas@saogoncalo.mg.gov.br',    null, 'ug', '0224', 'responsavel_unidade_gestora'],
        ['Gestor Transporte',              'transportes@saogoncalo.mg.gov.br',        null, 'ug', '0225', 'responsavel_unidade_gestora'],
        ['Gestor Ciência e Tecnologia',    'cienciaetecnologia@saogoncalo.mg.gov.br', null, 'ug', '0227', 'responsavel_unidade_gestora'],

        // Três Secretarias cujo e-mail institucional ainda não foi informado:
        // entram por nome de usuário, com endereço reservado que não existe de
        // propósito — ninguém escreve para @provisorio.local por engano.
        ['Gestor Fazenda',                 'fazenda@provisorio.local',  'fazenda',  'ug', '0204', 'responsavel_unidade_gestora'],
        ['Gestor Saúde',                   'saude@provisorio.local',    'saude',    'ug', '0206', 'responsavel_unidade_gestora'],
        ['Gestor Trabalho e Desen. Social','trabalho@provisorio.local', 'trabalho', 'ug', '0209', 'responsavel_unidade_gestora'],
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

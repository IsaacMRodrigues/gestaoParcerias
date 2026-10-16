<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * As contas que o sistema criava prontas (cria_quadro_de_usuarios_da_prefeitura) saem: cada servidor faz
 * o próprio cadastro e a SCP aprova. Sem nenhum registro, a conta é apagada; com registro (assinou,
 * enviou, aprovou algo), é desativada para guardar a autoria, perde os perfis e libera o e-mail e o
 * nome de usuário, para o secretário se cadastrar com o endereço institucional.
 */
class ContasPreCadastradas
{
    /** As do quadro antigo, menos o administrador e a SCP, e a da Saúde criada depois à mão. */
    public const EMAILS = [
        'prefeito@gmail.com', 'seplan@gmail.com', 'planejamento@saogoncalo.mg.gov.br', 'procurador@saogoncalo.mg.gov.br',
        'governo@saogoncalo.mg.gov.br', 'juridico@saogoncalo.mg.gov.br', 'administracao@saogoncalo.mg.gov.br',
        'obras@saogoncalo.mg.gov.br', 'educacao@saogoncalo.mg.gov.br', 'esportes@saogoncalo.mg.gov.br',
        'meioambiente@saogoncalo.mg.gov.br', 'agricultura@saogoncalo.mg.gov.br', 'servicosurbanos@saogoncalo.mg.gov.br',
        'cultura@saogoncalo.mg.gov.br', 'desenvolvimento@saogoncalo.mg.gov.br', 'gestaodepessoas@saogoncalo.mg.gov.br',
        'transportes@saogoncalo.mg.gov.br', 'cienciaetecnologia@saogoncalo.mg.gov.br',
        'fazenda@provisorio.local', 'saude@provisorio.local', 'trabalho@provisorio.local',
        'gestor.saude@saogoncalo.mg.gov.br',
    ];

    /** @return array{apagadas: list<string>, desativadas: list<string>} */
    public static function remover(array $emails = self::EMAILS): array
    {
        $feito = ['apagadas' => [], 'desativadas' => []];

        foreach (User::whereIn('email', $emails)->get() as $user) {
            DB::transaction(function () use ($user, &$feito) {
                $email = $user->email;
                // Fora dos perfis em qualquer caso: não aparece em lista de quem assina ou recebe.
                DB::table('model_has_roles')->where('model_type', User::class)->where('model_id', $user->id)->delete();
                DB::table('model_has_permissions')->where('model_type', User::class)->where('model_id', $user->id)->delete();

                if ($user->motivosParaNaoExcluir() === []) {
                    DB::table('users')->where('id', $user->id)->delete();
                    $feito['apagadas'][] = $email;

                    return;
                }

                [$local, $dominio] = explode('@', $email, 2);
                DB::table('users')->where('id', $user->id)->update([
                    'status'     => false,
                    'email'      => $local . '+desativada' . $user->id . '@' . $dominio,
                    'login'      => null,
                    'updated_at' => now(),
                ]);
                $feito['desativadas'][] = $email;
            });
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return $feito;
    }
}

<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * As contas que o sistema criava prontas (cria_quadro_de_usuarios_da_prefeitura) saem: cada servidor faz
 * o próprio cadastro e a SCP aprova. Tudo o que fizeram foi teste, então são apagadas mesmo com registro:
 * a autoria fica em branco e o e-mail institucional fica livre para o secretário se cadastrar.
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

    /** @return list<string> os e-mails apagados */
    public static function remover(array $emails = self::EMAILS): array
    {
        $apagadas = [];

        foreach (User::whereIn('email', $emails)->get() as $user) {
            DB::transaction(function () use ($user) {
                DB::table('model_has_roles')->where('model_type', User::class)->where('model_id', $user->id)->delete();
                DB::table('model_has_permissions')->where('model_type', User::class)->where('model_id', $user->id)->delete();
                DB::table('users')->where('id', $user->id)->delete();
            });
            $apagadas[] = $user->email;
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return $apagadas;
    }
}

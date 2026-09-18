<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * O carimbo de assinatura passa a guardar quem assinou.
 *
 * Até aqui o carimbo lia o nome e o cargo do usuário **de agora**: bastava a
 * pessoa editar o próprio perfil, mudar de setor ou ganhar outro papel para
 * que todos os documentos que ela já tinha assinado passassem a dizer outra
 * coisa. Documento assinado é ato jurídico com data certa — quem assinou, e em
 * que qualidade, não muda depois.
 *
 * As assinaturas já existentes recebem o nome e o cargo atuais: é a melhor
 * aproximação disponível hoje, e daqui em diante ficam congeladas.
 */
return new class extends Migration
{
    /** As três tabelas que guardam assinatura, e a coluna de quem assinou. */
    private const TABELAS = [
        'pecas'            => 'assinado_por',
        'processo_pecas'   => 'assinado_por',
        'ordens_pagamento' => 'assinado_por',
    ];

    public function up(): void
    {
        foreach (array_keys(self::TABELAS) as $tabela) {
            Schema::table($tabela, function (Blueprint $table) {
                $table->string('assinante_nome')->nullable()->after('assinado_em');
                $table->string('assinante_cargo')->nullable()->after('assinante_nome');
            });
        }

        // O Termo de Parceria é assinado pelas duas partes.
        Schema::table('pecas', function (Blueprint $table) {
            $table->string('contra_assinante_nome')->nullable()->after('contra_assinado_em');
            $table->string('contra_assinante_cargo')->nullable()->after('contra_assinante_nome');
        });

        $identidades = $this->identidades();

        foreach (self::TABELAS as $tabela => $coluna) {
            $this->congelar($tabela, $coluna, 'assinante_nome', 'assinante_cargo', $identidades);
        }

        $this->congelar('pecas', 'contra_assinado_por', 'contra_assinante_nome', 'contra_assinante_cargo', $identidades);
    }

    public function down(): void
    {
        foreach (array_keys(self::TABELAS) as $tabela) {
            Schema::table($tabela, function (Blueprint $table) {
                $table->dropColumn(['assinante_nome', 'assinante_cargo']);
            });
        }

        Schema::table('pecas', function (Blueprint $table) {
            $table->dropColumn(['contra_assinante_nome', 'contra_assinante_cargo']);
        });
    }

    /** Nome e cargo de cada usuário, montados uma vez só. */
    private function identidades(): array
    {
        return User::with(['roles', 'orgao', 'osc'])->get()
            ->mapWithKeys(fn (User $u) => [$u->id => $u->identidadeParaAssinatura()])
            ->all();
    }

    private function congelar(string $tabela, string $coluna, string $campoNome, string $campoCargo, array $identidades): void
    {
        DB::table($tabela)
            ->whereNotNull($coluna)
            ->select('id', $coluna)
            ->orderBy('id')
            ->chunk(200, function ($linhas) use ($tabela, $coluna, $campoNome, $campoCargo, $identidades) {
                foreach ($linhas as $linha) {
                    $identidade = $identidades[$linha->{$coluna}] ?? null;
                    if (!$identidade) {
                        continue;
                    }

                    DB::table($tabela)->where('id', $linha->id)->update([
                        $campoNome  => $identidade['nome'],
                        $campoCargo => $identidade['cargo'],
                    ]);
                }
            });
    }
};

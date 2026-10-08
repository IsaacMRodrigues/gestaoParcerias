<?php

namespace App\Models;

use App\Models\Concerns\ImpedeExclusaoComVinculos;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Osc extends Model
{
    use ImpedeExclusaoComVinculos;

    public const TIPOS = [
        'associacao'  => 'Associação',
        'fundacao'    => 'Fundação',
        'cooperativa' => 'Cooperativa',
        'oscip'       => 'OSCIP',
        'os'          => 'Organização Social (OS)',
        'outro'       => 'Outro',
    ];

    protected $fillable = [
        'user_id',
        'name', 'tipo', 'cnpj', 'data_abertura', 'cnae_primario', 'cnae_secundario', 'email', 'phone',
        'cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'estado',
        'resp_nome', 'resp_cpf', 'resp_rg', 'resp_rg_orgao', 'resp_email', 'resp_phone',
        'resp_cep', 'resp_logradouro', 'resp_numero', 'resp_complemento', 'resp_bairro', 'resp_cidade', 'resp_estado',
        'anexo_cartao_cnpj', 'resp_anexo_cpf', 'resp_anexo_comprovante', 'resp_anexo_ata',
        'status',
    ];

    /** Anexos do cadastro: campo => rótulo. */
    public const ANEXOS = [
        'anexo_cartao_cnpj'      => 'Cartão CNPJ',
        'resp_anexo_cpf'         => 'CPF do representante',
        'resp_anexo_comprovante' => 'Comprovante de endereço do representante',
        'resp_anexo_ata'         => 'Ata da atual diretoria',
    ];

    protected function casts(): array
    {
        return [
            'status'        => 'boolean',
            'data_abertura' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function propostas(): HasMany
    {
        return $this->hasMany(Proposta::class);
    }

    /** Contas de acesso da organização (não confundir com membros(), a diretoria declarada). */
    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** Todas as versões da área "Arquivos da OSC". */
    public function arquivos(): HasMany
    {
        return $this->hasMany(OscArquivo::class)->orderByDesc('versao');
    }

    /** A versão atual de cada documento: ['tipo' => OscArquivo]. */
    public function arquivosAtuais(): \Illuminate\Support\Collection
    {
        $todos = $this->relationLoaded('arquivos') ? $this->arquivos : $this->arquivos()->get();

        return $todos->sortByDesc('versao')->unique('tipo')->keyBy('tipo');
    }

    /**
     * O que falta na área: documento não anexado e certidão vencida (vazio = completa). Com a parceria,
     * também a versão que a UG recusou nela, até a OSC enviar outra.
     */
    public function pendenciasDosArquivos(?Proposta $parceria = null): array
    {
        $atuais = $this->arquivosAtuais();
        $recusas = $parceria
            ? OscArquivoAnalise::where('proposta_id', $parceria->id)->where('situacao', 'recusado')->get()->keyBy('osc_arquivo_id')
            : collect();
        $pend = [];

        foreach (OscArquivo::tipos() as $tipo => $rotulo) {
            $arquivo = $atuais[$tipo] ?? null;
            // Complementar não é exigido na área: só pesa a recusa nesta parceria.
            if (OscArquivo::ehComplementar($tipo) && (!$arquivo || $arquivo->vencida())) {
                continue;
            }
            if (!$arquivo) {
                $pend[] = $rotulo . ' (não anexado)';
            } elseif ($arquivo->vencida()) {
                $pend[] = $rotulo . ' (vencida em ' . $arquivo->validade->format('d/m/Y') . ')';
            } elseif ($recusa = $recusas[$arquivo->id] ?? null) {
                $pend[] = $rotulo . ' (recusado nesta parceria' . ($recusa->motivo ? ': ' . $recusa->motivo : '') . ' — envie nova versão)';
            }
        }

        return $pend;
    }

    public function membros(): HasMany
    {
        return $this->hasMany(OscMembro::class);
    }

    protected function vinculosBloqueantes(): array
    {
        return [
            'propostas' => ['proposta', 'propostas'],
        ];
    }

    protected function fraseDeBloqueio(): string
    {
        return 'Esta OSC não pode ser excluída';
    }
}

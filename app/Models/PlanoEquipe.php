<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Item 12 do modelo de Plano de Trabalho: equipe contratada ou própria da OSC a serviço da parceria. */
class PlanoEquipe extends Model
{
    protected $table = 'plano_equipe';

    /** Natureza do vínculo, com as três opções do modelo. */
    public const VINCULOS = [
        'clt'          => 'CLT',
        'contratado'   => 'Contratado',
        'voluntariado' => 'Voluntariado',
    ];

    protected $fillable = ['proposta_id', 'manifestacao_id', 'cargo_funcao', 'formacao', 'carga_horaria_mensal', 'vinculo'];

    public function vinculoLabel(): string
    {
        return self::VINCULOS[$this->vinculo] ?? (string) $this->vinculo;
    }
}

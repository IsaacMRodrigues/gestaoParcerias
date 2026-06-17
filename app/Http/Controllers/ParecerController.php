<?php

namespace App\Http\Controllers;

use App\Http\Requests\ParecerRequest;
use App\Models\Parecer;
use App\Models\Proposta;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ParecerController extends Controller
{
    /**
     * Permissão exigida conforme o tipo de parecer.
     */
    private const PERMISSAO_POR_TIPO = [
        'tecnico'  => 'pareceres_tecnico',
        'juridico' => 'pareceres_juridico',
        'decisao'  => 'pareceres_decisao',
    ];

    private function autorizarTipo(string $tipo): void
    {
        $permissao = self::PERMISSAO_POR_TIPO[$tipo] ?? null;
        abort_unless($permissao && auth()->user()->can($permissao), 403,
            'Seu perfil não pode emitir este tipo de parecer.');
    }

    public function create(Proposta $proposta, string $tipo): View
    {
        abort_unless(array_key_exists($tipo, Parecer::TIPOS), 404);
        $this->autorizarTipo($tipo);

        $proposta->load('pareceres');

        return view('pareceres.create', compact('proposta', 'tipo'));
    }

    public function store(ParecerRequest $request, Proposta $proposta): RedirectResponse
    {
        $data = $request->validated();
        $this->autorizarTipo($data['tipo']);

        $parecer = $proposta->pareceres()->create($data);

        // Atualiza o status da proposta conforme a transição definida no model
        $novoStatus = Parecer::STATUS_TRANSITIONS[$data['tipo']][$data['resultado']] ?? null;
        if ($novoStatus) {
            $proposta->update(['status' => $novoStatus]);
        }

        // Se solicitou diligência, cria o registro
        if ($data['resultado'] === 'diligencia' && !empty($data['diligencia_descricao'])) {
            $proposta->diligencias()->create([
                'parecer_id' => $parecer->id,
                'descricao'  => $data['diligencia_descricao'],
                'prazo'      => $data['diligencia_prazo'],
                'status'     => 'pendente',
            ]);
        }

        return redirect()->route('propostas.show', $proposta)
            ->with('success', Parecer::TIPOS[$data['tipo']] . ' registrado com sucesso.');
    }
}

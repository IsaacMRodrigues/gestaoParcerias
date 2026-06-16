<?php

namespace App\Http\Controllers;

use App\Http\Requests\ParecerRequest;
use App\Models\Parecer;
use App\Models\Proposta;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ParecerController extends Controller
{
    public function create(Proposta $proposta, string $tipo): View
    {
        abort_unless(array_key_exists($tipo, Parecer::TIPOS), 404);

        $proposta->load('pareceres');

        return view('pareceres.create', compact('proposta', 'tipo'));
    }

    public function store(ParecerRequest $request, Proposta $proposta): RedirectResponse
    {
        $data = $request->validated();

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

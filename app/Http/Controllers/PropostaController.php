<?php

namespace App\Http\Controllers;

use App\Models\Proposta;
use Illuminate\View\View;

/**
 * Propostas, do lado do município: ler e analisar.
 *
 * Criar, editar, remover e submeter saíram daqui — são atos da OSC, feitos no
 * portal (ver PortalController). O que havia era um CRUD completo com a OSC
 * escolhida num dropdown: o município redigia a proposta em nome de terceiro,
 * submetia por ele e depois a aprovava, sem que nada registrasse quem de fato
 * propôs. O plano de trabalho (metas e etapas) segue editável aqui.
 */
class PropostaController extends Controller
{
    public function index(): View
    {
        $propostas = Proposta::with(['chamamento.programa', 'osc'])
            ->visiveisPara(auth()->user())
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('propostas.index', compact('propostas'));
    }

    public function show(Proposta $proposta): View
    {
        $proposta->load(['chamamento.programa', 'osc', 'metas.etapas', 'pareceres.diligencias', 'documentos.uploader']);

        return view('propostas.show', compact('proposta'));
    }

}

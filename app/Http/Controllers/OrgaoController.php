<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrgaoRequest;
use App\Models\Orgao;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrgaoController extends Controller
{
    /**
     * Secretarias e as pessoas de cada uma, na mesma tela.
     *
     * Eram duas listagens que ninguém consultava separadas: para saber quem
     * responde por uma Secretaria, abria-se Usuários e procurava-se pela coluna
     * de órgão. Agora a Secretaria traz a sua gente junto — e quem não é de
     * Secretaria nenhuma (os setores transversais) aparece num bloco à parte,
     * em vez de sumir da tela.
     */
    public function index(): View
    {
        $orgaos = Orgao::with(['usuarios.roles'])
            ->withCount('usuarios')
            ->orderBy('name')
            ->paginate(15);

        // SCP, SEPLAN, Procuradoria, Gabinete e TI atendem o Município inteiro
        // e não têm Secretaria (ver User::SETORES_TRANSVERSAIS). A OSC fica de
        // fora: ela tem tela própria, em Cadastros → OSCs.
        $semOrgao = User::whereNull('orgao_id')
            ->whereNull('osc_id')
            ->with('roles')
            ->orderByRaw("FIELD(setor,'ti','scp','seplan','pj','pm')")
            ->orderBy('name')
            ->get();

        return view('orgaos.index', [
            'orgaos'           => $orgaos,
            'semOrgao'         => $semOrgao,
            'setoresSemChefia' => UserController::setoresSemChefia(),
        ]);
    }

    public function create(): View
    {
        return view('orgaos.create');
    }

    public function store(OrgaoRequest $request): RedirectResponse
    {
        Orgao::create($request->validated());

        return redirect()->route('orgaos.index')
            ->with('success', 'Órgão/Secretaria cadastrado com sucesso.');
    }

    public function edit(Orgao $orgao): View
    {
        return view('orgaos.edit', compact('orgao'));
    }

    public function update(OrgaoRequest $request, Orgao $orgao): RedirectResponse
    {
        $orgao->update($request->validated());

        return redirect()->route('orgaos.index')
            ->with('success', 'Órgão/Secretaria atualizado com sucesso.');
    }

    public function destroy(Orgao $orgao): RedirectResponse
    {
        if ($bloqueio = $this->bloqueioDeExclusao($orgao)) {
            return $bloqueio;
        }

        $orgao->delete();

        return redirect()->route('orgaos.index')
            ->with('success', 'Órgão/Secretaria removido com sucesso.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\OscRequest;
use App\Models\Osc;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class OscController extends Controller
{
    public function index(): View
    {
        $oscs = Osc::withCount('membros')->orderBy('name')->paginate(15);

        return view('oscs.index', compact('oscs'));
    }

    public function create(): View
    {
        return view('oscs.create');
    }

    public function store(OscRequest $request): RedirectResponse
    {
        $osc = Osc::create($request->safe()->except([...array_keys(Osc::ANEXOS), 'membros']));

        $this->salvarAnexos($request, $osc);
        $this->sincronizarMembros($request, $osc);

        return redirect()->route('oscs.index')
            ->with('success', 'OSC cadastrada com sucesso.');
    }

    public function edit(Osc $osc): View
    {
        $osc->load('membros');

        return view('oscs.edit', compact('osc'));
    }

    public function update(OscRequest $request, Osc $osc): RedirectResponse
    {
        $osc->update($request->safe()->except([...array_keys(Osc::ANEXOS), 'membros']));

        $this->salvarAnexos($request, $osc);
        $this->sincronizarMembros($request, $osc);

        return redirect()->route('oscs.index')
            ->with('success', 'OSC atualizada com sucesso.');
    }

    public function destroy(Osc $osc): RedirectResponse
    {
        if ($bloqueio = $this->bloqueioDeExclusao($osc)) {
            return $bloqueio;
        }

        // Só depois de garantir que o delete vai passar: se apagasse a pasta
        // antes e o banco recusasse, os anexos sumiriam e o cadastro ficaria.
        Storage::disk('local')->deleteDirectory("oscs/{$osc->id}");
        $osc->delete();

        return redirect()->route('oscs.index')
            ->with('success', 'OSC removida com sucesso.');
    }

    /**
     * Download de um anexo do cadastro (disco privado).
     */
    public function baixarAnexo(Osc $osc, string $campo): StreamedResponse
    {
        abort_unless(array_key_exists($campo, Osc::ANEXOS) && $osc->{$campo}, 404);

        return Storage::disk('local')->download($osc->{$campo});
    }

    /**
     * Salva os anexos enviados (substitui o anterior, se houver).
     */
    private function salvarAnexos(OscRequest $request, Osc $osc): void
    {
        foreach (array_keys(Osc::ANEXOS) as $campo) {
            if ($request->hasFile($campo)) {
                if ($osc->{$campo}) {
                    Storage::disk('local')->delete($osc->{$campo});
                }
                $osc->{$campo} = $request->file($campo)->store("oscs/{$osc->id}", 'local');
            }
        }
        $osc->save();
    }

    /**
     * Recria os membros a partir do formulário (ignora linhas em branco).
     */
    private function sincronizarMembros(OscRequest $request, Osc $osc): void
    {
        $membros = collect($request->input('membros', []))
            ->filter(fn ($m) => filled($m['nome'] ?? null))
            ->map(fn ($m) => [
                'nome'  => $m['nome'],
                'cpf'   => $m['cpf'] ?? null,
                'phone' => $m['phone'] ?? null,
                'email' => $m['email'] ?? null,
                'cargo' => $m['cargo'] ?? null,
            ])
            ->values();

        $osc->membros()->delete();
        if ($membros->isNotEmpty()) {
            $osc->membros()->createMany($membros->all());
        }
    }
}

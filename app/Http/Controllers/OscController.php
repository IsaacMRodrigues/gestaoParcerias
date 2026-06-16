<?php

namespace App\Http\Controllers;

use App\Http\Requests\OscRequest;
use App\Models\Osc;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OscController extends Controller
{
    public function index(): View
    {
        $oscs = Osc::orderBy('name')->paginate(15);

        return view('oscs.index', compact('oscs'));
    }

    public function create(): View
    {
        return view('oscs.create');
    }

    public function store(OscRequest $request): RedirectResponse
    {
        Osc::create($request->validated());

        return redirect()->route('oscs.index')
            ->with('success', 'OSC cadastrada com sucesso.');
    }

    public function edit(Osc $osc): View
    {
        return view('oscs.edit', compact('osc'));
    }

    public function update(OscRequest $request, Osc $osc): RedirectResponse
    {
        $osc->update($request->validated());

        return redirect()->route('oscs.index')
            ->with('success', 'OSC atualizada com sucesso.');
    }

    public function destroy(Osc $osc): RedirectResponse
    {
        $osc->delete();

        return redirect()->route('oscs.index')
            ->with('success', 'OSC removida com sucesso.');
    }
}

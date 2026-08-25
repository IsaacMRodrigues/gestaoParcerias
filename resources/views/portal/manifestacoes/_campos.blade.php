{{-- Campos gerais da manifestação. Espera: $manifestacao (ou null), $orgaos --}}
<div>
    <x-input-label for="titulo" value="Título da proposta *" />
    <x-text-input id="titulo" name="titulo" type="text" class="mt-1 block w-full"
                  :value="old('titulo', $manifestacao?->titulo)" required />
    <x-input-error :messages="$errors->get('titulo')" class="mt-1" />
</div>

<div>
    <x-input-label for="orgao_id" value="Secretaria a que se dirige *" />
    <select name="orgao_id" id="orgao_id" required
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
        <option value="">Selecione…</option>
        @foreach($orgaos as $orgao)
            <option value="{{ $orgao->id }}" @selected(old('orgao_id', $manifestacao?->orgao_id) == $orgao->id)>
                {{ $orgao->name }}
            </option>
        @endforeach
    </select>
    <p class="mt-1 text-xs text-gray-400">É a Secretaria da área que dirá se há interesse público na parceria.</p>
    <x-input-error :messages="$errors->get('orgao_id')" class="mt-1" />
</div>

<div>
    <x-input-label for="objeto" value="Objeto *" />
    <textarea name="objeto" id="objeto" rows="3" required
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">{{ old('objeto', $manifestacao?->objeto) }}</textarea>
    <x-input-error :messages="$errors->get('objeto')" class="mt-1" />
</div>

<div>
    <x-input-label for="justificativa" value="Justificativa *" />
    <textarea name="justificativa" id="justificativa" rows="4" required
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">{{ old('justificativa', $manifestacao?->justificativa) }}</textarea>
    <p class="mt-1 text-xs text-gray-400">O interesse público da parceria: a necessidade que ela atende e por quê.</p>
    <x-input-error :messages="$errors->get('justificativa')" class="mt-1" />
</div>

<div>
    <x-input-label for="publico_alvo" value="Público atendido" />
    <textarea name="publico_alvo" id="publico_alvo" rows="2"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">{{ old('publico_alvo', $manifestacao?->publico_alvo) }}</textarea>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <x-input-label for="valor_solicitado" value="Valor solicitado (R$) *" />
        <x-text-input id="valor_solicitado" name="valor_solicitado" type="number" step="0.01" min="0"
                      class="mt-1 block w-full" :value="old('valor_solicitado', $manifestacao?->valor_solicitado)" required />
        <x-input-error :messages="$errors->get('valor_solicitado')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="valor_proprio" value="Contrapartida da OSC (R$)" />
        <x-text-input id="valor_proprio" name="valor_proprio" type="number" step="0.01" min="0"
                      class="mt-1 block w-full" :value="old('valor_proprio', $manifestacao?->valor_proprio)" />
    </div>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <x-input-label for="data_inicio_prevista" value="Início previsto" />
        <x-text-input id="data_inicio_prevista" name="data_inicio_prevista" type="date" class="mt-1 block w-full"
                      :value="old('data_inicio_prevista', $manifestacao?->data_inicio_prevista?->format('Y-m-d'))" />
    </div>
    <div>
        <x-input-label for="data_fim_prevista" value="Término previsto" />
        <x-text-input id="data_fim_prevista" name="data_fim_prevista" type="date" class="mt-1 block w-full"
                      :value="old('data_fim_prevista', $manifestacao?->data_fim_prevista?->format('Y-m-d'))" />
        <x-input-error :messages="$errors->get('data_fim_prevista')" class="mt-1" />
    </div>
</div>

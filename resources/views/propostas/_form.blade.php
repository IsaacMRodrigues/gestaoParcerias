@php $proposta = $proposta ?? null; @endphp

<div class="grid grid-cols-2 gap-4">
    <div>
        <x-input-label for="chamamento_id" value="Chamamento *" />
        <select id="chamamento_id" name="chamamento_id" required
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm">
            <option value="">Selecione...</option>
            @foreach($chamamentos as $chamamento)
                <option value="{{ $chamamento->id }}"
                    {{ old('chamamento_id', $proposta?->chamamento_id) == $chamamento->id ? 'selected' : '' }}>
                    [{{ $chamamento->programa->sigla ?? $chamamento->programa->name }}]
                    {{ $chamamento->numero ? $chamamento->numero . ' — ' : '' }}{{ $chamamento->titulo }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('chamamento_id')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="osc_id" value="OSC *" />
        <select id="osc_id" name="osc_id" required
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm">
            <option value="">Selecione...</option>
            @foreach($oscs as $osc)
                <option value="{{ $osc->id }}"
                    {{ old('osc_id', $proposta?->osc_id) == $osc->id ? 'selected' : '' }}>
                    {{ $osc->name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('osc_id')" class="mt-2" />
    </div>
</div>

<div class="mt-4">
    <x-input-label for="titulo" value="Título da Proposta *" />
    <x-text-input id="titulo" name="titulo" type="text" class="mt-1 block w-full"
                  value="{{ old('titulo', $proposta?->titulo) }}" required autofocus />
    <x-input-error :messages="$errors->get('titulo')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="objeto" value="Objeto *" />
    <textarea id="objeto" name="objeto" rows="3" required
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm">{{ old('objeto', $proposta?->objeto) }}</textarea>
    <x-input-error :messages="$errors->get('objeto')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="justificativa" value="Justificativa" />
    <textarea id="justificativa" name="justificativa" rows="3"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm">{{ old('justificativa', $proposta?->justificativa) }}</textarea>
    <x-input-error :messages="$errors->get('justificativa')" class="mt-2" />
</div>

<div class="grid grid-cols-2 gap-4 mt-4">
    <div>
        <x-input-label for="valor_solicitado" value="Valor Solicitado (R$) *" />
        <x-input-dinheiro name="valor_solicitado" :value="$proposta?->valor_solicitado" required class="mt-1" />
        <x-input-error :messages="$errors->get('valor_solicitado')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="valor_proprio" value="Contrapartida da OSC (R$)" />
        <x-input-dinheiro name="valor_proprio" :value="$proposta?->valor_proprio ?? 0" class="mt-1" />
        <x-input-error :messages="$errors->get('valor_proprio')" class="mt-2" />
    </div>
</div>

<div class="grid grid-cols-3 gap-4 mt-4">
    <div>
        <x-input-label for="data_inicio_prevista" value="Início Previsto" />
        <x-text-input id="data_inicio_prevista" name="data_inicio_prevista" type="date" class="mt-1 block w-full"
                      value="{{ old('data_inicio_prevista', $proposta?->data_inicio_prevista?->format('Y-m-d')) }}" />
    </div>
    <div>
        <x-input-label for="data_fim_prevista" value="Fim Previsto" />
        <x-text-input id="data_fim_prevista" name="data_fim_prevista" type="date" class="mt-1 block w-full"
                      value="{{ old('data_fim_prevista', $proposta?->data_fim_prevista?->format('Y-m-d')) }}" />
        <x-input-error :messages="$errors->get('data_fim_prevista')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="status" value="Status *" />
        <select id="status" name="status" required
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm">
            @foreach(\App\Models\Proposta::STATUS as $key => $label)
                <option value="{{ $key }}" {{ old('status', $proposta?->status ?? 'rascunho') === $key ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>
</div>

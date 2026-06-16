@php $aditivo = $aditivo ?? null; @endphp

<div class="grid grid-cols-3 gap-4">
    <div>
        <x-input-label for="numero" value="Número do Aditivo *" />
        <x-text-input id="numero" name="numero" type="number" min="1" class="mt-1 block w-full"
                      value="{{ old('numero', $aditivo?->numero ?? $numero ?? '') }}" required />
        <x-input-error :messages="$errors->get('numero')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="tipo" value="Tipo *" />
        <select id="tipo" name="tipo" required
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
            <option value="">Selecione...</option>
            @foreach(\App\Models\Aditivo::TIPOS as $key => $label)
                <option value="{{ $key }}" {{ old('tipo', $aditivo?->tipo) === $key ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('tipo')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="status" value="Status *" />
        <select id="status" name="status" required
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
            @foreach(\App\Models\Aditivo::STATUS as $key => $label)
                <option value="{{ $key }}" {{ old('status', $aditivo?->status ?? 'minuta') === $key ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<div class="mt-4">
    <x-input-label for="descricao" value="Descrição / Justificativa *" />
    <textarea id="descricao" name="descricao" rows="3" required
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">{{ old('descricao', $aditivo?->descricao) }}</textarea>
    <x-input-error :messages="$errors->get('descricao')" class="mt-2" />
</div>

<div class="grid grid-cols-3 gap-4 mt-4">
    <div>
        <x-input-label for="data_assinatura" value="Data de Assinatura" />
        <x-text-input id="data_assinatura" name="data_assinatura" type="date" class="mt-1 block w-full"
                      value="{{ old('data_assinatura', $aditivo?->data_assinatura?->format('Y-m-d')) }}" />
        <x-input-error :messages="$errors->get('data_assinatura')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="nova_data_fim" value="Nova Data de Fim (se prorrogação)" />
        <x-text-input id="nova_data_fim" name="nova_data_fim" type="date" class="mt-1 block w-full"
                      value="{{ old('nova_data_fim', $aditivo?->nova_data_fim?->format('Y-m-d')) }}" />
        <x-input-error :messages="$errors->get('nova_data_fim')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="valor_adicional" value="Valor Adicional (R$)" />
        <x-text-input id="valor_adicional" name="valor_adicional" type="number" step="0.01" min="0"
                      class="mt-1 block w-full"
                      value="{{ old('valor_adicional', $aditivo?->valor_adicional) }}" />
        <x-input-error :messages="$errors->get('valor_adicional')" class="mt-2" />
    </div>
</div>

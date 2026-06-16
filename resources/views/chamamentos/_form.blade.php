@php $chamamento = $chamamento ?? null; @endphp

<div class="grid grid-cols-3 gap-4">
    <div class="col-span-2">
        <x-input-label for="titulo" value="Título *" />
        <x-text-input id="titulo" name="titulo" type="text" class="mt-1 block w-full"
                      value="{{ old('titulo', $chamamento?->titulo) }}" required autofocus />
        <x-input-error :messages="$errors->get('titulo')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="numero" value="Número" />
        <x-text-input id="numero" name="numero" type="text" class="mt-1 block w-full"
                      value="{{ old('numero', $chamamento?->numero) }}" placeholder="001/2026" maxlength="50" />
        <x-input-error :messages="$errors->get('numero')" class="mt-2" />
    </div>
</div>

<div class="mt-4">
    <x-input-label for="objeto" value="Objeto *" />
    <textarea id="objeto" name="objeto" rows="3" required
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">{{ old('objeto', $chamamento?->objeto) }}</textarea>
    <x-input-error :messages="$errors->get('objeto')" class="mt-2" />
</div>

<div class="grid grid-cols-3 gap-4 mt-4">
    <div>
        <x-input-label for="tipo" value="Tipo *" />
        <select id="tipo" name="tipo" required
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
            <option value="">Selecione...</option>
            @foreach(\App\Models\Chamamento::TIPOS as $key => $label)
                <option value="{{ $key }}" {{ old('tipo', $chamamento?->tipo) === $key ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('tipo')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="valor_disponivel" value="Valor Disponível (R$)" />
        <x-text-input id="valor_disponivel" name="valor_disponivel" type="number" step="0.01" min="0"
                      class="mt-1 block w-full" value="{{ old('valor_disponivel', $chamamento?->valor_disponivel) }}" />
        <x-input-error :messages="$errors->get('valor_disponivel')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="status" value="Status *" />
        <select id="status" name="status" required
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
            @foreach(\App\Models\Chamamento::STATUS as $key => $label)
                <option value="{{ $key }}" {{ old('status', $chamamento?->status ?? 'rascunho') === $key ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('status')" class="mt-2" />
    </div>
</div>

<div class="border-t pt-4 mt-4">
    <p class="text-sm font-medium text-gray-700 mb-3">Datas</p>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <x-input-label for="data_publicacao" value="Publicação" />
            <x-text-input id="data_publicacao" name="data_publicacao" type="date" class="mt-1 block w-full"
                          value="{{ old('data_publicacao', $chamamento?->data_publicacao?->format('Y-m-d')) }}" />
        </div>
        <div>
            <x-input-label for="data_resultado" value="Resultado" />
            <x-text-input id="data_resultado" name="data_resultado" type="date" class="mt-1 block w-full"
                          value="{{ old('data_resultado', $chamamento?->data_resultado?->format('Y-m-d')) }}" />
        </div>
        <div>
            <x-input-label for="data_inicio_inscricao" value="Início das Inscrições" />
            <x-text-input id="data_inicio_inscricao" name="data_inicio_inscricao" type="date" class="mt-1 block w-full"
                          value="{{ old('data_inicio_inscricao', $chamamento?->data_inicio_inscricao?->format('Y-m-d')) }}" />
            <x-input-error :messages="$errors->get('data_inicio_inscricao')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="data_fim_inscricao" value="Fim das Inscrições" />
            <x-text-input id="data_fim_inscricao" name="data_fim_inscricao" type="date" class="mt-1 block w-full"
                          value="{{ old('data_fim_inscricao', $chamamento?->data_fim_inscricao?->format('Y-m-d')) }}" />
            <x-input-error :messages="$errors->get('data_fim_inscricao')" class="mt-2" />
        </div>
    </div>
</div>

<div class="mt-4">
    <x-input-label for="requisitos" value="Requisitos para Participação" />
    <textarea id="requisitos" name="requisitos" rows="4"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">{{ old('requisitos', $chamamento?->requisitos) }}</textarea>
    <x-input-error :messages="$errors->get('requisitos')" class="mt-2" />
</div>

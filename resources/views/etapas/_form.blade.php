@php $etapa = $etapa ?? null; @endphp

<div class="grid grid-cols-4 gap-4">
    <div>
        <x-input-label for="numero" value="Nº da Etapa *" />
        <x-text-input id="numero" name="numero" type="number" min="1" class="mt-1 block w-full"
                      value="{{ old('numero', $etapa?->numero ?? $numero) }}" required />
        <x-input-error :messages="$errors->get('numero')" class="mt-2" />
    </div>
    <div class="col-span-3">
        <x-input-label for="descricao" value="Descrição da Etapa *" />
        <x-text-input id="descricao" name="descricao" type="text" class="mt-1 block w-full"
                      value="{{ old('descricao', $etapa?->descricao) }}" required autofocus />
        <x-input-error :messages="$errors->get('descricao')" class="mt-2" />
    </div>
</div>

<div class="mt-4">
    <x-input-label for="responsavel" value="Responsável pela Etapa" />
    <x-text-input id="responsavel" name="responsavel" type="text" class="mt-1 block w-full"
                  value="{{ old('responsavel', $etapa?->responsavel) }}" />
</div>

<div class="grid grid-cols-2 gap-4 mt-4">
    <div>
        <x-input-label for="data_inicio" value="Data de Início" />
        <x-text-input id="data_inicio" name="data_inicio" type="date" class="mt-1 block w-full"
                      value="{{ old('data_inicio', $etapa?->data_inicio?->format('Y-m-d')) }}" />
    </div>
    <div>
        <x-input-label for="data_fim" value="Data de Fim" />
        <x-text-input id="data_fim" name="data_fim" type="date" class="mt-1 block w-full"
                      value="{{ old('data_fim', $etapa?->data_fim?->format('Y-m-d')) }}" />
        <x-input-error :messages="$errors->get('data_fim')" class="mt-2" />
    </div>
</div>

<div class="mt-4">
    <x-input-label for="recursos" value="Recursos Necessários" />
    <textarea id="recursos" name="recursos" rows="2"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm">{{ old('recursos', $etapa?->recursos) }}</textarea>
</div>

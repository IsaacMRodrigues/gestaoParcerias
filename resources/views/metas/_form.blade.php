@php $meta = $meta ?? null; @endphp

<div class="grid grid-cols-4 gap-4">
    <div>
        <x-input-label for="numero" value="Nº da Meta *" />
        <x-text-input id="numero" name="numero" type="number" min="1" class="mt-1 block w-full"
                      value="{{ old('numero', $meta?->numero ?? $numero) }}" required />
        <x-input-error :messages="$errors->get('numero')" class="mt-2" />
    </div>
    <div class="col-span-3">
        <x-input-label for="descricao" value="Descrição da Meta *" />
        <x-text-input id="descricao" name="descricao" type="text" class="mt-1 block w-full"
                      value="{{ old('descricao', $meta?->descricao) }}" required autofocus />
        <x-input-error :messages="$errors->get('descricao')" class="mt-2" />
    </div>
</div>

<div class="mt-4">
    <x-input-label for="atividades" value="Atividades" />
    <x-text-input id="atividades" name="atividades" type="text" class="mt-1 block w-full"
                  value="{{ old('atividades', $meta?->atividades) }}" />
</div>

<div class="grid grid-cols-2 gap-4 mt-4">
    <div>
        <x-input-label for="indicador" value="Indicador de Verificação" />
        <x-text-input id="indicador" name="indicador" type="text" class="mt-1 block w-full"
                      value="{{ old('indicador', $meta?->indicador) }}" />
    </div>
    <div>
        <x-input-label for="meios_verificacao" value="Documentos e Meios de Verificação" />
        <x-text-input id="meios_verificacao" name="meios_verificacao" type="text" class="mt-1 block w-full"
                      value="{{ old('meios_verificacao', $meta?->meios_verificacao) }}" />
    </div>
    <div>
        <x-input-label for="resultados_esperados" value="Resultados Esperados" />
        <x-text-input id="resultados_esperados" name="resultados_esperados" type="text" class="mt-1 block w-full"
                      value="{{ old('resultados_esperados', $meta?->resultados_esperados) }}" />
    </div>
    <div>
        <x-input-label for="valor" value="Valor da Meta (R$)" />
        <x-text-input id="valor" name="valor" type="number" step="0.01" min="0" class="mt-1 block w-full"
                      value="{{ old('valor', $meta?->valor) }}" />
    </div>
    <div class="col-span-2">
        <x-input-label for="meta_quantitativa" value="Meta Quantitativa" />
        <x-text-input id="meta_quantitativa" name="meta_quantitativa" type="text" class="mt-1 block w-full"
                      value="{{ old('meta_quantitativa', $meta?->meta_quantitativa) }}"
                      placeholder="Ex: 100 beneficiários atendidos" />
    </div>
</div>

<div class="grid grid-cols-2 gap-4 mt-4">
    <div>
        <x-input-label for="data_inicio" value="Data de Início" />
        <x-text-input id="data_inicio" name="data_inicio" type="date" class="mt-1 block w-full"
                      value="{{ old('data_inicio', $meta?->data_inicio?->format('Y-m-d')) }}" />
    </div>
    <div>
        <x-input-label for="data_fim" value="Data de Fim" />
        <x-text-input id="data_fim" name="data_fim" type="date" class="mt-1 block w-full"
                      value="{{ old('data_fim', $meta?->data_fim?->format('Y-m-d')) }}" />
        <x-input-error :messages="$errors->get('data_fim')" class="mt-2" />
    </div>
</div>

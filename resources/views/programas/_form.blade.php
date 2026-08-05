@php $programa = $programa ?? null; @endphp

<div class="grid grid-cols-2 gap-4">
    <div class="col-span-2">
        <x-input-label for="name" value="Nome do Programa *" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                      value="{{ old('name', $programa?->name) }}" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>
</div>

<div class="grid grid-cols-3 gap-4 mt-4">
    <div>
        <x-input-label for="sigla" value="Sigla" />
        <x-text-input id="sigla" name="sigla" type="text" class="mt-1 block w-full"
                      value="{{ old('sigla', $programa?->sigla) }}" maxlength="30" />
        <x-input-error :messages="$errors->get('sigla')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="tipo" value="Tipo de Instrumento *" />
        <select id="tipo" name="tipo" required
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm">
            <option value="">Selecione...</option>
            @foreach(\App\Models\Programa::TIPOS as $key => $label)
                <option value="{{ $key }}" {{ old('tipo', $programa?->tipo) === $key ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('tipo')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="orgao_id" value="Órgão / Secretaria *" />
        <select id="orgao_id" name="orgao_id" required
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm">
            <option value="">Selecione...</option>
            @foreach($orgaos as $orgao)
                <option value="{{ $orgao->id }}" {{ old('orgao_id', $programa?->orgao_id) == $orgao->id ? 'selected' : '' }}>
                    {{ $orgao->sigla ? "[{$orgao->sigla}] " : '' }}{{ $orgao->name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('orgao_id')" class="mt-2" />
    </div>
</div>

<div class="mt-4">
    <x-input-label for="objetivo" value="Objetivo" />
    <textarea id="objetivo" name="objetivo" rows="3"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm">{{ old('objetivo', $programa?->objetivo) }}</textarea>
    <x-input-error :messages="$errors->get('objetivo')" class="mt-2" />
</div>

<div class="grid grid-cols-3 gap-4 mt-4">
    <div>
        <x-input-label for="valor_total" value="Valor Total (R$)" />
        <x-text-input id="valor_total" name="valor_total" type="number" step="0.01" min="0"
                      class="mt-1 block w-full" value="{{ old('valor_total', $programa?->valor_total) }}" />
        <x-input-error :messages="$errors->get('valor_total')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="data_inicio" value="Data de Início" />
        <x-text-input id="data_inicio" name="data_inicio" type="date" class="mt-1 block w-full"
                      value="{{ old('data_inicio', $programa?->data_inicio?->format('Y-m-d')) }}" />
        <x-input-error :messages="$errors->get('data_inicio')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="data_fim" value="Data de Fim" />
        <x-text-input id="data_fim" name="data_fim" type="date" class="mt-1 block w-full"
                      value="{{ old('data_fim', $programa?->data_fim?->format('Y-m-d')) }}" />
        <x-input-error :messages="$errors->get('data_fim')" class="mt-2" />
    </div>
</div>

<div class="mt-4">
    <x-input-label for="status" value="Status *" />
    <select id="status" name="status" required
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm">
        @foreach(\App\Models\Programa::STATUS as $key => $label)
            <option value="{{ $key }}" {{ old('status', $programa?->status ?? 'ativo') === $key ? 'selected' : '' }}>
                {{ $label }}
            </option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('status')" class="mt-2" />
</div>

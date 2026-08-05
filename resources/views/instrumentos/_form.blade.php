@php $instrumento = $instrumento ?? null; @endphp

<div class="grid grid-cols-3 gap-4">
    <div>
        <x-input-label for="numero" value="Número do Instrumento *" />
        <x-text-input id="numero" name="numero" type="text" class="mt-1 block w-full"
                      value="{{ old('numero', $instrumento?->numero) }}"
                      placeholder="001/2026" required autofocus />
        <x-input-error :messages="$errors->get('numero')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="tipo" value="Tipo *" />
        <select id="tipo" name="tipo" required
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm">
            <option value="">Selecione...</option>
            @foreach(\App\Models\Instrumento::TIPOS as $key => $label)
                <option value="{{ $key }}" {{ old('tipo', $instrumento?->tipo) === $key ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('tipo')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="status" value="Status *" />
        <select id="status" name="status" required
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm">
            @foreach(\App\Models\Instrumento::STATUS as $key => $label)
                <option value="{{ $key }}" {{ old('status', $instrumento?->status ?? 'minuta') === $key ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<div class="mt-4">
    <x-input-label for="objeto" value="Objeto *" />
    <textarea id="objeto" name="objeto" rows="3" required
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm">{{ old('objeto', $instrumento?->objeto) }}</textarea>
    <x-input-error :messages="$errors->get('objeto')" class="mt-2" />
</div>

<div class="grid grid-cols-2 gap-4 mt-4">
    <div>
        <x-input-label for="valor_repasse" value="Valor de Repasse (R$) *" />
        <x-text-input id="valor_repasse" name="valor_repasse" type="number" step="0.01" min="0"
                      class="mt-1 block w-full"
                      value="{{ old('valor_repasse', $instrumento?->valor_repasse) }}" required />
        <x-input-error :messages="$errors->get('valor_repasse')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="valor_proprio" value="Contrapartida da OSC (R$)" />
        <x-text-input id="valor_proprio" name="valor_proprio" type="number" step="0.01" min="0"
                      class="mt-1 block w-full"
                      value="{{ old('valor_proprio', $instrumento?->valor_proprio ?? 0) }}" />
    </div>
</div>

<div class="grid grid-cols-3 gap-4 mt-4">
    <div>
        <x-input-label for="data_assinatura" value="Data de Assinatura" />
        <x-text-input id="data_assinatura" name="data_assinatura" type="date" class="mt-1 block w-full"
                      value="{{ old('data_assinatura', $instrumento?->data_assinatura?->format('Y-m-d')) }}" />
    </div>
    <div>
        <x-input-label for="data_inicio" value="Início da Vigência *" />
        <x-text-input id="data_inicio" name="data_inicio" type="date" class="mt-1 block w-full"
                      value="{{ old('data_inicio', $instrumento?->data_inicio?->format('Y-m-d')) }}" required />
        <x-input-error :messages="$errors->get('data_inicio')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="data_fim" value="Fim da Vigência *" />
        <x-text-input id="data_fim" name="data_fim" type="date" class="mt-1 block w-full"
                      value="{{ old('data_fim', $instrumento?->data_fim?->format('Y-m-d')) }}" required />
        <x-input-error :messages="$errors->get('data_fim')" class="mt-2" />
    </div>
</div>

<div class="border-t pt-4 mt-4" x-data="{ pub: {{ old('publicado_doe', $instrumento?->publicado_doe ? 'true' : 'false') }} }">
    <p class="text-sm font-medium text-gray-700 mb-3">Publicação no Diário Oficial</p>
    <div class="flex items-center gap-3 mb-4">
        <input id="publicado_doe" name="publicado_doe" type="checkbox" value="1"
               x-model="pub"
               class="rounded border-gray-300 text-brand-600 shadow-sm focus:ring-brand-500"
               {{ old('publicado_doe', $instrumento?->publicado_doe) ? 'checked' : '' }}>
        <x-input-label for="publicado_doe" value="Publicado no Diário Oficial" />
    </div>
    <div class="grid grid-cols-2 gap-4" x-show="pub">
        <div>
            <x-input-label for="data_publicacao_doe" value="Data da Publicação" />
            <x-text-input id="data_publicacao_doe" name="data_publicacao_doe" type="date"
                          class="mt-1 block w-full"
                          value="{{ old('data_publicacao_doe', $instrumento?->data_publicacao_doe?->format('Y-m-d')) }}" />
        </div>
        <div>
            <x-input-label for="numero_doe" value="Número / Edição do DOE" />
            <x-text-input id="numero_doe" name="numero_doe" type="text" class="mt-1 block w-full"
                          value="{{ old('numero_doe', $instrumento?->numero_doe) }}" />
        </div>
    </div>
</div>

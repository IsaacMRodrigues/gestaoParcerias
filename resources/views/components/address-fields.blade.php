{{-- Bloco de endereço reutilizável. Recebe $model com os valores atuais. --}}
@props(['model' => null])

<div class="border-t pt-4 mt-2">
    <p class="text-sm font-medium text-gray-700 mb-3">Endereço</p>
    <div class="grid grid-cols-3 gap-4">
        <div>
            <x-input-label for="cep" value="CEP" />
            <x-text-input id="cep" name="cep" type="text" class="mt-1 block w-full"
                          value="{{ old('cep', $model?->cep) }}" placeholder="00000-000" maxlength="9" />
            <x-input-error :messages="$errors->get('cep')" class="mt-2" />
        </div>
        <div class="col-span-2">
            <x-input-label for="logradouro" value="Logradouro" />
            <x-text-input id="logradouro" name="logradouro" type="text" class="mt-1 block w-full"
                          value="{{ old('logradouro', $model?->logradouro) }}" />
            <x-input-error :messages="$errors->get('logradouro')" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-4 gap-4 mt-4">
        <div>
            <x-input-label for="numero" value="Número" />
            <x-text-input id="numero" name="numero" type="text" class="mt-1 block w-full"
                          value="{{ old('numero', $model?->numero) }}" maxlength="20" />
            <x-input-error :messages="$errors->get('numero')" class="mt-2" />
        </div>
        <div class="col-span-2">
            <x-input-label for="complemento" value="Complemento" />
            <x-text-input id="complemento" name="complemento" type="text" class="mt-1 block w-full"
                          value="{{ old('complemento', $model?->complemento) }}" maxlength="100" />
        </div>
        <div>
            <x-input-label for="bairro" value="Bairro" />
            <x-text-input id="bairro" name="bairro" type="text" class="mt-1 block w-full"
                          value="{{ old('bairro', $model?->bairro) }}" />
        </div>
    </div>

    <div class="grid grid-cols-3 gap-4 mt-4">
        <div class="col-span-2">
            <x-input-label for="cidade" value="Cidade" />
            <x-text-input id="cidade" name="cidade" type="text" class="mt-1 block w-full"
                          value="{{ old('cidade', $model?->cidade) }}" />
        </div>
        <div>
            <x-input-label for="estado" value="UF" />
            <x-text-input id="estado" name="estado" type="text" class="mt-1 block w-full"
                          value="{{ old('estado', $model?->estado) }}" maxlength="2" placeholder="MG" />
            <x-input-error :messages="$errors->get('estado')" class="mt-2" />
        </div>
    </div>
</div>

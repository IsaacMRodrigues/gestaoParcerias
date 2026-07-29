{{-- Bloco de endereço reutilizável. Recebe $model com os valores atuais.
     $prefix permite reaproveitar o bloco para outro endereço (ex.: 'resp_'),
     mantendo nomes de campo distintos. $title personaliza o cabeçalho. --}}
@props(['model' => null, 'prefix' => '', 'title' => 'Endereço'])

@php
    $f = fn ($campo) => $prefix . $campo;               // nome do input (ex.: resp_cep)
    $v = fn ($campo) => old($prefix . $campo, $model?->{$prefix . $campo}); // valor atual
@endphp

<div class="border-t pt-4 mt-2">
    <p class="text-sm font-medium text-gray-700 mb-3">{{ $title }}</p>
    <div class="grid grid-cols-3 gap-4">
        <div>
            <x-input-label :for="$f('cep')" value="CEP" />
            <x-text-input :id="$f('cep')" :name="$f('cep')" type="text" class="mt-1 block w-full"
                          :value="$v('cep')" placeholder="00000-000" maxlength="9" />
            <x-input-error :messages="$errors->get($f('cep'))" class="mt-2" />
        </div>
        <div class="col-span-2">
            <x-input-label :for="$f('logradouro')" value="Logradouro" />
            <x-text-input :id="$f('logradouro')" :name="$f('logradouro')" type="text" class="mt-1 block w-full"
                          :value="$v('logradouro')" />
            <x-input-error :messages="$errors->get($f('logradouro'))" class="mt-2" />
        </div>
    </div>

    <div class="grid grid-cols-4 gap-4 mt-4">
        <div>
            <x-input-label :for="$f('numero')" value="Número" />
            <x-text-input :id="$f('numero')" :name="$f('numero')" type="text" class="mt-1 block w-full"
                          :value="$v('numero')" maxlength="20" />
            <x-input-error :messages="$errors->get($f('numero'))" class="mt-2" />
        </div>
        <div class="col-span-2">
            <x-input-label :for="$f('complemento')" value="Complemento" />
            <x-text-input :id="$f('complemento')" :name="$f('complemento')" type="text" class="mt-1 block w-full"
                          :value="$v('complemento')" maxlength="100" />
        </div>
        <div>
            <x-input-label :for="$f('bairro')" value="Bairro" />
            <x-text-input :id="$f('bairro')" :name="$f('bairro')" type="text" class="mt-1 block w-full"
                          :value="$v('bairro')" />
        </div>
    </div>

    <div class="grid grid-cols-3 gap-4 mt-4">
        <div class="col-span-2">
            <x-input-label :for="$f('cidade')" value="Cidade" />
            <x-text-input :id="$f('cidade')" :name="$f('cidade')" type="text" class="mt-1 block w-full"
                          :value="$v('cidade')" />
        </div>
        <div>
            <x-input-label :for="$f('estado')" value="UF" />
            <x-text-input :id="$f('estado')" :name="$f('estado')" type="text" class="mt-1 block w-full"
                          :value="$v('estado')" maxlength="2" placeholder="MG" />
            <x-input-error :messages="$errors->get($f('estado'))" class="mt-2" />
        </div>
    </div>
</div>

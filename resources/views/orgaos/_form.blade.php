@php $orgao = $orgao ?? null; @endphp

<div class="grid grid-cols-4 gap-4">
    <div>
        <x-input-label for="codigo" value="Código UG" />
        <x-text-input id="codigo" name="codigo" type="text" class="mt-1 block w-full"
                      value="{{ old('codigo', $orgao?->codigo) }}" placeholder="0206" maxlength="4" />
        <x-input-error :messages="$errors->get('codigo')" class="mt-2" />
    </div>
    <div class="col-span-2">
        <x-input-label for="name" value="Nome *" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                      value="{{ old('name', $orgao?->name) }}" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="sigla" value="Sigla" />
        <x-text-input id="sigla" name="sigla" type="text" class="mt-1 block w-full"
                      value="{{ old('sigla', $orgao?->sigla) }}" maxlength="20" />
        <x-input-error :messages="$errors->get('sigla')" class="mt-2" />
    </div>
</div>

<div class="grid grid-cols-3 gap-4 mt-4">
    <div>
        <x-input-label for="cnpj" value="CNPJ" />
        <x-text-input id="cnpj" name="cnpj" type="text" class="mt-1 block w-full"
                      value="{{ old('cnpj', $orgao?->cnpj) }}" placeholder="00.000.000/0000-00" maxlength="18" />
        <x-input-error :messages="$errors->get('cnpj')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="email" value="E-mail" />
        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                      value="{{ old('email', $orgao?->email) }}" />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="phone" value="Telefone" />
        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full"
                      value="{{ old('phone', $orgao?->phone) }}" placeholder="(00) 0000-0000" maxlength="20" />
    </div>
</div>

<x-address-fields :model="$orgao" />

<div class="flex items-center gap-3 mt-4">
    <input id="status" name="status" type="checkbox" value="1"
           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
           {{ old('status', $orgao ? ($orgao->status ? '1' : '0') : '1') === '1' ? 'checked' : '' }}>
    <x-input-label for="status" value="Órgão ativo" />
</div>

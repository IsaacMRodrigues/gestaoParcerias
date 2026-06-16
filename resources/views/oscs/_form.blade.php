@php $osc = $osc ?? null; @endphp

{{-- Dados da OSC --}}
<div class="grid grid-cols-2 gap-4">
    <div>
        <x-input-label for="name" value="Nome da OSC *" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                      value="{{ old('name', $osc?->name) }}" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="tipo" value="Tipo" />
        <select id="tipo" name="tipo"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
            <option value="">Selecione...</option>
            @foreach(\App\Models\Osc::TIPOS as $key => $label)
                <option value="{{ $key }}" {{ old('tipo', $osc?->tipo) === $key ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('tipo')" class="mt-2" />
    </div>
</div>

<div class="grid grid-cols-3 gap-4 mt-4">
    <div>
        <x-input-label for="cnpj" value="CNPJ *" />
        <x-text-input id="cnpj" name="cnpj" type="text" class="mt-1 block w-full"
                      value="{{ old('cnpj', $osc?->cnpj) }}" placeholder="00.000.000/0000-00" maxlength="18" required />
        <x-input-error :messages="$errors->get('cnpj')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="email" value="E-mail" />
        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                      value="{{ old('email', $osc?->email) }}" />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="phone" value="Telefone" />
        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full"
                      value="{{ old('phone', $osc?->phone) }}" placeholder="(00) 00000-0000" maxlength="20" />
    </div>
</div>

<x-address-fields :model="$osc" />

{{-- Responsável Legal --}}
<div class="border-t pt-4 mt-2">
    <p class="text-sm font-medium text-gray-700 mb-3">Responsável Legal</p>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <x-input-label for="resp_nome" value="Nome" />
            <x-text-input id="resp_nome" name="resp_nome" type="text" class="mt-1 block w-full"
                          value="{{ old('resp_nome', $osc?->resp_nome) }}" />
            <x-input-error :messages="$errors->get('resp_nome')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="resp_cpf" value="CPF" />
            <x-text-input id="resp_cpf" name="resp_cpf" type="text" class="mt-1 block w-full"
                          value="{{ old('resp_cpf', $osc?->resp_cpf) }}" placeholder="000.000.000-00" maxlength="14" />
            <x-input-error :messages="$errors->get('resp_cpf')" class="mt-2" />
        </div>
    </div>
    <div class="grid grid-cols-2 gap-4 mt-4">
        <div>
            <x-input-label for="resp_email" value="E-mail" />
            <x-text-input id="resp_email" name="resp_email" type="email" class="mt-1 block w-full"
                          value="{{ old('resp_email', $osc?->resp_email) }}" />
            <x-input-error :messages="$errors->get('resp_email')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="resp_phone" value="Telefone" />
            <x-text-input id="resp_phone" name="resp_phone" type="text" class="mt-1 block w-full"
                          value="{{ old('resp_phone', $osc?->resp_phone) }}" placeholder="(00) 00000-0000" maxlength="20" />
        </div>
    </div>
</div>

<div class="flex items-center gap-3 mt-4">
    <input id="status" name="status" type="checkbox" value="1"
           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
           {{ old('status', $osc ? ($osc->status ? '1' : '0') : '1') === '1' ? 'checked' : '' }}>
    <x-input-label for="status" value="OSC ativa" />
</div>

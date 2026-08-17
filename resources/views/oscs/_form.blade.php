@php
    $osc = $osc ?? null;
    // Membros para o repeater: repopula do old() em caso de erro, senão do banco.
    $membrosIniciais = old('membros', ($osc?->membros ?? collect())->map(fn ($m) => [
        'nome'  => $m->nome,
        'cpf'   => $m->cpf,
        'phone' => $m->phone,
        'email' => $m->email,
        'cargo' => $m->cargo,
    ])->values()->all());
@endphp

{{-- 1. Dados básicos --}}
<div class="grid grid-cols-2 gap-4">
    <div>
        <x-input-label for="name" value="Razão social / Nome da OSC *" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                      value="{{ old('name', $osc?->name) }}" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="tipo" value="Tipo" />
        <select id="tipo" name="tipo"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm">
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

<div class="grid grid-cols-2 gap-4 mt-4">
    <div>
        <x-input-label for="cnpj" value="CNPJ *" />
        <x-text-input id="cnpj" name="cnpj" type="text" class="mt-1 block w-full"
                      value="{{ old('cnpj', $osc?->cnpj) }}" placeholder="00.000.000/0000-00" maxlength="18" required />
        <x-input-error :messages="$errors->get('cnpj')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="data_abertura" value="Data de abertura do CNPJ" />
        <x-text-input id="data_abertura" name="data_abertura" type="date" class="mt-1 block w-full"
                      value="{{ old('data_abertura', $osc?->data_abertura?->format('Y-m-d')) }}" />
        <x-input-error :messages="$errors->get('data_abertura')" class="mt-2" />
    </div>
</div>

<div class="grid grid-cols-2 gap-4 mt-4">
    <div>
        <x-input-label for="cnae_primario" value="CNAE primário" />
        <x-text-input id="cnae_primario" name="cnae_primario" type="text" class="mt-1 block w-full"
                      value="{{ old('cnae_primario', $osc?->cnae_primario) }}" placeholder="0000-0/00 — descrição" />
        <x-input-error :messages="$errors->get('cnae_primario')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="cnae_secundario" value="CNAE secundário" />
        <x-text-input id="cnae_secundario" name="cnae_secundario" type="text" class="mt-1 block w-full"
                      value="{{ old('cnae_secundario', $osc?->cnae_secundario) }}" placeholder="0000-0/00 — descrição" />
        <x-input-error :messages="$errors->get('cnae_secundario')" class="mt-2" />
    </div>
</div>

<div class="grid grid-cols-2 gap-4 mt-4">
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

<x-address-fields :model="$osc" title="Endereço da OSC" />

{{-- Anexo: Cartão CNPJ --}}
<x-osc-anexo :osc="$osc" campo="anexo_cartao_cnpj" label="Cartão CNPJ" />

{{-- 2. Responsável Legal --}}
<div class="border-t pt-4 mt-2">
    <p class="text-sm font-medium text-gray-700 mb-3">Responsável Legal</p>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <x-input-label for="resp_nome" value="Nome completo" />
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

    <x-address-fields :model="$osc" prefix="resp_" title="Endereço do representante" />

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-2">
        <x-osc-anexo :osc="$osc" campo="resp_anexo_cpf" label="CPF do representante" />
        <x-osc-anexo :osc="$osc" campo="resp_anexo_comprovante" label="Comprovante de endereço" />
        <x-osc-anexo :osc="$osc" campo="resp_anexo_ata" label="Ata da atual diretoria" />
    </div>
</div>

{{-- 3. Membros --}}
<div class="border-t pt-4 mt-2" x-data="{ membros: @js($membrosIniciais) }">
    <div class="flex items-center justify-between mb-3">
        <div>
            <p class="text-sm font-medium text-gray-700">Membros / Diretoria</p>
            <p class="text-xs text-gray-400">CPF, nome, contato e cargo de cada membro do quadro.</p>
        </div>
        <button type="button" @click="membros.push({nome:'',cpf:'',phone:'',email:'',cargo:''})"
                class="btn btn-outline btn-sm">
            + Adicionar membro
        </button>
    </div>

    <p x-show="membros.length === 0" class="text-sm text-gray-400 py-2">Nenhum membro cadastrado.</p>

    <template x-for="(m, i) in membros" :key="i">
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end border border-gray-100 rounded-md p-3 mb-2">
            <div class="sm:col-span-4">
                <label class="text-xs font-medium text-gray-500">Nome</label>
                <input type="text" x-model="m.nome" :name="`membros[${i}][nome]`"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
            </div>
            <div class="sm:col-span-3">
                <label class="text-xs font-medium text-gray-500">CPF</label>
                <input type="text" x-model="m.cpf" :name="`membros[${i}][cpf]`" maxlength="14" placeholder="000.000.000-00"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
            </div>
            <div class="sm:col-span-2">
                <label class="text-xs font-medium text-gray-500">Cargo/Função</label>
                <input type="text" x-model="m.cargo" :name="`membros[${i}][cargo]`"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
            </div>
            <div class="sm:col-span-2">
                <label class="text-xs font-medium text-gray-500">Telefone</label>
                <input type="text" x-model="m.phone" :name="`membros[${i}][phone]`" maxlength="20"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
            </div>
            <div class="sm:col-span-11">
                <label class="text-xs font-medium text-gray-500">E-mail</label>
                <input type="email" x-model="m.email" :name="`membros[${i}][email]`"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
            </div>
            <div class="sm:col-span-1 flex justify-end">
                <button type="button" @click="membros.splice(i, 1)"
                        class="text-xs text-red-500 hover:text-red-700 py-2">Remover</button>
            </div>
        </div>
    </template>
</div>

{{-- Status --}}
<div class="flex items-center gap-3 mt-4">
    <input id="status" name="status" type="checkbox" value="1"
           class="rounded border-gray-300 text-brand-600 shadow-sm focus:ring-brand-500"
           {{ old('status', $osc ? ($osc->status ? '1' : '0') : '1') === '1' ? 'checked' : '' }}>
    <x-input-label for="status" value="OSC ativa" />
</div>

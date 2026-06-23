{{-- Variável $user disponível apenas no edit --}}
@php $user = $user ?? null; @endphp

{{-- Nome --}}
<div>
    <x-input-label for="name" value="Nome completo *" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                  value="{{ old('name', $user?->name) }}" required autofocus />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

{{-- E-mail --}}
<div>
    <x-input-label for="email" value="E-mail *" />
    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                  value="{{ old('email', $user?->email) }}" required />
    <x-input-error :messages="$errors->get('email')" class="mt-2" />
</div>

{{-- CPF e Telefone --}}
<div class="grid grid-cols-2 gap-4">
    <div>
        <x-input-label for="cpf" value="CPF" />
        <x-text-input id="cpf" name="cpf" type="text" class="mt-1 block w-full"
                      value="{{ old('cpf', $user?->cpf) }}" placeholder="000.000.000-00" maxlength="14" />
        <x-input-error :messages="$errors->get('cpf')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="phone" value="Telefone" />
        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full"
                      value="{{ old('phone', $user?->phone) }}" placeholder="(00) 00000-0000" maxlength="20" />
        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
    </div>
</div>

{{-- Setor de lotação --}}
<div>
    <x-input-label for="setor" value="Setor de lotação" />
    <select id="setor" name="setor"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
        <option value="">Nenhum</option>
        @foreach(\App\Models\User::LOTACOES as $key => $label)
            <option value="{{ $key }}" {{ old('setor', $user?->setor) === $key ? 'selected' : '' }}>
                {{ $label }}
            </option>
        @endforeach
    </select>
    <p class="text-xs text-gray-400 mt-1">
        Define a lotação do usuário (e em qual caixa de entrada ele recebe processos do trâmite).
        Perfis marcados <span class="font-medium">(exclusivo)</span> exigem o setor correspondente.
    </p>
    <x-input-error :messages="$errors->get('setor')" class="mt-2" />
</div>

{{-- Unidade Gestora (Secretaria) — preenche a UG automaticamente na abertura de processo --}}
<div>
    <x-input-label for="orgao_id" value="Unidade Gestora (Secretaria)" />
    <select id="orgao_id" name="orgao_id"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
        <option value="">Nenhuma</option>
        @foreach(\App\Models\Orgao::whereNotNull('codigo')->orderBy('codigo')->get() as $org)
            <option value="{{ $org->id }}" {{ (string) old('orgao_id', $user?->orgao_id) === (string) $org->id ? 'selected' : '' }}>
                {{ $org->codigo }} — {{ $org->name }}
            </option>
        @endforeach
    </select>
    <p class="text-xs text-gray-400 mt-1">
        Para usuários da UG: a Secretaria é preenchida automaticamente ao abrir um processo.
    </p>
    <x-input-error :messages="$errors->get('orgao_id')" class="mt-2" />
</div>

{{-- Perfis de acesso (múltiplos) --}}
<div>
    <x-input-label value="Perfis de acesso *" />
    @php $perfisAtuais = old('roles', $user?->roles->pluck('name')->all() ?? []); @endphp
    <div class="mt-1 grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1.5 border border-gray-200 rounded-md p-3 max-h-72 overflow-y-auto">
        @foreach($roles as $role)
            @php $excl = \App\Models\User::PERFIS_EXCLUSIVOS[$role->name] ?? null; @endphp
            <label class="flex items-start gap-2 text-sm text-gray-700">
                <input type="checkbox" name="roles[]" value="{{ $role->name }}"
                       class="mt-0.5 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                       {{ in_array($role->name, $perfisAtuais) ? 'checked' : '' }}>
                <span>
                    {{ \App\Models\User::$roleLabels[$role->name] ?? $role->name }}
                    @if($excl)
                        <span class="text-xs text-amber-600">(exclusivo: {{ \App\Models\User::LOTACOES[$excl] ?? $excl }})</span>
                    @endif
                </span>
            </label>
        @endforeach
    </div>
    <x-input-error :messages="$errors->get('roles')" class="mt-2" />
</div>

{{-- Senha --}}
<div class="grid grid-cols-2 gap-4">
    <div>
        <x-input-label for="password" value="{{ $user ? 'Nova senha (deixe em branco para manter)' : 'Senha *' }}" />
        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full"
                      autocomplete="new-password" />
        <x-input-error :messages="$errors->get('password')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="password_confirmation" value="Confirmar senha" />
        <x-text-input id="password_confirmation" name="password_confirmation" type="password"
                      class="mt-1 block w-full" autocomplete="new-password" />
    </div>
</div>

{{-- Status --}}
<div class="flex items-center gap-3">
    <input id="status" name="status" type="checkbox" value="1"
           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
           {{ old('status', $user ? ($user->status ? '1' : '0') : '1') === '1' ? 'checked' : '' }}>
    <x-input-label for="status" value="Usuário ativo" />
</div>

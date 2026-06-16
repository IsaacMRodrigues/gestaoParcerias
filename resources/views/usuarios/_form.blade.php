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

{{-- Perfil --}}
<div>
    <x-input-label for="role" value="Perfil de acesso *" />
    <select id="role" name="role" required
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
        <option value="">Selecione...</option>
        @foreach($roles as $role)
            <option value="{{ $role->name }}"
                {{ old('role', $user?->roles->first()?->name) === $role->name ? 'selected' : '' }}>
                {{ \App\Models\User::$roleLabels[$role->name] ?? $role->name }}
            </option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('role')" class="mt-2" />
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

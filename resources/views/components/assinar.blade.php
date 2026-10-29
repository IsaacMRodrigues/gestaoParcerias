{{-- Botão de assinar: abre ali mesmo o pedido da senha, que o servidor confere (Controller::senhaDaAssinaturaRecusada).
     Senha recusada, o formulário volta aberto com o motivo. --}}
@props(['action', 'rotulo' => 'Assinar', 'pequeno' => false])

@php
    $recusada = session('assinatura_recusada') === $action;
    $campo = 'senha-assinatura-' . substr(md5($action), 0, 8);
@endphp

<form method="POST" action="{{ $action }}" x-data="{ aberto: @js($recusada) }" {{ $attributes }}>
    @csrf @method('PATCH')

    <button type="button" x-show="!aberto" @click="aberto = true; $nextTick(() => document.getElementById('{{ $campo }}').focus())"
            class="btn btn-primary {{ $pequeno ? 'btn-sm' : '' }}" @if($recusada) x-cloak @endif>
        {{ $rotulo }}
    </button>

    <div x-show="aberto" @if(!$recusada) x-cloak @endif class="max-w-md rounded-lg border border-brand-200 bg-brand-50 p-3 space-y-2">
        <label for="{{ $campo }}" class="block text-xs font-semibold text-gray-800">Para assinar, digite a sua senha</label>
        <x-input-senha :id="$campo" name="senha_assinatura" required />
        @if(blank(auth()->user()?->cpf))
            {{-- A barra de validação traz o CPF de quem assina; a conta sem CPF o informa uma vez (fica no cadastro). --}}
            <label for="{{ $campo }}-cpf" class="block text-xs font-semibold text-gray-800">Seu CPF</label>
            <x-text-input :id="$campo . '-cpf'" name="cpf_assinatura" required inputmode="numeric" maxlength="14"
                          placeholder="000.000.000-00" class="block w-full py-2.5" />
            <p class="text-[11px] leading-snug text-gray-500">Sua conta ainda não tem CPF. Ele fica no seu cadastro e sai,
                parcialmente oculto, na barra de validação do documento.</p>
        @endif
        @if($recusada && session('error'))
            <p class="text-xs font-medium text-red-700">{{ session('error') }}</p>
        @endif
        <p class="text-[11px] leading-snug text-gray-500">
            Depois de assinado, o texto não muda mais. Ficam registrados a data e a hora oficiais de Brasília, o código
            de integridade do texto e o endereço IP desta conexão; o documento sai com a barra de validação, com o seu
            nome e o CPF parcialmente oculto.
        </p>
        <div class="flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary btn-sm">Confirmar assinatura</button>
            <button type="button" @click="aberto = false" class="btn btn-outline btn-sm">Cancelar</button>
        </div>
    </div>
</form>

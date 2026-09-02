{{-- Uma pessoa, na listagem de Cadastros. Recuada (pl-12) para se ler como
     item da Secretaria acima, e não como outra Secretaria. --}}
@php
    // No bloco "Sem Secretaria" não há o que recolher: a linha é sempre visível.
    $recolhivel = $recolhivel ?? false;
@endphp
<tr class="border-t border-gray-100" @if($recolhivel) x-show="aberto" x-cloak @endif>
    <td class="px-6 py-2.5 pl-12">
        <span class="text-gray-900">{{ $usuario->name }}</span>
        @if($usuario->login)
            <span class="ml-1.5 px-1.5 py-0.5 text-[11px] font-mono text-slate-600 bg-slate-100 ring-1 ring-slate-200 rounded">{{ $usuario->login }}</span>
        @endif
        @if($usuario->setor)
            <span class="ml-1.5 text-[11px] text-gray-400">{{ $usuario->setorLabel() }}</span>
        @endif
    </td>
    <td class="px-6 py-2.5 text-gray-600">{{ $usuario->email }}</td>
    <td class="px-6 py-2.5">
        @php $perfil = $usuario->roles->first(); @endphp
        @if($perfil)
            <span class="px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200 rounded">
                {{ \App\Models\User::$roleLabels[$perfil->name] ?? $perfil->name }}
            </span>
            @if($usuario->roles->count() > 1)
                <span class="text-xs text-gray-400">+{{ $usuario->roles->count() - 1 }}</span>
            @endif
        @else
            {{-- Conta ativa sem perfil não vê módulo nenhum: é defeito de
                 cadastro, não estado normal. Por isso em laranja. --}}
            <span class="text-xs font-semibold text-accent-700">sem perfil</span>
        @endif
    </td>
    <td class="px-6 py-2.5">
        @if(! $usuario->isAprovado())
            <span class="px-2 py-0.5 text-xs font-semibold bg-accent-50 text-accent-800 border border-accent-200 rounded">{{ \App\Models\User::APPROVAL[$usuario->approval_status] ?? $usuario->approval_status }}</span>
        @elseif($usuario->status)
            <span class="text-xs text-brand-700">Ativo</span>
        @else
            <span class="text-xs text-gray-500">Inativo</span>
        @endif
    </td>
    <td class="px-6 py-2.5 text-right whitespace-nowrap space-x-3">
        <a href="{{ route('usuarios.edit', $usuario) }}" class="text-xs font-semibold text-brand-700 hover:text-brand-800 transition">Editar</a>
        <form action="{{ route('usuarios.destroy', $usuario) }}" method="POST" class="inline"
              data-confirm="Deseja remover {{ $usuario->name }}?">
            @csrf @method('DELETE')
            <button type="submit" class="text-xs font-medium text-gray-500 hover:text-red-700 transition">Remover</button>
        </form>
    </td>
</tr>

<x-portal-layout>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="flex items-center justify-between gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Usuários da Organização</h1>
                <p class="text-sm text-gray-500 mt-0.5">{{ $osc->name }} — CNPJ: {{ $osc->cnpj }}</p>
            </div>
            <a href="{{ route('portal.usuarios.create') }}" class="btn btn-primary shrink-0">
                Cadastrar usuário
            </a>
        </div>

        @if(session('success'))
            <div class="mb-4 bg-brand-50 border border-brand-200 text-brand-800 px-4 py-3 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
                {{ session('error') }}
            </div>
        @endif

        {{-- Diz de saída o que cada tipo de conta pode fazer. Sem isso, a
             diferença só apareceria quando o membro clicasse em "Submeter" e
             levasse um 403 sem explicação. --}}
        <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 mb-6 text-sm text-gray-600">
            <p class="font-medium text-gray-900 mb-1">Como funcionam os acessos</p>
            <p>
                Acompanhar as propostas e o andamento é de toda a equipe. O que cada pessoa pode
                <strong>fazer</strong> são as funções marcadas na linha dela — dá para mudar a qualquer momento.
                <strong>Submeter proposta</strong>, <strong>protocolar recurso</strong> e
                <strong>assinar o Termo</strong> continuam com você, responsável legal — são atos que
                vinculam juridicamente a organização.
            </p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-5 py-3 font-medium">Usuário</th>
                        <th class="px-5 py-3 font-medium">Papel</th>
                        <th class="px-5 py-3 font-medium">Funções</th>
                        <th class="px-5 py-3 font-medium">Acesso</th>
                        <th class="px-5 py-3 font-medium text-right">Ação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($usuarios as $usuario)
                        @php $ehDono = $usuario->id === $osc->user_id; @endphp
                        <tr class="{{ $usuario->status ? '' : 'bg-gray-50/70' }}">
                            <td class="px-5 py-4">
                                <p class="font-medium text-gray-900">{{ $usuario->name }}</p>
                                <p class="text-xs text-gray-500">{{ $usuario->email }}</p>
                            </td>
                            <td class="px-5 py-4">
                                @if($ehDono)
                                    <span class="px-2 py-1 text-xs font-medium bg-brand-100 text-brand-800 rounded-full">
                                        Responsável Legal
                                    </span>
                                @else
                                    <span class="px-2 py-1 text-xs font-medium bg-slate-100 text-slate-700 rounded-full">
                                        Membro
                                    </span>
                                    @if($usuario->solicitacao_obs)
                                        <p class="text-xs text-gray-500 mt-1">{{ $usuario->solicitacao_obs }}</p>
                                    @endif
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @if($ehDono)
                                    {{-- O responsável legal faz tudo o que a OSC pode:
                                         não há o que marcar nem por que desmarcar. --}}
                                    <span class="text-xs text-gray-500">Todas as funções</span>
                                @else
                                    @php $marcadas = $usuario->permissions->pluck('name')->all(); @endphp
                                    <details class="group">
                                        <summary class="cursor-pointer select-none marker:content-none
                                                        text-xs text-gray-600 hover:text-gray-900">
                                            @forelse($marcadas as $chave)
                                                <span class="inline-block px-2 py-0.5 mb-1 mr-1 bg-slate-100 text-slate-700 rounded">
                                                    {{ $funcoes[$chave]['rotulo'] ?? $chave }}
                                                </span>
                                            @empty
                                                <span class="italic text-gray-400">Só acompanha</span>
                                            @endforelse
                                            <span class="block mt-0.5 font-semibold text-brand-700 group-open:hidden">Alterar</span>
                                        </summary>
                                        <form method="POST" action="{{ route('portal.usuarios.funcoes', $usuario) }}"
                                              class="mt-2 space-y-1.5">
                                            @csrf @method('PATCH')
                                            @foreach($funcoes as $chave => $funcao)
                                                <label class="flex items-center gap-2 text-xs text-gray-700">
                                                    <input type="checkbox" name="funcoes[]" value="{{ $chave }}"
                                                           @checked(in_array($chave, $marcadas))
                                                           class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                                    {{ $funcao['rotulo'] }}
                                                </label>
                                            @endforeach
                                            <button type="submit" class="btn btn-secondary btn-sm mt-1">Salvar funções</button>
                                        </form>
                                    </details>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @if($usuario->status)
                                    <span class="inline-flex items-center gap-1.5 text-brand-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-brand-500"></span> Ativo
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 text-gray-500">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Suspenso
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                @if($ehDono)
                                    {{-- Sem ação: a conta do responsável legal é
                                         o próprio cadastro da OSC. --}}
                                    <span class="text-xs text-gray-400">—</span>
                                @else
                                    <form method="POST" action="{{ route('portal.usuarios.acesso', $usuario) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-outline btn-sm">
                                            {{ $usuario->status ? 'Suspender' : 'Reativar' }}
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($usuarios->count() === 1)
            <p class="text-sm text-gray-500 mt-4">
                Só a sua conta por enquanto. Cadastre quem trabalha nas propostas para que cada pessoa
                entre com o próprio login — assim fica registrado quem enviou cada documento.
            </p>
        @endif

    </div>
</x-portal-layout>

{{-- Secretarias e as pessoas de cada uma, na mesma tela.

     Eram duas listagens que ninguém consultava separadas: para saber quem
     responde por uma Secretaria, abria-se Usuários e procurava-se pela coluna
     de órgão. Aqui a Secretaria traz a sua gente logo abaixo, e quem não é de
     Secretaria nenhuma tem bloco próprio no fim — em vez de sumir da tela. --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Órgãos e usuários</h2>
                <p class="text-sm text-gray-500 mt-0.5">Cada Secretaria com as contas de acesso dela.</p>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                <a href="{{ route('usuarios.create') }}" class="btn btn-outline">+ Novo usuário</a>
                <a href="{{ route('orgaos.create') }}" class="btn btn-primary">+ Novo órgão</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-flash-message />

            {{-- Setor sem chefia designada: a porta de cadastrar a própria equipe
                 existe, mas ninguém lá dentro a enxerga. Quem fecha essa lacuna
                 é quem está nesta tela, e nada avisava. --}}
            @if(! empty($setoresSemChefia))
                <div class="px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-700">
                    <p class="font-medium text-gray-900 mb-1">Setores sem quem cadastre a própria equipe</p>
                    <p>
                        {{ implode(', ', $setoresSemChefia) }} —
                        ninguém nesses setores tem o perfil <strong>Chefe de Setor</strong>, então a tela
                        "Meus usuários" não aparece para eles e cada conta precisa ser criada aqui.
                        Para delegar, edite a pessoa responsável e marque esse perfil; o cadastro que ela
                        fizer continua vindo para a sua aprovação.
                    </p>
                </div>
            @endif

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">Órgão / usuário</th>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">E-mail / sigla</th>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">Perfil</th>
                            <th class="px-6 py-3.5 text-left text-[12px] font-bold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>

                    @forelse($orgaos as $orgao)
                        <tbody class="border-b border-gray-200">
                            {{-- Linha do órgão: cinza, para separar as Secretarias
                                 numa lista em que quase tudo é linha de pessoa. --}}
                            <tr class="bg-gray-50/70">
                                <td class="px-6 py-3">
                                    <span class="font-mono text-xs text-gray-500 mr-2">{{ $orgao->codigo ?? '—' }}</span>
                                    <span class="font-semibold text-gray-900">{{ $orgao->name }}</span>
                                    <span class="ml-2 text-xs text-gray-500">
                                        {{ $orgao->usuarios_count }}
                                        {{ $orgao->usuarios_count === 1 ? 'usuário' : 'usuários' }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-gray-600">
                                    {{ $orgao->email ?? $orgao->sigla ?? '—' }}
                                </td>
                                <td class="px-6 py-3 text-gray-400 text-xs">{{ $orgao->cnpj ?? '' }}</td>
                                <td class="px-6 py-3">
                                    @if($orgao->status)
                                        <span class="px-2.5 py-1 text-xs font-semibold bg-brand-50 text-brand-800 border border-brand-200 rounded-md">Ativo</span>
                                    @else
                                        <span class="px-2.5 py-1 text-xs font-semibold bg-red-50 text-red-800 border border-red-200 rounded-md">Inativo</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right whitespace-nowrap space-x-3">
                                    <a href="{{ route('orgaos.edit', $orgao) }}" class="text-xs font-semibold text-brand-700 hover:text-brand-800 transition">Editar órgão</a>
                                    <form action="{{ route('orgaos.destroy', $orgao) }}" method="POST" class="inline"
                                          data-confirm="Deseja remover o órgão &quot;{{ $orgao->name }}&quot;?">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs font-medium text-gray-500 hover:text-red-700 transition">Remover</button>
                                    </form>
                                </td>
                            </tr>

                            @forelse($orgao->usuarios as $usuario)
                                @include('orgaos._linha-usuario', ['usuario' => $usuario])
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-3 pl-12 text-xs text-gray-400">
                                        Nenhum usuário nesta Secretaria.
                                        <a href="{{ route('usuarios.create') }}" class="font-semibold text-brand-700 hover:underline">Cadastrar</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    @empty
                        <tbody>
                            <tr><td colspan="5" class="px-6 py-12"><x-empty-state icone="lista">Nenhum órgão cadastrado.</x-empty-state></td></tr>
                        </tbody>
                    @endforelse
                </table>

                @if($orgaos->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">{{ $orgaos->links() }}</div>
                @endif
            </div>

            {{-- Quem atende o Município inteiro não é de Secretaria nenhuma
                 (ver User::SETORES_TRANSVERSAIS). Sem este bloco, juntar as duas
                 listagens faria a SCP, a Procuradoria e o próprio administrador
                 desaparecerem da tela de cadastros. --}}
            @if($semOrgao->isNotEmpty())
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-3 bg-gray-50 border-b border-gray-200">
                        <p class="font-semibold text-gray-900 text-sm">Sem Secretaria — setores que atendem o Município inteiro</p>
                        <p class="text-xs text-gray-500 mt-0.5">
                            SCP, Planejamento, Procuradoria, Gabinete e TI conduzem etapas de todas as parcerias.
                        </p>
                    </div>
                    <table class="min-w-full text-sm">
                        <tbody>
                            @foreach($semOrgao as $usuario)
                                @include('orgaos._linha-usuario', ['usuario' => $usuario])
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

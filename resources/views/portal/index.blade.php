<x-portal-layout>
    {{-- Abertura --}}
    <div class="bg-white border-b border-gray-200 px-4">
        <div class="max-w-6xl mx-auto py-12">
            <span class="inline-block text-xs font-semibold uppercase tracking-wider text-brand-700 bg-brand-50 px-2.5 py-1 rounded">
                Portal Público de Parcerias
            </span>
            <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900 mt-4">
                Chamamentos Públicos Abertos
            </h1>
            <p class="text-gray-600 mt-3 max-w-2xl">
                Organizações da Sociedade Civil podem submeter propostas para os chamamentos disponíveis.
            </p>
            <div class="flex flex-wrap items-center gap-3 mt-7">
                @guest
                    <a href="{{ route('portal.osc.create') }}"
                       class="btn btn-primary">
                        Cadastre sua OSC
                    </a>
                @endguest
                <a href="#chamamentos"
                   class="btn btn-secondary">
                    Ver chamamentos
                </a>
                <a href="{{ route('transparencia') }}"
                   class="text-sm font-medium text-accent-600 hover:text-accent-700 hover:underline">
                    Parcerias já celebradas →
                </a>
            </div>
        </div>
    </div>

    <div id="chamamentos" class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        @if(session('success'))
            <div class="mb-6 bg-brand-50 border border-brand-200 text-brand-800 px-4 py-3 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if(session('info'))
            <div class="mb-6 bg-slate-50 border border-slate-200 text-slate-800 px-4 py-3 rounded-lg text-sm">
                {{ session('info') }}
            </div>
        @endif

        <div class="flex items-center justify-between mb-5">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Chamamentos disponíveis</h2>
            @if($chamamentos->total() > 0)
                <span class="text-xs font-medium text-brand-700 bg-brand-50 px-2.5 py-1 rounded-full">{{ $chamamentos->total() }} aberto(s)</span>
            @endif
        </div>

        @forelse($chamamentos as $chamamento)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-4 hover:shadow-md transition">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <x-selo-modalidade :tipo="$chamamento->tipo" />
                            <span class="text-xs text-gray-400">
                                {{ $chamamento->programa->orgao->sigla ?? $chamamento->programa->orgao->name }}
                            </span>
                        </div>
                        <h2 class="text-lg font-semibold text-gray-900">
                            @if($chamamento->numero)
                                <span class="text-gray-500 font-normal">{{ $chamamento->numero }} —</span>
                            @endif
                            {{ $chamamento->titulo }}
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">
                            Programa: <strong>{{ $chamamento->programa->name }}</strong>
                        </p>
                        @if($chamamento->objeto)
                            <p class="text-sm text-gray-500 mt-2 line-clamp-2">{{ $chamamento->objeto }}</p>
                        @endif
                        <div class="flex flex-wrap gap-4 mt-3 text-sm text-gray-600">
                            @if($chamamento->valor_disponivel)
                                <span>
                                    Valor disponível:
                                    <strong>R$ {{ number_format($chamamento->valor_disponivel, 2, ',', '.') }}</strong>
                                </span>
                            @endif
                            @if($chamamento->data_fim_inscricao)
                                <span>
                                    Inscrições até:
                                    <strong class="{{ $chamamento->data_fim_inscricao->isPast() ? 'text-red-600' : 'text-brand-700' }}">
                                        {{ $chamamento->data_fim_inscricao->format('d/m/Y') }}
                                    </strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 shrink-0">
                        <a href="{{ route('portal.chamamento', $chamamento) }}"
                           class="btn btn-outline">
                            Ver Detalhes
                        </a>
                        @if($chamamento->aceitaPropostas())
                            {{-- Participar é ação de OSC. O servidor navega no portal
                                 para consultar, e o convite não se aplica a ele: em vez
                                 de um botão que só levaria a um aviso de bloqueio, não
                                 aparece botão nenhum. --}}
                            @auth
                                @if(auth()->user()->ehRepresentanteOsc())
                                    <a href="{{ route('portal.participar', $chamamento) }}"
                                       class="btn btn-primary">
                                        Quero Participar
                                    </a>
                                @endif
                            @else
                                <a href="{{ route('login') }}"
                                   class="btn btn-primary">
                                    Entrar para Participar
                                </a>
                            @endauth
                        @elseif($chamamento->ehDispensa())
                            <span class="px-4 py-2 text-sm font-medium text-brand-700 bg-brand-50 border border-brand-200 rounded-lg text-center">
                                Publicado
                            </span>
                        @else
                            <span class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-50 border border-slate-200 rounded-lg text-center">
                                Inscrições em breve
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl border border-gray-200 px-6 py-14 text-center">
                <div class="w-14 h-14 mx-auto rounded-full bg-brand-50 flex items-center justify-center">
                    <svg class="w-7 h-7 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                    </svg>
                </div>
                <p class="mt-4 text-lg font-semibold text-gray-900">Nenhuma publicação no momento</p>
                <p class="text-sm text-gray-500 mt-1 max-w-md mx-auto">
                    Assim que um chamamento, dispensa ou inexigibilidade for publicado, ele aparecerá aqui.
                </p>
                @guest
                    <div class="flex flex-wrap items-center justify-center gap-3 mt-6">
                        <a href="{{ route('portal.osc.create') }}"
                           class="btn btn-primary">
                            Cadastrar minha OSC
                        </a>
                        <a href="{{ route('login') }}"
                           class="btn btn-outline">
                            Já tenho cadastro
                        </a>
                    </div>
                @endguest
            </div>
        @endforelse

        @if($chamamentos->hasPages())
            <div class="mt-6">{{ $chamamentos->links() }}</div>
        @endif

        {{-- Como participar (onboarding de OSC nova — só para visitantes) --}}
        @guest
        <div class="mt-14">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-5 text-center">Como participar</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @foreach([
                    ['1', 'Cadastre sua OSC', 'Crie o cadastro da sua organização com CNPJ e dados do representante legal.'],
                    ['2', 'Envie sua proposta', 'Escolha um chamamento aberto e submeta a proposta com o plano de trabalho.'],
                    ['3', 'Acompanhe', 'Veja o andamento da análise e os resultados em "Minhas Propostas".'],
                ] as [$n, $titulo, $desc])
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <span class="w-9 h-9 rounded-lg bg-brand-600 text-white font-bold flex items-center justify-center">{{ $n }}</span>
                        <h3 class="mt-4 font-semibold text-gray-900">{{ $titulo }}</h3>
                        <p class="text-sm text-gray-500 mt-1">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
        @endguest
    </div>
</x-portal-layout>

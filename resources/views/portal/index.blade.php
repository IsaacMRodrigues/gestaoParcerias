<x-portal-layout>
    {{-- Hero --}}
    <div class="relative bg-gradient-to-br from-indigo-800 via-indigo-700 to-purple-800 text-white px-4 overflow-hidden">
        <div class="absolute inset-0 opacity-10" style="background-image:radial-gradient(circle at 1px 1px,#fff 1px,transparent 0);background-size:28px 28px;"></div>
        <div class="relative max-w-3xl mx-auto text-center py-16">
            <span class="inline-block text-xs font-semibold uppercase tracking-wider text-indigo-200 bg-white/10 px-3 py-1 rounded-full mb-4">Portal Público de Parcerias</span>
            <h1 class="text-4xl font-bold mb-3 tracking-tight">Chamamentos Públicos Abertos</h1>
            <p class="text-indigo-200 text-lg">
                Organizações da Sociedade Civil podem submeter propostas para os chamamentos disponíveis.
            </p>
            <div class="flex flex-wrap items-center justify-center gap-3 mt-7">
                <a href="{{ route('portal.osc.create') }}"
                   class="px-5 py-2.5 text-sm font-semibold text-indigo-800 bg-white rounded-lg shadow hover:bg-indigo-50 transition">
                    Cadastre sua OSC
                </a>
                <a href="#chamamentos"
                   class="px-5 py-2.5 text-sm font-semibold text-white border border-white/40 rounded-lg hover:bg-white/10 transition">
                    Ver chamamentos
                </a>
            </div>
        </div>
    </div>

    <div id="chamamentos" class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        @if(session('success'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if(session('info'))
            <div class="mb-6 bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg text-sm">
                {{ session('info') }}
            </div>
        @endif

        <div class="flex items-center justify-between mb-5">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Chamamentos disponíveis</h2>
            @if($chamamentos->total() > 0)
                <span class="text-xs font-medium text-indigo-700 bg-indigo-50 px-2.5 py-1 rounded-full">{{ $chamamentos->total() }} aberto(s)</span>
            @endif
        </div>

        @forelse($chamamentos as $chamamento)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-4 hover:shadow-md transition">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-medium text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded">
                                {{ \App\Models\Chamamento::TIPOS[$chamamento->tipo] ?? $chamamento->tipo }}
                            </span>
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
                                    <strong class="{{ $chamamento->data_fim_inscricao->isPast() ? 'text-red-600' : 'text-green-700' }}">
                                        {{ $chamamento->data_fim_inscricao->format('d/m/Y') }}
                                    </strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 shrink-0">
                        <a href="{{ route('portal.chamamento', $chamamento) }}"
                           class="px-4 py-2 text-sm font-medium text-indigo-700 border border-indigo-300 rounded-lg hover:bg-indigo-50 text-center transition">
                            Ver Detalhes
                        </a>
                        @if($chamamento->status_efetivo === 'em_inscricao')
                            @auth
                                <a href="{{ route('portal.participar', $chamamento) }}"
                                   class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 text-center transition">
                                    Quero Participar
                                </a>
                            @else
                                <a href="{{ route('login') }}"
                                   class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 text-center transition">
                                    Entrar para Participar
                                </a>
                            @endauth
                        @else
                            <span class="px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-lg text-center">
                                Inscrições em breve
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl border border-gray-200 px-6 py-14 text-center">
                <div class="w-14 h-14 mx-auto rounded-full bg-indigo-50 flex items-center justify-center">
                    <svg class="w-7 h-7 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                    </svg>
                </div>
                <p class="mt-4 text-lg font-semibold text-gray-900">Nenhum chamamento aberto no momento</p>
                <p class="text-sm text-gray-500 mt-1 max-w-md mx-auto">
                    Assim que um novo chamamento for publicado, ele aparecerá aqui. Adiante o seu cadastro para participar quando abrir.
                </p>
                <div class="flex flex-wrap items-center justify-center gap-3 mt-6">
                    <a href="{{ route('portal.osc.create') }}"
                       class="px-5 py-2.5 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                        Cadastrar minha OSC
                    </a>
                    <a href="{{ route('login') }}"
                       class="px-5 py-2.5 text-sm font-semibold text-indigo-700 border border-indigo-300 rounded-lg hover:bg-indigo-50 transition">
                        Já tenho cadastro
                    </a>
                </div>
            </div>
        @endforelse

        @if($chamamentos->hasPages())
            <div class="mt-6">{{ $chamamentos->links() }}</div>
        @endif

        {{-- Como participar --}}
        <div class="mt-14">
            <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-5 text-center">Como participar</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                @foreach([
                    ['1', 'Cadastre sua OSC', 'Crie o cadastro da sua organização com CNPJ e dados do representante legal.'],
                    ['2', 'Envie sua proposta', 'Escolha um chamamento aberto e submeta a proposta com o plano de trabalho.'],
                    ['3', 'Acompanhe', 'Veja o andamento da análise e os resultados em "Minhas Propostas".'],
                ] as [$n, $titulo, $desc])
                    <div class="bg-white rounded-xl border border-gray-200 p-6">
                        <span class="w-9 h-9 rounded-lg bg-indigo-600 text-white font-bold flex items-center justify-center">{{ $n }}</span>
                        <h3 class="mt-4 font-semibold text-gray-900">{{ $titulo }}</h3>
                        <p class="text-sm text-gray-500 mt-1">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-portal-layout>

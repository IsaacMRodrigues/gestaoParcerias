<x-portal-layout>
    {{-- Hero --}}
    <div class="bg-indigo-800 text-white py-14 px-4">
        <div class="max-w-3xl mx-auto text-center">
            <h1 class="text-3xl font-bold mb-3">Chamamentos Públicos Abertos</h1>
            <p class="text-indigo-200 text-lg">
                Organizações da Sociedade Civil podem submeter propostas para os chamamentos
                disponíveis abaixo. <a href="{{ route('portal.osc.create') }}" class="underline text-white hover:text-indigo-100">Cadastre sua OSC</a> para participar.
            </p>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

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
            <div class="text-center py-20 text-gray-400">
                <p class="text-lg font-medium">Nenhum chamamento aberto no momento.</p>
                <p class="text-sm mt-1">Volte em breve ou cadastre sua OSC para receber novidades.</p>
            </div>
        @endforelse

        @if($chamamentos->hasPages())
            <div class="mt-6">{{ $chamamentos->links() }}</div>
        @endif
    </div>
</x-portal-layout>

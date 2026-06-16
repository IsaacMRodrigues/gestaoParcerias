<x-portal-layout>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <p class="text-sm text-indigo-600 mb-2">
            <a href="{{ route('portal.index') }}" class="hover:underline">← Chamamentos</a>
        </p>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">

            <div class="flex items-start justify-between gap-4 mb-6">
                <div>
                    <span class="text-xs font-medium text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded">
                        {{ \App\Models\Chamamento::TIPOS[$chamamento->tipo] ?? $chamamento->tipo }}
                    </span>
                    <h1 class="text-2xl font-bold text-gray-900 mt-2">
                        @if($chamamento->numero) {{ $chamamento->numero }} — @endif
                        {{ $chamamento->titulo }}
                    </h1>
                    <p class="text-gray-500 mt-1 text-sm">
                        {{ $chamamento->programa->orgao->name }}
                        &middot; Programa: {{ $chamamento->programa->name }}
                    </p>
                </div>
                <div class="shrink-0">
                    @auth
                        @if($chamamento->status === 'em_inscricao')
                            <a href="{{ route('portal.participar', $chamamento) }}"
                               class="inline-block px-5 py-2.5 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                                Submeter Proposta
                            </a>
                        @endif
                    @else
                        @if($chamamento->status === 'em_inscricao')
                            <a href="{{ route('login') }}"
                               class="inline-block px-5 py-2.5 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                                Entrar para Participar
                            </a>
                        @endif
                    @endauth
                </div>
            </div>

            <dl class="grid grid-cols-2 gap-x-8 gap-y-4 text-sm border-t border-gray-100 pt-6">
                @if($chamamento->valor_disponivel)
                    <div>
                        <dt class="text-gray-500">Valor Disponível</dt>
                        <dd class="font-semibold text-gray-900 mt-0.5">
                            R$ {{ number_format($chamamento->valor_disponivel, 2, ',', '.') }}
                        </dd>
                    </div>
                @endif
                @if($chamamento->data_inicio_inscricao)
                    <div>
                        <dt class="text-gray-500">Período de Inscrição</dt>
                        <dd class="font-medium text-gray-900 mt-0.5">
                            {{ $chamamento->data_inicio_inscricao->format('d/m/Y') }}
                            a {{ $chamamento->data_fim_inscricao->format('d/m/Y') }}
                        </dd>
                    </div>
                @endif
                @if($chamamento->data_inicio_vigencia)
                    <div>
                        <dt class="text-gray-500">Vigência Prevista</dt>
                        <dd class="font-medium text-gray-900 mt-0.5">
                            {{ $chamamento->data_inicio_vigencia->format('d/m/Y') }}
                            @if($chamamento->data_fim_vigencia) a {{ $chamamento->data_fim_vigencia->format('d/m/Y') }} @endif
                        </dd>
                    </div>
                @endif
                <div>
                    <dt class="text-gray-500">Status</dt>
                    <dd class="mt-0.5">
                        @php $color = \App\Models\Chamamento::STATUS_COLORS[$chamamento->status] ?? 'gray'; @endphp
                        <span class="px-2 py-1 text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800 rounded-full">
                            {{ \App\Models\Chamamento::STATUS[$chamamento->status] }}
                        </span>
                    </dd>
                </div>
            </dl>

            @if($chamamento->objeto)
                <div class="mt-6 border-t border-gray-100 pt-6">
                    <h2 class="text-sm font-semibold text-gray-700 mb-2">Objeto</h2>
                    <p class="text-sm text-gray-700 leading-relaxed">{{ $chamamento->objeto }}</p>
                </div>
            @endif

            @if($chamamento->descricao)
                <div class="mt-6 border-t border-gray-100 pt-6">
                    <h2 class="text-sm font-semibold text-gray-700 mb-2">Informações Gerais</h2>
                    <div class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ $chamamento->descricao }}</div>
                </div>
            @endif

            @if($chamamento->status === 'em_inscricao')
                <div class="mt-8 bg-indigo-50 border border-indigo-200 rounded-lg p-5 text-center">
                    <p class="text-sm text-indigo-800 font-medium mb-3">
                        Sua OSC pode submeter uma proposta para este chamamento.
                    </p>
                    @auth
                        <a href="{{ route('portal.participar', $chamamento) }}"
                           class="inline-block px-6 py-2.5 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                            Submeter Proposta
                        </a>
                    @else
                        <div class="flex justify-center gap-3">
                            <a href="{{ route('login') }}"
                               class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                                Entrar
                            </a>
                            <a href="{{ route('portal.osc.create') }}"
                               class="px-5 py-2 text-sm font-medium text-indigo-700 border border-indigo-300 rounded-lg hover:bg-indigo-50 transition">
                                Cadastrar OSC
                            </a>
                        </div>
                    @endauth
                </div>
            @endif
        </div>
    </div>
</x-portal-layout>

@php
    $secoes = [
        'Público' => [
            [route('landing'), 'Início'],
            [route('portal.index'), 'Portal de Parcerias — chamamentos abertos'],
            [route('manifestacao.publica.create'), 'Manifestar interesse (PMIS)'],
            [route('manifestacao.publica.index'), 'Manifestações de interesse recebidas'],
            [route('transparencia'), 'Transparência'],
            [route('validacao.index'), 'Validar documento'],
        ],
        'Acesso' => [
            [route('login'), 'Entrar'],
            [route('portal.osc.create'), 'Cadastrar a sua OSC'],
            [route('register'), 'Solicitar acesso (servidor da Prefeitura)'],
            [route('password.request'), 'Esqueci a senha'],
        ],
        'Informações' => [
            [route('termos.index'), 'Termos de Uso e Adesão'],
            [route('cookies.index'), 'Política de Cookies'],
            [route('acessibilidade'), 'Acessibilidade e atalhos'],
        ],
    ];
    // Logado, a área de cada um; os módulos ficam no menu lateral.
    if ($u = auth()->user()) {
        $secoes['Sua área'] = $u->temAcessoInterno()
            ? [[route('dashboard'), 'Painel'], [route('caixa'), 'Caixa de Entrada']]
            : [[route('portal.index'), 'Portal da OSC'], [route('portal.minhas-propostas'), 'Minhas inscrições']];
    }
@endphp

<x-portal-layout>
    <x-slot name="title">Mapa do sítio</x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 sm:p-8 text-sm text-gray-700 space-y-6">
            <h1 class="text-2xl font-bold text-gray-900">Mapa do sítio</h1>
            @foreach($secoes as $secao => $links)
                <section>
                    <h2 class="text-base font-semibold text-gray-900">{{ $secao }}</h2>
                    <ul class="mt-2 space-y-1.5">
                        @foreach($links as [$url, $rotulo])
                            <li><a href="{{ $url }}" class="text-brand-700 hover:underline">{{ $rotulo }}</a></li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
            @auth
                <p class="text-gray-500">Os módulos do sistema ficam no menu lateral, conforme o seu perfil.</p>
            @endauth
        </div>
    </div>
</x-portal-layout>

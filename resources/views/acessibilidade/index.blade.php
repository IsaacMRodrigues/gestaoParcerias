<x-portal-layout>
    <x-slot name="title">Acessibilidade</x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 sm:p-8 text-sm text-gray-700 leading-relaxed space-y-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Acessibilidade</h1>
                <p class="text-gray-500 mt-1">Plataforma de Gestão de Parcerias — Município de São Gonçalo do Rio Abaixo/MG</p>
            </div>

            <p>A Plataforma busca atender, de forma progressiva, ao Modelo de Acessibilidade em Governo Eletrônico (e-MAG)
                e às diretrizes do W3C, como pede a Lei Federal nº 13.146/2015 (Lei Brasileira de Inclusão), art. 63.</p>

            <h2 class="text-base font-semibold text-gray-900 pt-2">Atalhos de teclado</h2>
            <p>Funcionam em todas as páginas, fora dos campos de texto: basta apertar o número.</p>
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full text-sm">
                    <tbody class="divide-y divide-gray-100">
                        @foreach([
                            '0' => 'Ir para o menu',
                            '1' => 'Ir para o conteúdo',
                            '2' => 'Aumentar o tamanho do texto',
                            '3' => 'Diminuir o tamanho do texto',
                            '4' => 'Ativar o alto contraste',
                            '5' => 'Desativar o alto contraste',
                            '6' => 'Abrir esta página de ajuda',
                            '7' => 'Abrir o mapa do sítio',
                        ] as $tecla => $acao)
                            <tr>
                                <td class="px-3 py-2 w-16"><kbd class="px-2 py-0.5 rounded border border-gray-300 bg-gray-50 font-mono text-gray-900">{{ $tecla }}</kbd></td>
                                <td class="px-3 py-2">{{ $acao }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p>No início de cada página, a primeira parada do <kbd class="px-1 rounded border border-gray-300 font-mono">Tab</kbd>
                é o link "Pular para o conteúdo". A navegação pelo teclado segue a ordem da página: menu, conteúdo e ações.</p>

            <h2 class="text-base font-semibold text-gray-900 pt-2">Controles na tela</h2>
            <p>O botão <strong>Acessibilidade</strong>, no canto de baixo à esquerda de todas as páginas, abre os controles:
                aumentar e diminuir o texto (até 130%) e ativar ou desativar o alto contraste (fundo preto, texto branco e
                links em amarelo). As escolhas ficam guardadas neste navegador e valem nas próximas visitas.</p>
            <p>O zoom do próprio navegador também funciona em todas as telas: <kbd class="px-1 rounded border border-gray-300 font-mono">Ctrl</kbd> +
                <kbd class="px-1 rounded border border-gray-300 font-mono">+</kbd> aumenta e <kbd class="px-1 rounded border border-gray-300 font-mono">Ctrl</kbd> +
                <kbd class="px-1 rounded border border-gray-300 font-mono">−</kbd> diminui.</p>

            <h2 class="text-base font-semibold text-gray-900 pt-2">Libras</h2>
            <p>O ícone do <strong>VLibras</strong>, à direita da tela, traduz para a Língua Brasileira de Sinais o texto que
                você selecionar. O VLibras é um serviço do Governo Federal.</p>

            <h2 class="text-base font-semibold text-gray-900 pt-2">Encontrou uma barreira?</h2>
            <p>Se algum atalho conflitar com o seu navegador ou com uma tecnologia assistiva, use o menu e os controles da tela.
                Para relatar um problema de acessibilidade, use o Suporte, no menu do sistema; a correção é feita de forma
                progressiva. Veja também o <a href="{{ route('mapa-do-sitio') }}" class="text-brand-700 hover:underline">mapa do sítio</a>.</p>
        </div>
    </div>
</x-portal-layout>

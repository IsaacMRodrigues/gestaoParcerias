@php
    // Os cookies que a Plataforma grava, como estão configurados (config/session.php). O "Lembrar de mim" dura o
    // padrão do Laravel (SessionGuard: 576.000 minutos).
    $sessao = (int) config('session.lifetime');
    $cookies = [
        [config('session.cookie'), 'Plataforma', 'Mantém você conectado enquanto usa o sistema e guarda o andamento das telas.',
            "{$sessao} minutos sem uso, ou até sair"],
        ['XSRF-TOKEN', 'Plataforma', 'Protege os formulários: impede que outro site envie dados em seu nome.', 'A mesma da sessão'],
        ['remember_web_… (seguido de um código)', 'Plataforma, só se você marcar "Lembrar de mim" ao entrar',
            'Mantém você conectado neste navegador sem pedir a senha de novo.', '400 dias, ou até sair'],
        ['pgp_aviso_cookies', 'Plataforma', 'Lembra que você já viu o aviso de cookies.', '1 ano'],
        ['Cookies do reCAPTCHA (como _GRECAPTCHA)', 'Google, só na página do formulário público de manifestação de interesse',
            'Distingue pessoas de robôs, para proteger o formulário contra envios automáticos.', 'Definida pelo Google'],
    ];
@endphp

<x-portal-layout>
    <x-slot name="title">Política de Cookies</x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 sm:p-8 text-sm text-gray-700 leading-relaxed space-y-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Política de Cookies</h1>
                <p class="text-gray-500 mt-1">Plataforma de Gestão de Parcerias — Município de São Gonçalo do Rio Abaixo/MG · atualizada em 28/10/2026</p>
            </div>

            <p>Cookies são pequenos arquivos que o site grava no seu navegador para reconhecer a sua visita. A Plataforma usa
                <strong>apenas cookies necessários</strong> ao funcionamento e à segurança do sistema. Não usa cookies de
                propaganda, de estatística de navegação nem de redes sociais.</p>

            <h2 class="text-base font-semibold text-gray-900 pt-2">Os cookies que gravamos</h2>
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full text-xs">
                    <thead class="bg-gray-50 text-gray-500 uppercase tracking-wide">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold">Cookie</th>
                            <th class="px-3 py-2 text-left font-semibold">Quem grava</th>
                            <th class="px-3 py-2 text-left font-semibold">Para quê</th>
                            <th class="px-3 py-2 text-left font-semibold">Duração</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($cookies as [$nome, $quem, $para, $duracao])
                            <tr class="align-top">
                                <td class="px-3 py-2 font-mono text-gray-900">{{ $nome }}</td>
                                <td class="px-3 py-2">{{ $quem }}</td>
                                <td class="px-3 py-2">{{ $para }}</td>
                                <td class="px-3 py-2">{{ $duracao }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p>Na área interna, a busca do sistema guarda no seu próprio navegador (armazenamento local, chave
                <span class="font-mono">pgp:paleta:recentes</span>) as últimas telas abertas, para sugeri-las de novo. Essa
                lista não é enviada ao servidor e some quando você limpa os dados do navegador.</p>

            <p>As fontes de letra vêm do Bunny Fonts, que não grava cookies. O reCAPTCHA só é carregado na página do
                formulário público de manifestação de interesse, e o Google trata esses dados conforme a sua
                <a href="https://policies.google.com/privacy" target="_blank" rel="noopener" class="text-brand-700 hover:underline">Política de Privacidade</a>.</p>

            <h2 class="text-base font-semibold text-gray-900 pt-2">Como controlar</h2>
            <p>Como são necessários, esses cookies não se desligam na Plataforma. Você pode apagá-los ou bloqueá-los nas
                configurações do seu navegador, mas, sem eles, não é possível entrar no sistema nem enviar formulários.</p>

            <p>O tratamento de dados pessoais na Plataforma está descrito nos
                <a href="{{ route('termos.index') }}" class="text-brand-700 hover:underline">Termos de Uso e Adesão</a>, item 8, nos termos da
                Lei Federal nº 13.709/2018 (LGPD).</p>
        </div>
    </div>
</x-portal-layout>

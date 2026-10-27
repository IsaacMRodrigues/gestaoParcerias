@php
    $campo = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500';
@endphp

<x-portal-layout>
    <x-slot name="title">Manifestar interesse</x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Manifestação de Interesse Social (PMIS)</h1>
            <p class="text-sm text-gray-500 mt-1">
                <a href="{{ route('manifestacao.publica.index') }}" class="text-brand-700 hover:underline">Ver as manifestações já recebidas →</a>
            </p>
        </div>

        {{-- Apresentação do PMIS: o que é, o que a lei exige e o que acontece depois. --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 text-sm text-gray-700 space-y-3">
            <p>
                O Procedimento de Manifestação de Interesse Social (PMIS) é um dos instrumentos de participação social do
                Marco Regulatório das Organizações da Sociedade Civil (MROSC), instituído pela Lei nº 13.019/2014.
            </p>
            <p>
                Por meio dele, organizações da sociedade civil, movimentos sociais e cidadãos podem apresentar propostas
                para a Prefeitura avaliar a abertura de um chamamento público. A proposta <strong>não implica
                necessariamente</strong> a realização do chamamento.
            </p>
            <p>
                Se a proposta acatada tiver sido elaborada por uma organização da sociedade civil, ela pode participar do
                chamamento, que é obrigatório caso a proposta seja executada (art. 21 da Lei nº 13.019/2014).
            </p>
            <div>
                <p>A proposta deve trazer:</p>
                <ul class="mt-1 list-disc list-inside space-y-0.5">
                    <li>identificação de quem a assina: cópia do documento de identidade, se pessoa física, ou documentação que comprove a representação, se pessoa jurídica;</li>
                    <li>indicação do interesse público envolvido;</li>
                    <li>diagnóstico da realidade que se quer modificar, aprimorar ou desenvolver e, quando possível, a viabilidade, os custos, os benefícios e os prazos de execução da ação pretendida.</li>
                </ul>
            </div>
            <p>
                Se aceita, a proposta passa por oitiva pública e chamamento público. Escolha abaixo a Secretaria a que ela se
                destina e anexe a proposta em PDF.
            </p>
        </div>

        <form action="{{ route('manifestacao.publica.store') }}" method="POST" enctype="multipart/form-data"
              class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-5">
            @csrf

            @if($osc)
                <div class="rounded-lg border border-brand-100 bg-brand-50 p-3 text-sm text-brand-900">
                    Manifestação em nome de <strong>{{ $osc->name }}</strong>, vinculada ao cadastro da organização.
                </div>
            @else
                <div class="grid sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <x-input-label for="proponente_nome" value="Nome / Razão Social *" />
                        <input type="text" id="proponente_nome" name="proponente_nome" required maxlength="255"
                               value="{{ old('proponente_nome') }}" class="{{ $campo }}">
                        <x-input-error :messages="$errors->get('proponente_nome')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="proponente_tipo" value="Tipo de pessoa *" />
                        <select id="proponente_tipo" name="proponente_tipo" required class="{{ $campo }}">
                            <option value="">Escolha…</option>
                            @foreach(\App\Models\ManifestacaoInteresse::TIPOS_DE_PESSOA as $chave => $rotulo)
                                <option value="{{ $chave }}" @selected(old('proponente_tipo') === $chave)>{{ $rotulo }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('proponente_tipo')" class="mt-1" />
                    </div>
                </div>
            @endif

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="proponente_email" value="E-mail *" />
                    <input type="email" id="proponente_email" name="proponente_email" required maxlength="255"
                           value="{{ old('proponente_email', $osc?->email ?? auth()->user()?->email) }}" class="{{ $campo }}">
                    <p class="text-xs text-gray-400 mt-1">Para onde vão o protocolo, o link de acompanhamento e a decisão.</p>
                    <x-input-error :messages="$errors->get('proponente_email')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="orgao_id" value="Secretaria *" />
                    <select id="orgao_id" name="orgao_id" required class="{{ $campo }}">
                        <option value="">Escolha…</option>
                        @foreach($orgaos as $orgao)
                            <option value="{{ $orgao->id }}" @selected((string) old('orgao_id') === (string) $orgao->id)>{{ $orgao->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('orgao_id')" class="mt-1" />
                </div>
            </div>

            @foreach([
                'interesse_publico' => 'Indicação do interesse público envolvido',
                'diagnostico'       => 'Diagnóstico da realidade que se quer modificar, aprimorar ou desenvolver',
                'viabilidade'       => 'Indicação da viabilidade, dos custos, dos benefícios e dos prazos de execução da ação pretendida',
            ] as $nome => $rotulo)
                <div>
                    <x-input-label :for="$nome" :value="$rotulo . ' *'" />
                    <textarea id="{{ $nome }}" name="{{ $nome }}" rows="5" required maxlength="5000"
                              class="{{ $campo }}">{{ old($nome) }}</textarea>
                    <x-input-error :messages="$errors->get($nome)" class="mt-1" />
                </div>
            @endforeach

            <div>
                <x-input-label for="arquivo" value="Proposta em PDF *" />
                <input type="file" id="arquivo" name="arquivo" accept="application/pdf,.pdf" required
                       class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                <p class="text-xs text-gray-400 mt-1">Somente PDF, até 25 MB, com a proposta e a identificação de quem a assina.</p>
                <x-input-error :messages="$errors->get('arquivo')" class="mt-1" />
            </div>

            @if(\App\Rules\Recaptcha::configurado())
                <div>
                    <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
                    <x-input-error :messages="$errors->get('g-recaptcha-response')" class="mt-1" />
                </div>
                <script src="https://www.google.com/recaptcha/api.js" async defer></script>
            @else
                <x-input-error :messages="$errors->get('g-recaptcha-response')" class="mt-1" />
            @endif

            <div class="flex items-center gap-3 pt-2 border-t border-gray-100">
                <button type="submit" class="btn btn-primary">Enviar manifestação</button>
                <button type="reset" class="btn btn-outline">Limpar</button>
            </div>
        </form>
    </div>
</x-portal-layout>

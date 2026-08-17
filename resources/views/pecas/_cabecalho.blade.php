{{-- Cabeçalho da lista de peças, com o progresso embutido.

     Antes o progresso era um card separado logo acima da lista — duas caixas
     para o mesmo assunto. Espera: $titulo, $descricao, $progresso. --}}
<div class="px-6 py-4 border-b border-gray-200">
    <div class="flex items-start justify-between gap-4 flex-wrap">
        <div class="min-w-0">
            <h3 class="text-base font-semibold text-gray-900">{{ $titulo }}</h3>
            <p class="text-xs text-gray-500 mt-0.5">{{ $descricao }}</p>
        </div>

        <div class="shrink-0 text-right">
            <span class="text-lg font-bold {{ $progresso['percent'] === 100 ? 'text-brand-700' : 'text-gray-900' }}">
                {{ $progresso['ok'] }}<span class="text-gray-400 font-semibold">/{{ $progresso['total'] }}</span>
            </span>
            <span class="block text-xs text-gray-500">obrigatórias concluídas</span>
        </div>
    </div>

    <div class="mt-3 w-full bg-gray-100 rounded-full h-2 overflow-hidden">
        <div class="h-2 rounded-full transition-all {{ $progresso['percent'] === 100 ? 'bg-brand-600' : 'bg-accent-400' }}"
             style="width: {{ $progresso['percent'] }}%"></div>
    </div>

    @if($progresso['percent'] === 100)
        <p class="text-xs font-semibold text-brand-700 mt-2 flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Documentação obrigatória completa.
        </p>
    @endif
</div>

{{-- Campo de pesquisa de uma lista: envia ?busca= para a mesma tela e oferece limpar. --}}
@props([
    'action',
    'placeholder' => 'Pesquisar…',
    'ancora' => null,
])

@php $valor = request('busca'); @endphp

<form method="GET" action="{{ $action }}{{ $ancora ? '#' . $ancora : '' }}" role="search"
      {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2']) }}>
    <div class="relative flex-1 min-w-0">
        <svg class="absolute top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" style="left:.75rem" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10.5a6.5 6.5 0 11-13 0 6.5 6.5 0 0113 0z"/>
        </svg>
        <input type="search" name="busca" value="{{ $valor }}" placeholder="{{ $placeholder }}" aria-label="Pesquisar"
               style="padding-left:2.25rem" class="block w-full border-gray-300 rounded-lg shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">
    </div>
    <button class="btn btn-primary btn-sm">Pesquisar</button>
    @if(filled($valor))
        <a href="{{ $action }}{{ $ancora ? '#' . $ancora : '' }}" class="text-sm text-gray-500 hover:text-gray-800 px-1">Limpar</a>
    @endif
</form>

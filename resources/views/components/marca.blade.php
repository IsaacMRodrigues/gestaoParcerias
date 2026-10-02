{{-- Logotipo da Prefeitura. $variant: 'color' (fundo claro) ou 'branco' (fundo escuro,
     rebatido para branco). O tamanho vem de $class ('h-9' ou 'w-full h-auto'). --}}
@props(['variant' => 'color', 'class' => 'h-9'])

<img src="https://pmsgra.net/logotipo.png" alt="Prefeitura de São Gonçalo do Rio Abaixo"
     class="{{ $class }} shrink-0 {{ $variant === 'branco' ? 'brightness-0 invert' : '' }}">

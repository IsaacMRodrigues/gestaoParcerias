{{-- Selo da modalidade, com a mesma cor em todo lugar. $rotulo permite o nome longo do
     Processo ("Dispensa de Chamamento Público"). --}}
@props(['tipo', 'rotulo' => null])

@php
    $cor = \App\Models\Chamamento::TIPOS_COLORS[$tipo] ?? 'slate';
    $texto = $rotulo ?? (\App\Models\Chamamento::TIPOS[$tipo] ?? $tipo);
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-md
        whitespace-nowrap bg-{$cor}-50 text-{$cor}-700 ring-1 ring-{$cor}-200"]) }}>
    {{ $texto }}
</span>

{{-- Selo da modalidade (Chamamento Público, Dispensa, Inexigibilidade).

     A mesma informação aparecia em cinco telas com quatro aparências: verde
     claro no portal, verde forte no processo, cinza na seleção e texto puro na
     listagem. Além de inconsistente, o verde não distinguia uma modalidade da
     outra — são três categorias com efeitos jurídicos diferentes, e agora cada
     uma tem sua cor, igual em todo lugar.

     $rotulo permite o nome longo do Processo ("Dispensa de Chamamento Público")
     onde há espaço, mantendo a mesma cor do nome curto do Chamamento. --}}
@props(['tipo', 'rotulo' => null])

@php
    $cor = \App\Models\Chamamento::TIPOS_COLORS[$tipo] ?? 'slate';
    $texto = $rotulo ?? (\App\Models\Chamamento::TIPOS[$tipo] ?? $tipo);
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-md
        whitespace-nowrap bg-{$cor}-50 text-{$cor}-700 ring-1 ring-{$cor}-200"]) }}>
    {{ $texto }}
</span>

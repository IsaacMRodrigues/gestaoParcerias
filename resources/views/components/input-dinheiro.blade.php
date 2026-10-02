{{-- Campo de dinheiro: o visível é mascarado por resources/js/money.js e o oculto leva o
     número. Sem JS, o middleware NormalizaValoresMonetarios converte no servidor. --}}
@props([
    'name',
    'value' => null,
    'id' => null,
    'required' => false,
    'placeholder' => '0,00',
])

@php
    // O `disabled` precisa valer para o campo oculto também: desabilitar só o
    // visível deixaria o valor sendo enviado num formulário que a tela mostra
    // como bloqueado.
    $desabilitado = filter_var($attributes->get('disabled', false), FILTER_VALIDATE_BOOL);

    $id     = $id ?: $name;
    $bruto  = old($name, $value);
    $exibir = ($bruto === null || $bruto === '') ? '' : number_format((float) $bruto, 2, ',', '.');
@endphp

<div class="relative">
    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-gray-500 pointer-events-none">R$</span>

    <input type="text" inputmode="decimal" id="{{ $id }}_display" data-money="{{ $id }}"
           value="{{ $exibir }}" placeholder="{{ $placeholder }}" @required($required)
           {{ $attributes->merge(['class' => 'block w-full pl-10 border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500']) }}>

    <input type="hidden" name="{{ $name }}" id="{{ $id }}" value="{{ $bruto }}" @disabled($desabilitado)>
</div>

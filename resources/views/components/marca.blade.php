{{-- Logotipo oficial da Prefeitura de São Gonçalo do Rio Abaixo.
     $variant: 'color' (fundo claro) | 'branco' (fundo escuro — aplica um
     quadro branco atrás do logo para garantir contraste). --}}
@props(['variant' => 'color', 'class' => 'h-9'])

@if($variant === 'branco')
    <span class="inline-flex items-center justify-center bg-white rounded-lg px-2 py-1 shadow-sm shrink-0">
        <img src="https://pmsgra.net/logotipo.png" alt="Prefeitura de São Gonçalo do Rio Abaixo"
             class="{{ $class }} w-auto">
    </span>
@else
    <img src="https://pmsgra.net/logotipo.png" alt="Prefeitura de São Gonçalo do Rio Abaixo"
         class="{{ $class }} w-auto shrink-0">
@endif

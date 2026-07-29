{{-- Campo de anexo do cadastro da OSC: upload + link para o arquivo atual. --}}
@props(['osc' => null, 'campo', 'label'])

<div class="mt-3">
    <x-input-label :for="$campo" :value="$label" />
    <input id="{{ $campo }}" name="{{ $campo }}" type="file" accept=".pdf,.jpg,.jpeg,.png"
           class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:text-xs file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
    @if($osc?->{$campo})
        <p class="text-xs text-gray-500 mt-1">
            Atual:
            <a href="{{ route('oscs.anexo', [$osc, $campo]) }}" class="text-indigo-600 hover:underline" target="_blank">
                baixar arquivo enviado
            </a>
            — enviar novo substitui.
        </p>
    @endif
    <x-input-error :messages="$errors->get($campo)" class="mt-2" />
</div>

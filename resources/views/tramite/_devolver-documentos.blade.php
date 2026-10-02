{{-- Devolução por documento: só os marcados reabrem e o trâmite volta à etapa do mais antigo.
     Espera: $documentos (Devolucao::candidatas). --}}
@if($documentos->isNotEmpty())
    <fieldset class="border border-red-200 rounded-md p-3">
        <legend class="px-1 text-xs font-semibold text-red-800">Quais documentos estão errados?</legend>
        <p class="text-xs text-gray-500 mb-2">
            Só os marcados voltam para correção; os outros continuam assinados. O trâmite volta para a etapa do
            documento mais antigo que você marcar.
        </p>
        <div class="space-y-1">
            @foreach($documentos as $doc)
                <label class="flex items-start gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="documentos[]" value="{{ $doc->id }}" @checked(in_array($doc->id, old('documentos', [])))
                           class="mt-0.5 rounded border-gray-300 text-red-600 focus:ring-red-500">
                    <span>
                        {{ \App\Support\Devolucao::rotulo($doc) }}
                        <span class="text-xs text-gray-400">· etapa {{ \App\Support\Devolucao::etapaDoDocumento($doc) + 1 }}</span>
                    </span>
                </label>
            @endforeach
        </div>
        <x-input-error :messages="$errors->get('documentos.*')" class="mt-1" />
    </fieldset>
@endif

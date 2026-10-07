{{-- Nova Proposta, primeiro formulário: o plano de aplicação dos recursos, digitado. O valor pleiteado
     e a planilha de itens se lançam no plano de trabalho, na tela seguinte. --}}
<div class="pt-4 border-t border-gray-100">
    <x-input-label for="plano_aplicacao" value="Plano de aplicação dos recursos *" />
    <textarea name="plano_aplicacao" id="plano_aplicacao" rows="5" required maxlength="1000"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-brand-500 focus:border-brand-500">{{ old('plano_aplicacao') }}</textarea>
    <p class="mt-1 text-xs text-gray-400">O que será comprado ou contratado, e por quanto.</p>
    <x-input-error :messages="$errors->get('plano_aplicacao')" class="mt-1" />
</div>

<x-app-layout>
    <x-slot name="header">
        <p class="text-sm text-gray-500">
            <a href="{{ route('propostas.index') }}" class="hover:underline">Propostas</a>
            &rsaquo;
            <a href="{{ route('propostas.show', $proposta) }}" class="hover:underline">{{ $proposta->titulo }}</a>
        </p>
        <h2 class="text-2xl font-bold text-gray-900 mt-0.5">
            {{ \App\Models\Parecer::TIPOS[$tipo] }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <form action="{{ route('propostas.pareceres.store', $proposta) }}" method="POST"
                      class="space-y-5" x-data="{ resultado: '' }">
                    @csrf
                    <input type="hidden" name="tipo" value="{{ $tipo }}">

                    <div>
                        <x-input-label for="resultado" value="Resultado *" />
                        <select id="resultado" name="resultado" required
                                x-model="resultado"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm">
                            <option value="">Selecione...</option>
                            @foreach(\App\Models\Parecer::RESULTADOS as $key => $label)
                                <option value="{{ $key }}" {{ old('resultado') === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('resultado')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="texto" value="Texto do Parecer *" />
                        <textarea id="texto" name="texto" rows="6" required
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm">{{ old('texto') }}</textarea>
                        <x-input-error :messages="$errors->get('texto')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="responsavel" value="Responsável" />
                            <x-text-input id="responsavel" name="responsavel" type="text"
                                          class="mt-1 block w-full" value="{{ old('responsavel') }}" />
                        </div>
                        <div>
                            <x-input-label for="data_parecer" value="Data *" />
                            <x-text-input id="data_parecer" name="data_parecer" type="date"
                                          class="mt-1 block w-full"
                                          value="{{ old('data_parecer', today()->format('Y-m-d')) }}" required />
                            <x-input-error :messages="$errors->get('data_parecer')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="observacoes" value="Observações" />
                        <textarea id="observacoes" name="observacoes" rows="3"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm">{{ old('observacoes') }}</textarea>
                    </div>

                    {{-- Campos de diligência: aparecem apenas quando resultado = diligencia --}}
                    <div x-show="resultado === 'diligencia'" x-cloak
                         class="border border-blue-200 bg-blue-50 rounded-lg p-4 space-y-4">
                        <p class="text-sm font-medium text-blue-800">Dados da Diligência</p>
                        <div>
                            <x-input-label for="diligencia_descricao" value="O que está sendo solicitado *" />
                            <textarea id="diligencia_descricao" name="diligencia_descricao" rows="3"
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm">{{ old('diligencia_descricao') }}</textarea>
                            <x-input-error :messages="$errors->get('diligencia_descricao')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="diligencia_prazo" value="Prazo para Resposta *" />
                            <x-text-input id="diligencia_prazo" name="diligencia_prazo" type="date"
                                          class="mt-1 block w-full" value="{{ old('diligencia_prazo') }}" />
                            <x-input-error :messages="$errors->get('diligencia_prazo')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4 pt-2">
                        <a href="{{ route('propostas.show', $proposta) }}"
                           class="text-sm text-gray-600 hover:text-gray-900">Cancelar</a>
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-brand-600 rounded-lg shadow-sm hover:bg-brand-700 transition">
                            Registrar Parecer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

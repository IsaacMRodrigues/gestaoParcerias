<x-app-layout>
    <x-slot name="header">
        <p class="text-sm text-gray-500">
            <a href="{{ route('propostas.index') }}" class="hover:underline">Propostas</a>
            &rsaquo;
            <a href="{{ route('propostas.show', $proposta) }}" class="hover:underline">{{ $proposta->titulo }}</a>
        </p>
        <h2 class="text-xl font-semibold text-gray-800 mt-0.5">Diligência</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <x-flash-message />

            <div class="bg-white shadow rounded-lg p-6 space-y-4">
                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-gray-500">O que foi solicitado</dt>
                        <dd class="mt-1 text-gray-900">{{ $diligencia->descricao }}</dd>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="text-gray-500">Prazo</dt>
                            <dd class="text-gray-900">{{ $diligencia->prazo->format('d/m/Y') }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Status</dt>
                            <dd>
                                @php $color = \App\Models\Diligencia::STATUS_COLORS[$diligencia->status] ?? 'gray'; @endphp
                                <span class="px-2 py-1 text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-800 rounded-full">
                                    {{ \App\Models\Diligencia::STATUS[$diligencia->status] }}
                                </span>
                            </dd>
                        </div>
                    </div>
                    @if($diligencia->resposta)
                        <div>
                            <dt class="text-gray-500">Resposta da OSC</dt>
                            <dd class="mt-1 text-gray-900">{{ $diligencia->resposta }}</dd>
                            <dd class="text-xs text-gray-400 mt-1">
                                Respondido em {{ $diligencia->respondida_em->format('d/m/Y H:i') }}
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>

            @if($diligencia->status === 'pendente')
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-base font-semibold text-gray-800 mb-4">Responder Diligência</h3>
                    <form action="{{ route('propostas.diligencias.responder', [$proposta, $diligencia]) }}"
                          method="POST" class="space-y-4">
                        @csrf
                        @method('PATCH')
                        <div>
                            <x-input-label for="resposta" value="Resposta *" />
                            <textarea id="resposta" name="resposta" rows="5" required
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">{{ old('resposta') }}</textarea>
                            <x-input-error :messages="$errors->get('resposta')" class="mt-2" />
                        </div>
                        <div class="flex justify-end">
                            <button type="submit"
                                    class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                                Enviar Resposta
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>

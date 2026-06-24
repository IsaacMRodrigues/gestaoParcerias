<x-portal-layout>
    <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
            <h1 class="text-2xl font-bold text-gray-900">Validar Documento</h1>
            <p class="text-sm text-gray-500 mt-1 mb-6">
                Informe o <strong>código de validação</strong> impresso no documento assinado para
                conferir a autenticidade.
            </p>

            @if(session('erro'))
                <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
                    {{ session('erro') }}
                </div>
            @endif

            <form action="{{ route('validacao.verificar') }}" method="POST" class="flex gap-2">
                @csrf
                <input type="text" name="codigo" required value="{{ old('codigo') }}"
                       placeholder="Ex.: A1B2-C3D4-E5"
                       class="flex-1 border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm uppercase">
                <button type="submit"
                        class="px-5 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                    Validar
                </button>
            </form>
            @error('codigo') <p class="text-red-600 text-xs mt-2">{{ $message }}</p> @enderror
        </div>
    </div>
</x-portal-layout>

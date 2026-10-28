<x-portal-layout>
    <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 py-12 space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
            <h1 class="text-2xl font-bold text-gray-900">Validar Documento</h1>
            <p class="text-sm text-gray-500 mt-1 mb-6">
                Informe o <strong>código de validação</strong> impresso no documento assinado para conferir quem assinou,
                quando, e se o texto é o mesmo do momento da assinatura.
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
                       class="flex-1 border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm uppercase">
                <button type="submit"
                        class="btn btn-primary">
                    Validar
                </button>
            </form>
            @error('codigo') <p class="text-red-600 text-xs mt-2">{{ $message }}</p> @enderror
        </div>

        {{-- O PDF é reconhecido pelo hash do arquivo (TrilhaDeAssinatura::registrarEmissao); ele não fica guardado. --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
            <h2 class="text-lg font-bold text-gray-900">Conferir um arquivo PDF</h2>
            <p class="text-sm text-gray-500 mt-1 mb-4">
                Recebeu o PDF de um documento assinado? Envie o arquivo para saber se ele saiu deste sistema e se está
                intacto: qualquer alteração, mesmo de uma letra, é detectada. O arquivo é só conferido, não fica guardado.
            </p>
            <form action="{{ route('validacao.arquivo') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <input type="file" name="arquivo" accept="application/pdf,.pdf" required
                       class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100">
                <button type="submit" class="btn btn-primary">Conferir arquivo</button>
            </form>
            @error('arquivo') <p class="text-red-600 text-xs mt-2">{{ $message }}</p> @enderror
        </div>
    </div>
</x-portal-layout>

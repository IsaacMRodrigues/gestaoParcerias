<x-portal-layout>
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-4">
        <div class="flex items-center justify-between gap-4 print:hidden">
            <a href="{{ route('portal.arquivos.index') }}" class="text-sm text-brand-600 hover:underline">← Arquivos da OSC</a>
            <button type="button" onclick="window.print()" class="btn btn-primary btn-sm">Imprimir</button>
        </div>
        <p class="text-xs text-gray-500 print:hidden">
            {{ $titulo }} — preenchida com o cadastro da organização. Confira, imprima, assine e anexe a versão assinada
            em "Arquivos da OSC". Onde estiver XXXXX, o cadastro não tem o dado: complete à mão ou atualize o cadastro.
        </p>
        <div class="documento-html bg-white border border-gray-200 rounded-lg p-8 text-gray-900 text-sm">
            {!! $conteudo !!}
        </div>
    </div>
</x-portal-layout>

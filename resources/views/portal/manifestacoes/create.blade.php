<x-portal-layout>
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <p class="text-sm text-brand-600">
            <a href="{{ route('portal.manifestacoes.index') }}" class="hover:underline">← Manifestações de Interesse</a>
        </p>
        <h1 class="text-2xl font-bold text-gray-900 mt-1">Nova manifestação de interesse</h1>
        <p class="text-sm text-gray-500 mt-1 mb-6">
            Comece pelos dados gerais. Na tela seguinte você monta o plano de trabalho e anexa a
            habilitação — a manifestação só vai ao município quando você submeter.
        </p>

        <x-flash-message />

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <form action="{{ route('portal.manifestacoes.store') }}" method="POST" class="space-y-4">
                @csrf
                @include('portal.manifestacoes._campos', ['manifestacao' => null])
                <div class="pt-2">
                    <button type="submit" class="btn btn-primary">Criar manifestação</button>
                </div>
            </form>
        </div>
    </div>
</x-portal-layout>

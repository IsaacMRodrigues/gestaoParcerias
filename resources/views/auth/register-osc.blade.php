<x-portal-layout>
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Cadastrar Organização</h1>
            <p class="text-gray-500 mt-1 text-sm">
                Preencha os dados da sua OSC para participar dos chamamentos públicos.
                Já tem conta? <a href="{{ route('login') }}" class="text-brand-600 hover:underline">Entrar</a>
            </p>
        </div>

        <form method="POST" action="{{ route('portal.osc.store') }}" class="space-y-8">
            @csrf

            {{-- Dados da OSC --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-base font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-100">
                    Dados da Organização (OSC)
                </h2>
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label for="osc_name" class="block text-sm font-medium text-gray-700 mb-1">Razão Social / Nome da OSC *</label>
                        <input id="osc_name" name="osc_name" type="text" required
                               value="{{ old('osc_name') }}"
                               class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm @error('osc_name') border-red-300 @enderror">
                        @error('osc_name') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="cnpj" class="block text-sm font-medium text-gray-700 mb-1">CNPJ *</label>
                            <input id="cnpj" name="cnpj" type="text" required
                                   value="{{ old('cnpj') }}" placeholder="00.000.000/0000-00"
                                   class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm @error('cnpj') border-red-300 @enderror">
                            @error('cnpj') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="tipo" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Organização *</label>
                            <select id="tipo" name="tipo" required
                                    class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm @error('tipo') border-red-300 @enderror">
                                <option value="">Selecione...</option>
                                @foreach(\App\Models\Osc::TIPOS as $key => $label)
                                    <option value="{{ $key }}" {{ old('tipo') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('tipo') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="osc_email" class="block text-sm font-medium text-gray-700 mb-1">E-mail Institucional</label>
                            <input id="osc_email" name="osc_email" type="email"
                                   value="{{ old('osc_email') }}"
                                   class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm">
                        </div>
                        <div>
                            <label for="osc_phone" class="block text-sm font-medium text-gray-700 mb-1">Telefone Institucional</label>
                            <input id="osc_phone" name="osc_phone" type="text"
                                   value="{{ old('osc_phone') }}"
                                   class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm">
                        </div>
                    </div>

                    {{-- Endereço --}}
                    <div class="grid grid-cols-3 gap-4 pt-2 border-t border-gray-100">
                        <div>
                            <label for="cep" class="block text-sm font-medium text-gray-700 mb-1">CEP</label>
                            <input id="cep" name="cep" type="text" value="{{ old('cep') }}" placeholder="00000-000"
                                   class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm">
                        </div>
                        <div class="col-span-2">
                            <label for="logradouro" class="block text-sm font-medium text-gray-700 mb-1">Logradouro</label>
                            <input id="logradouro" name="logradouro" type="text" value="{{ old('logradouro') }}"
                                   class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm">
                        </div>
                        <div>
                            <label for="numero" class="block text-sm font-medium text-gray-700 mb-1">Número</label>
                            <input id="numero" name="numero" type="text" value="{{ old('numero') }}"
                                   class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm">
                        </div>
                        <div>
                            <label for="complemento" class="block text-sm font-medium text-gray-700 mb-1">Complemento</label>
                            <input id="complemento" name="complemento" type="text" value="{{ old('complemento') }}"
                                   class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm">
                        </div>
                        <div>
                            <label for="bairro" class="block text-sm font-medium text-gray-700 mb-1">Bairro</label>
                            <input id="bairro" name="bairro" type="text" value="{{ old('bairro') }}"
                                   class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm">
                        </div>
                        <div class="col-span-2">
                            <label for="cidade" class="block text-sm font-medium text-gray-700 mb-1">Cidade *</label>
                            <input id="cidade" name="cidade" type="text" required value="{{ old('cidade') }}"
                                   class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm @error('cidade') border-red-300 @enderror">
                            @error('cidade') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">Estado *</label>
                            <select id="estado" name="estado" required
                                    class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm @error('estado') border-red-300 @enderror">
                                <option value="">UF</option>
                                @foreach(['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'] as $uf)
                                    <option value="{{ $uf }}" {{ old('estado') === $uf ? 'selected' : '' }}>{{ $uf }}</option>
                                @endforeach
                            </select>
                            @error('estado') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Dados do Representante Legal --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-base font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-100">
                    Dados do Representante Legal
                </h2>
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label for="resp_nome" class="block text-sm font-medium text-gray-700 mb-1">Nome Completo *</label>
                        <input id="resp_nome" name="resp_nome" type="text" required
                               value="{{ old('resp_nome') }}"
                               class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm @error('resp_nome') border-red-300 @enderror">
                        @error('resp_nome') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="cpf" class="block text-sm font-medium text-gray-700 mb-1">CPF *</label>
                            <input id="cpf" name="cpf" type="text" required
                                   value="{{ old('cpf') }}" placeholder="000.000.000-00"
                                   class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm @error('cpf') border-red-300 @enderror">
                            @error('cpf') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="resp_phone" class="block text-sm font-medium text-gray-700 mb-1">Telefone</label>
                            <input id="resp_phone" name="resp_phone" type="text"
                                   value="{{ old('resp_phone') }}" placeholder="(00) 00000-0000"
                                   class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm">
                        </div>
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-mail (será seu login) *</label>
                        <input id="email" name="email" type="email" required
                               value="{{ old('email') }}"
                               class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm @error('email') border-red-300 @enderror">
                        @error('email') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Senha *</label>
                            <input id="password" name="password" type="password" required
                                   class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm @error('password') border-red-300 @enderror">
                            @error('password') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirmar Senha *</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" required
                                   class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-brand-500 focus:border-brand-500 text-sm">
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <a href="{{ route('portal.index') }}" class="text-sm text-gray-500 hover:text-gray-800">Cancelar</a>
                <button type="submit"
                        class="btn btn-primary">
                    Criar Cadastro e Entrar
                </button>
            </div>
        </form>
    </div>
</x-portal-layout>

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Completa o cadastro da OSC (Módulo 1.2): dados básicos que faltavam
 * (data de abertura do CNPJ, CNAE primário/secundário), endereço do
 * representante legal e os anexos exigidos (cartão CNPJ; CPF, comprovante
 * de endereço e ata da diretoria do representante).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oscs', function (Blueprint $table) {
            // Dados básicos
            $table->date('data_abertura')->nullable()->after('cnpj');
            $table->string('cnae_primario')->nullable()->after('data_abertura');
            $table->string('cnae_secundario')->nullable()->after('cnae_primario');

            // Endereço do representante legal
            $table->string('resp_cep', 9)->nullable()->after('resp_phone');
            $table->string('resp_logradouro')->nullable()->after('resp_cep');
            $table->string('resp_numero', 20)->nullable()->after('resp_logradouro');
            $table->string('resp_complemento', 100)->nullable()->after('resp_numero');
            $table->string('resp_bairro', 100)->nullable()->after('resp_complemento');
            $table->string('resp_cidade', 100)->nullable()->after('resp_bairro');
            $table->string('resp_estado', 2)->nullable()->after('resp_cidade');

            // Anexos (caminhos no disco privado 'local')
            $table->string('anexo_cartao_cnpj')->nullable()->after('resp_estado');
            $table->string('resp_anexo_cpf')->nullable()->after('anexo_cartao_cnpj');
            $table->string('resp_anexo_comprovante')->nullable()->after('resp_anexo_cpf');
            $table->string('resp_anexo_ata')->nullable()->after('resp_anexo_comprovante');
        });
    }

    public function down(): void
    {
        Schema::table('oscs', function (Blueprint $table) {
            $table->dropColumn([
                'data_abertura', 'cnae_primario', 'cnae_secundario',
                'resp_cep', 'resp_logradouro', 'resp_numero', 'resp_complemento',
                'resp_bairro', 'resp_cidade', 'resp_estado',
                'anexo_cartao_cnpj', 'resp_anexo_cpf', 'resp_anexo_comprovante', 'resp_anexo_ata',
            ]);
        });
    }
};

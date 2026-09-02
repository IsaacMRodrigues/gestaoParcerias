<?php

namespace Database\Seeders;

use App\Models\Orgao;
use Illuminate\Database\Seeder;

class UnidadesGestorasSeeder extends Seeder
{
    /**
     * Quadro padrão de Unidades Gestoras do município (código => nome).
     * O código entra na composição do número do processo (UG.Seq.Ano.Esfera).
     */
    public const UNIDADES = [
        '0201' => 'Governo',
        '0202' => 'Jurídico',
        '0203' => 'Administração',
        '0204' => 'Fazenda',
        '0205' => 'Obras',
        '0206' => 'Saúde',
        '0207' => 'Educação',
        '0208' => 'Esportes e Lazer',
        '0209' => 'Trabalho e Desen. Social',
        '0210' => 'Meio Ambiente',
        '0211' => 'Agricultura',
        '0212' => 'Fundo Mun. Assis. Social',
        '0213' => 'Fundo Mun. Crian. e Adolesc.',
        '0214' => 'Planejamento',
        '0215' => 'Serviços Urbanos',
        '0216' => 'Cultura e Turismo',
        '0217' => 'Desen. Econômico',
        '0218' => 'Controladoria',
        '0219' => 'Juventude',
        '0221' => 'Ouvidoria',
        '0222' => 'Fundo Mun. Idoso',
        '0223' => 'Gabinete',
        '0224' => 'Gestão de Pessoa',
        '0225' => 'Transporte',
        '0226' => 'Fundo Mun. Pessoa com Def',
        '0227' => 'Ciência e Tecnologia',
    ];

    public function run(): void
    {
        foreach (self::UNIDADES as $codigo => $nome) {
            Orgao::firstOrCreate(
                ['codigo' => $codigo],
                ['name' => $nome, 'status' => true],
            );
        }
    }
}

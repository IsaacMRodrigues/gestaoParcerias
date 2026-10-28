<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Hora Legal Brasileira para o carimbo do tempo: pergunta aos servidores de estrato 1 do NTP.br, que o
 * Observatório Nacional sincroniza, e corrige o relógio do servidor pelo desvio medido. Se nenhum responder,
 * vale o relógio do servidor, e o carimbo registra que não houve fonte oficial.
 */
class HoraLegal
{
    public const FUSO = 'America/Sao_Paulo';

    /** Segundos entre 1900, a época do NTP, e 1970, a do Unix. */
    private const EPOCA_NTP = 2208988800;

    public function agora(): CarimboDoTempo
    {
        foreach ((array) config('services.hora_legal.servidores', []) as $servidor) {
            $medida = $this->consultar($servidor, (float) config('services.hora_legal.timeout', 1.0));

            if ($medida !== null) {
                [$instante, $desvio] = $medida;

                return new CarimboDoTempo(self::doUnix($instante), $servidor, (int) round($desvio * 1000));
            }
        }

        if (!empty(config('services.hora_legal.servidores'))) {
            Log::warning('Hora legal indisponível: carimbo do tempo pelo relógio do servidor.');
        }

        return new CarimboDoTempo(self::doUnix(microtime(true)));
    }

    /** A data e hora em Brasília, para exibir: o sistema guarda em UTC. */
    public static function emBrasilia(?CarbonInterface $em): ?Carbon
    {
        return $em === null ? null : Carbon::instance($em)->setTimezone(self::FUSO);
    }

    /**
     * Uma consulta SNTP (RFC 4330). Devolve [instante corrigido em segundos Unix, desvio do relógio local em
     * segundos] ou null quando o servidor não responde ou a resposta não serve.
     *
     * @return array{0: float, 1: float}|null
     */
    protected function consultar(string $servidor, float $timeout): ?array
    {
        $socket = @stream_socket_client('udp://' . $servidor . ':123', $erro, $mensagem, $timeout);
        if (!$socket) {
            return null;
        }

        stream_set_timeout($socket, (int) $timeout, (int) (fmod($timeout, 1) * 1_000_000));

        // LI 0, versão 4, modo 3 (cliente); o instante de envio vai no campo "transmit" e o servidor o devolve
        // em "originate": é assim que se sabe que a resposta é desta pergunta.
        $t1 = microtime(true);
        $enviado = self::paraNtp($t1);
        @fwrite($socket, chr(0x23) . str_repeat("\0", 39) . $enviado);
        $resposta = @fread($socket, 48);
        $t4 = microtime(true);
        fclose($socket);

        return is_string($resposta) ? self::interpretar($resposta, $enviado, $t1, $t4) : null;
    }

    /**
     * Lê a resposta do servidor: [instante corrigido, desvio], ou null se ela não serve.
     *
     * @return array{0: float, 1: float}|null
     */
    public static function interpretar(string $resposta, string $enviado, float $t1, float $t4): ?array
    {
        if (strlen($resposta) < 48 || substr($resposta, 24, 8) !== $enviado) {
            return null;
        }

        $cabecalho = ord($resposta[0]);
        $estrato = ord($resposta[1]);
        // LI 3 = relógio do servidor não sincronizado; modo 4 = resposta de servidor; estrato 0 = recusa.
        if (($cabecalho >> 6) === 3 || ($cabecalho & 0x07) !== 4 || $estrato < 1 || $estrato > 15) {
            return null;
        }

        $t2 = self::doNtp(substr($resposta, 32, 8));
        $t3 = self::doNtp(substr($resposta, 40, 8));
        $desvio = (($t2 - $t1) + ($t3 - $t4)) / 2;

        return [$t4 + $desvio, $desvio];
    }

    private static function doUnix(float $segundos): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('U.u', sprintf('%.6F', $segundos), 'UTC');
    }

    public static function paraNtp(float $unix): string
    {
        $segundos = (int) floor($unix);

        return pack('NN', ($segundos + self::EPOCA_NTP) & 0xFFFFFFFF, (int) (($unix - $segundos) * 4294967296));
    }

    private static function doNtp(string $bytes): float
    {
        ['s' => $segundos, 'f' => $fracao] = unpack('Ns/Nf', $bytes);

        // Depois de 2036 o contador de 32 bits recomeça (era 1).
        if ($segundos < 0x80000000) {
            $segundos += 0x100000000;
        }

        return $segundos - self::EPOCA_NTP + $fracao / 4294967296;
    }
}

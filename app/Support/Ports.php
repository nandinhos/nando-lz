<?php

namespace App\Support;

/**
 * Detecção de portas para evitar conflitos (com banco, sistema e outros
 * serviços) ao expor a aplicação — especialmente no modo Docker.
 *
 * A verificação é feita tentando abrir um socket na porta: se o bind falha,
 * algo já a está usando. É o teste mais fiel ao que o `docker compose up` vai
 * enfrentar, e não depende de ferramentas externas (ss/netstat/lsof).
 */
class Ports
{
    /** Faixa "alta" preferida para serviços do projeto (evita well-known/registered baixos). */
    public const DEFAULT_PREFERRED = 18000;

    public const RANGE_END = 65535;

    /** Uma porta está livre se conseguimos fazer bind de TCP nela em 0.0.0.0. */
    public static function isFree(int $port): bool
    {
        if ($port < 1 || $port > self::RANGE_END) {
            return false;
        }

        $socket = @stream_socket_server("tcp://0.0.0.0:{$port}", $errno, $errstr);
        if ($socket === false) {
            return false;
        }

        fclose($socket);

        return true;
    }

    /**
     * Sugere uma porta ALTA livre a partir de `$preferred`, procurando para cima.
     * Retorna a própria `$preferred` se já estiver livre; null se nada livre até o fim.
     */
    public static function suggest(int $preferred = self::DEFAULT_PREFERRED, int $max = self::RANGE_END): ?int
    {
        $preferred = max($preferred, 1024); // nunca sugere porta privilegiada

        for ($port = $preferred; $port <= $max; $port++) {
            if (self::isFree($port)) {
                return $port;
            }
        }

        return null;
    }
}

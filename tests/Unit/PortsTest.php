<?php

use App\Support\Ports;

it('detecta porta ocupada e sugere outra livre', function () {
    $port = Ports::suggest(19500);
    expect($port)->not->toBeNull()
        ->and(Ports::isFree($port))->toBeTrue();

    // Ocupa a porta e confirma a detecção + o salto para a próxima livre.
    $socket = stream_socket_server("tcp://0.0.0.0:{$port}", $errno, $errstr);
    expect($socket)->not->toBeFalse();

    try {
        expect(Ports::isFree($port))->toBeFalse();

        $next = Ports::suggest($port);
        expect($next)->not->toBeNull()
            ->and($next)->toBeGreaterThan($port)
            ->and(Ports::isFree($next))->toBeTrue();
    } finally {
        fclose($socket);
    }
});

it('nunca sugere porta privilegiada nem aceita fora de faixa', function () {
    expect(Ports::isFree(0))->toBeFalse()
        ->and(Ports::isFree(70000))->toBeFalse()
        ->and(Ports::suggest(80))->toBeGreaterThanOrEqual(1024);
});

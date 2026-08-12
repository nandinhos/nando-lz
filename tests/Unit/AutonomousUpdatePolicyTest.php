<?php

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/** @param array<string, string> $fixture */
function assertAutonomousUpdate(array $fixture): Process
{
    $repository = sys_get_temp_dir().'/nando-lz-autonomy-'.bin2hex(random_bytes(8));
    mkdir($repository, 0755, true);

    $run = static function (array $command) use ($repository): Process {
        $process = new Process($command, $repository);
        $process->mustRun();

        return $process;
    };

    $run(['git', 'init', '--initial-branch=main']);
    $run(['git', 'config', 'user.name', 'test']);
    $run(['git', 'config', 'user.email', 'test@example.com']);
    file_put_contents($repository.'/composer.lock', '{"content-hash":"base","packages":[]}');
    file_put_contents($repository.'/README.md', '# Base');
    $run(['git', 'add', '.']);
    $run(['git', 'commit', '-m', 'base']);
    $run(['git', 'switch', '-c', 'candidate']);

    foreach ($fixture as $path => $contents) {
        $target = $repository.'/'.$path;
        if (! is_dir(dirname($target))) {
            mkdir(dirname($target), 0755, true);
        }
        file_put_contents($target, $contents);
    }

    $run(['git', 'add', '.']);
    $run(['git', 'commit', '-m', 'candidate']);

    $process = new Process([
        base_path('scripts/assert-autonomous-update.sh'),
        'main',
        'candidate',
    ], $repository);
    $process->run();

    (new Filesystem)->deleteDirectory($repository);

    return $process;
}

it('aceita apenas lock files, README e relatório verde', function () {
    $result = assertAutonomousUpdate([
        'composer.lock' => '{"content-hash":"base","packages":[{"name":"safe/package"}]}',
        'docs/reports/auto-update/2026-08-11.md' => "# Relatório\n\n- Resultado geral: ✅ verde\n",
    ]);

    expect($result->getExitCode())->toBe(0)
        ->and($result->getOutput())->toContain('Candidato autônomo validado');
});

it('rejeita manifest ou código junto de atualização de dependência', function (string $path) {
    $result = assertAutonomousUpdate([
        'composer.lock' => '{"content-hash":"base","packages":[{"name":"safe/package"}]}',
        'docs/reports/auto-update/2026-08-11.md' => "# Relatório\n\n- Resultado geral: ✅ verde\n",
        $path => 'fora do escopo',
    ]);

    expect($result->getExitCode())->toBe(1)
        ->and($result->getErrorOutput())->toContain('fora do escopo');
})->with(['composer.json', 'app/Models/User.php', '.github/workflows/ci.yml']);

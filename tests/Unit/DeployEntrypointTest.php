<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

function runUploadAssetsFixture(callable $buildTree): Process
{
    $baseDir = sys_get_temp_dir().'/nando-lz-upload-'.bin2hex(random_bytes(8));
    $workDir = $baseDir.'/work';
    $assetsDir = $baseDir.'/assets';
    $tarPath = $baseDir.'/payload.tar.gz';

    mkdir($workDir, 0755, true);
    mkdir($assetsDir, 0755, true);

    try {
        $tarArgs = $buildTree($workDir);
        $tar = new Process(array_merge(['tar', '-czf', $tarPath, '-C', $workDir], $tarArgs));
        $tar->mustRun();

        $sha = str_repeat('a', 40);
        $process = new Process(
            ['bash', base_path('scripts/deploy-entrypoint.sh')],
            $baseDir,
            [
                'SSH_ORIGINAL_COMMAND' => "upload-assets {$sha}",
                'NANDOLZ_ASSETS_DIR' => $assetsDir,
            ],
            file_get_contents($tarPath) ?: ''
        );
        $process->run();

        return $process;
    } finally {
        (new Filesystem)->deleteDirectory($baseDir);
    }
}

it('aceita pacote de assets válido contendo apenas public/build e manifest.json', function () {
    $result = runUploadAssetsFixture(function (string $workDir): array {
        mkdir($workDir.'/public/build/assets', 0755, true);
        file_put_contents($workDir.'/public/build/manifest.json', '{}');
        file_put_contents($workDir.'/public/build/assets/app.css', 'body{}');

        return ['public/build'];
    });

    expect($result->getExitCode())->toBe(0)
        ->and($result->getOutput())->toContain('Assets recebidos');
});

it('rejeita pacote de assets com arquivo fora de public/build', function () {
    $result = runUploadAssetsFixture(function (string $workDir): array {
        mkdir($workDir.'/public/build', 0755, true);
        mkdir($workDir.'/app', 0755, true);
        file_put_contents($workDir.'/public/build/manifest.json', '{}');
        file_put_contents($workDir.'/app/unexpected.txt', 'evil');

        return ['public/build', 'app/unexpected.txt'];
    });

    expect($result->getExitCode())->toBe(68)
        ->and($result->getErrorOutput())->toContain('entrada fora de public/build/');
});

it('rejeita pacote de assets sem public/build/manifest.json', function () {
    $result = runUploadAssetsFixture(function (string $workDir): array {
        mkdir($workDir.'/public/build/assets', 0755, true);
        file_put_contents($workDir.'/public/build/assets/app.css', 'body{}');

        return ['public/build'];
    });

    expect($result->getExitCode())->toBe(68)
        ->and($result->getErrorOutput())->toContain('public/build/manifest.json');
});

it('rejeita pacote de assets contendo link simbólico', function () {
    $result = runUploadAssetsFixture(function (string $workDir): array {
        mkdir($workDir.'/public/build', 0755, true);
        file_put_contents($workDir.'/public/build/manifest.json', '{}');
        symlink('/etc/passwd', $workDir.'/public/build/leak');

        return ['public/build'];
    });

    expect($result->getExitCode())->toBe(68)
        ->and($result->getErrorOutput())->toContain('tipo de entrada não permitido (l)');
});

it('rejeita pacote de assets contendo hardlink', function () {
    $result = runUploadAssetsFixture(function (string $workDir): array {
        mkdir($workDir.'/public/build', 0755, true);
        file_put_contents($workDir.'/public/build/manifest.json', '{}');
        link($workDir.'/public/build/manifest.json', $workDir.'/public/build/hardlink.json');

        return ['public/build'];
    });

    expect($result->getExitCode())->toBe(68)
        ->and($result->getErrorOutput())->toContain('tipo de entrada não permitido (h)');
});

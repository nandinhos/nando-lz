<?php

declare(strict_types=1);

/**
 * Classifica atualizações de lockfile e gera resumo markdown para o relatório
 * de manutenção automática, distinguindo pacotes principais de majors
 * transitivas e apontando os motivos reais de classificação REVIEW.
 */
final class UpdateClassifier
{
    /** @var list<string> */
    private const PRIMARY_PACKAGES = [
        'laravel/framework',
        'filament/filament',
        'livewire/livewire',
        'pestphp/pest',
    ];

    /** @var list<string> */
    private const AUTONOMOUS_PATHS = [
        'composer.lock',
        'package-lock.json',
        'README.md',
    ];

    /**
     * @param  array<string, string>  $oldVersions
     * @param  array<string, string>  $newVersions
     * @param  list<string>  $changedFiles
     * @return array{
     *     primary_updates: list<string>,
     *     transitive_majors: list<string>,
     *     classification: string,
     *     review_reasons: list<string>
     * }
     */
    public static function analyze(
        array $oldVersions,
        array $newVersions,
        array $changedFiles,
        bool $gatesFailed = false
    ): array {
        $primaryUpdates = [];
        $primaryMajors = [];
        $transitiveMajors = [];

        foreach ($newVersions as $name => $newVersion) {
            if (! isset($oldVersions[$name])) {
                continue;
            }
            $oldVersion = $oldVersions[$name];
            if ($oldVersion === $newVersion) {
                continue;
            }

            $bumpType = self::bumpType($oldVersion, $newVersion);
            if (in_array($name, self::PRIMARY_PACKAGES, true)) {
                $primaryUpdates[] = sprintf('`%s`: `%s` → `%s` (%s)', $name, $oldVersion, $newVersion, $bumpType);
                if ($bumpType === 'major') {
                    $primaryMajors[] = sprintf('`%s`: `%s` → `%s`', $name, $oldVersion, $newVersion);
                }
            } elseif ($bumpType === 'major') {
                $transitiveMajors[] = sprintf('`%s`: `%s` → `%s`', $name, $oldVersion, $newVersion);
            }
        }

        $reviewReasons = [];
        if ($gatesFailed) {
            $reviewReasons[] = 'Um ou mais gates de validação falharam durante o ciclo.';
        }

        $manifestChanged = array_values(array_filter(
            $changedFiles,
            static fn (string $file): bool => in_array($file, ['composer.json', 'package.json'], true)
        ));
        if ($manifestChanged !== []) {
            $reviewReasons[] = 'Manifesto alterado (`'.implode('`, `', $manifestChanged).'`).';
        }

        $outOfScope = array_values(array_filter(
            $changedFiles,
            static fn (string $file): bool => ! self::isAutonomousPath($file)
        ));
        if ($outOfScope !== []) {
            $sample = array_slice($outOfScope, 0, 5);
            $extra = count($outOfScope) > 5 ? sprintf(' (+%d arquivos)', count($outOfScope) - 5) : '';
            $reviewReasons[] = sprintf(
                'Arquivos fora do escopo autônomo (%d): `%s`%s.',
                count($outOfScope),
                implode('`, `', $sample),
                $extra
            );
        }

        if ($primaryMajors !== []) {
            $reviewReasons[] = sprintf(
                'Atualização major em %d pacote(s) principal(is): %s.',
                count($primaryMajors),
                implode(', ', $primaryMajors)
            );
        }

        if ($transitiveMajors !== []) {
            $reviewReasons[] = sprintf(
                'Atualização major em %d dependência(s) transitiva(s) no `composer.lock`.',
                count($transitiveMajors)
            );
        }

        $classification = $reviewReasons === [] ? 'AUTO' : 'REVIEW';

        return [
            'primary_updates' => $primaryUpdates,
            'transitive_majors' => $transitiveMajors,
            'classification' => $classification,
            'review_reasons' => $reviewReasons,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function readLockVersions(string $lockPath): array
    {
        if (! is_file($lockPath)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($lockPath), true);
        if (! is_array($decoded)) {
            return [];
        }

        $versions = [];
        foreach (array_merge($decoded['packages'] ?? [], $decoded['packages-dev'] ?? []) as $package) {
            if (is_array($package) && isset($package['name'], $package['version'])) {
                $versions[(string) $package['name']] = (string) $package['version'];
            }
        }

        ksort($versions);

        return $versions;
    }

    public static function bumpType(string $oldVersion, string $newVersion): string
    {
        $oldParts = self::parseSemver($oldVersion);
        $newParts = self::parseSemver($newVersion);

        if ($oldParts === null || $newParts === null) {
            return 'update';
        }

        if ($newParts[0] > $oldParts[0]) {
            return 'major';
        }
        if ($newParts[1] > $oldParts[1]) {
            return 'minor';
        }

        return 'patch';
    }

    /**
     * @return array{int, int, int}|null
     */
    private static function parseSemver(string $version): ?array
    {
        if (preg_match('/^v?(\d+)\.(\d+)\.(\d+)/', $version, $matches) !== 1) {
            return null;
        }

        return [(int) $matches[1], (int) $matches[2], (int) $matches[3]];
    }

    private static function isAutonomousPath(string $path): bool
    {
        if (in_array($path, self::AUTONOMOUS_PATHS, true)) {
            return true;
        }

        return preg_match('#^docs/reports/auto-update/\d{4}-\d{2}-\d{2}\.md$#', $path) === 1;
    }

    public static function formatMarkdown(array $analysis): string
    {
        $lines = [];
        $lines[] = '## Classificação (§7.2)';
        $lines[] = '';
        $lines[] = sprintf('- Classificação efetiva: **%s**', $analysis['classification']);

        if ($analysis['primary_updates'] === []) {
            $lines[] = '- Pacotes principais: sem alterações.';
        } else {
            $lines[] = '- Pacotes principais atualizados:';
            foreach ($analysis['primary_updates'] as $item) {
                $lines[] = '  - '.$item;
            }
        }

        if ($analysis['transitive_majors'] === []) {
            $lines[] = '- Majors transitivas (`composer.lock`): nenhuma.';
        } else {
            $lines[] = '- Majors transitivas (`composer.lock`):';
            foreach ($analysis['transitive_majors'] as $item) {
                $lines[] = '  - '.$item;
            }
        }

        if ($analysis['review_reasons'] !== []) {
            $lines[] = '- Motivo(s) da classificação **REVIEW**:';
            foreach ($analysis['review_reasons'] as $reason) {
                $lines[] = '  - '.$reason;
            }
        } else {
            $lines[] = '- Motivo: apenas atualizações de lockfile/documentação dentro do escopo autônomo.';
        }

        return implode("\n", $lines);
    }
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $oldLock = $argv[1] ?? '';
    $newLock = $argv[2] ?? 'composer.lock';
    $failedFlag = ($argv[3] ?? '0') === '1';
    $changedArg = $argv[4] ?? '';
    $changedRaw = $changedArg === '-' ? (string) stream_get_contents(STDIN) : str_replace(['\r\n', '\n'], "\n", $changedArg);

    $changedFiles = array_values(array_filter(
        array_map('trim', explode("\n", $changedRaw)),
        static fn (string $line): bool => $line !== ''
    ));

    $analysis = UpdateClassifier::analyze(
        UpdateClassifier::readLockVersions($oldLock),
        UpdateClassifier::readLockVersions($newLock),
        $changedFiles,
        $failedFlag
    );

    echo UpdateClassifier::formatMarkdown($analysis)."\n";
}

<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Snapshot da stack instalada para a landing (welcome).
 *
 * Fonte de verdade: composer.lock (versões realmente instaladas — nunca
 * hardcode), CHANGELOG.md (release do starter) e docs/reports/auto-update/
 * (última atualização aplicada pelo ciclo de manutenção).
 *
 * Tudo degrada graciosamente: arquivo ausente ou banco fora do ar viram
 * null — a welcome renderiza sempre.
 */
class Stack
{
    /** @return array{name: string, githubUrl: ?string, versions: array<string, ?string>, release: ?string, build: string, lastUpdate: ?array{date: string, ok: ?bool, path: string}} */
    public static function snapshot(): array
    {
        $githubUrl = rtrim((string) config('app.github_url', 'https://github.com/nandinhos/nando-lz'), '/');

        return [
            'name' => (string) config('app.name', 'Laravel'),
            'githubUrl' => $githubUrl !== '' ? $githubUrl : null,
            'versions' => [
                'Laravel' => static::locked('laravel/framework'),
                'Filament' => static::locked('filament/filament'),
                'Livewire' => static::locked('livewire/livewire'),
                'Pest' => static::locked('pestphp/pest'),
                'PHP' => PHP_VERSION,
                'PostgreSQL' => static::postgres(),
            ],
            'release' => static::release(),
            'build' => Build::id(),
            'lastUpdate' => static::lastUpdate(),
        ];
    }

    public static function isStarter(): bool
    {
        return (string) config('app.name', 'Laravel') === 'nando-lz';
    }

    protected static function locked(string $package): ?string
    {
        static $packages = null;

        if ($packages === null) {
            $file = base_path('composer.lock');
            $lock = is_file($file) ? json_decode((string) file_get_contents($file), true) : [];
            $packages = [];
            foreach (array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []) as $p) {
                $packages[$p['name']] = ltrim($p['version'], 'v');
            }
        }

        return $packages[$package] ?? null;
    }

    protected static function postgres(): ?string
    {
        try {
            // Só o número: "16.14 (Debian 16.14-1...)" → "16.14"
            return preg_replace('/\s.*/', '', DB::selectOne("select current_setting('server_version') as v")->v);
        } catch (\Throwable) {
            return null; // banco fora do ar não pode derrubar a welcome
        }
    }

    /** Última release do starter, lida do CHANGELOG (ex.: "1.1.0"). */
    protected static function release(): ?string
    {
        $file = base_path('CHANGELOG.md');
        if (! is_file($file) || ! preg_match('/^## \[(\d+\.\d+\.\d+)\]/m', (string) file_get_contents($file), $m)) {
            return null;
        }

        return $m[1];
    }

    /** Relatório mais recente do ciclo de manutenção (§9). */
    protected static function lastUpdate(): ?array
    {
        $reports = glob(base_path('docs/reports/auto-update/*.md')) ?: [];
        if ($reports === []) {
            return null;
        }

        sort($reports); // nomes YYYY-MM-DD.md ordenam cronologicamente
        $latest = end($reports);
        $body = (string) file_get_contents($latest);

        return [
            'date' => basename($latest, '.md'),
            // null = relatório sem veredito legível (formato inesperado)
            'ok' => str_contains($body, '✅') ? true : (str_contains($body, '❌') ? false : null),
            'path' => 'docs/reports/auto-update/'.basename($latest),
        ];
    }
}

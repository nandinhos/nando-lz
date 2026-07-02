<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Mantém o README **exato** com a stack realmente instalada.
 *
 * Fonte única de verdade:
 *  - Laravel/Filament/Livewire/Pest → composer.lock (versão instalada)
 *  - PHP → constraint em composer.json (require.php)
 *  - PostgreSQL → tag da imagem em docker-compose.yml
 *  - Node → node-version em .github/workflows/ci.yml
 *
 * A landing e o monitor já leem do composer.lock em runtime; este comando faz o
 * README seguir a mesma verdade. `--check` falha (sem escrever) se houver drift
 * — é o gate que garante que os números "sempre batem exato" no fluxo de update.
 */
#[Signature('stack:sync {--check : Falha se o README estiver dessincronizado, sem escrever}')]
#[Description('Sincroniza os badges e a tabela de stack do README com a stack instalada.')]
class SyncStackDocs extends Command
{
    public function handle(): int
    {
        $readme = base_path('README.md');
        if (! is_file($readme)) {
            $this->error('README.md não encontrado.');

            return self::FAILURE;
        }

        $content = (string) file_get_contents($readme);
        $new = $this->replaceBlock($content, 'stack:badges', $this->badgesBlock());
        $new = $this->replaceBlock($new, 'stack:table', $this->tableBlock());

        if ($this->option('check')) {
            if ($new !== $content) {
                $this->error('README fora de sincronia com a stack instalada. Rode: php artisan stack:sync');

                return self::FAILURE;
            }
            $this->info('README bate exato com a stack instalada. ✓');

            return self::SUCCESS;
        }

        if ($new !== $content) {
            file_put_contents($readme, $new);
            $this->info('README sincronizado com a stack.');
        } else {
            $this->info('README já estava em sincronia.');
        }

        return self::SUCCESS;
    }

    private function replaceBlock(string $content, string $marker, string $body): string
    {
        $m = preg_quote($marker, '/');
        $pattern = '/(<!-- '.$m.':start -->\n).*?(<!-- '.$m.':end -->)/s';

        return preg_replace_callback($pattern, fn ($mt) => $mt[1].$body."\n".$mt[2], $content) ?? $content;
    }

    private function badgesBlock(): string
    {
        $laravel = $this->minor($this->locked('laravel/framework'));
        $filament = $this->minor($this->locked('filament/filament'));
        $php = $this->composerPhp();
        $pg = $this->composeImageTag('postgres');

        return implode("\n", [
            "![Laravel](https://img.shields.io/badge/Laravel-{$laravel}-FF2D20?logo=laravel)",
            "![Filament](https://img.shields.io/badge/Filament-{$filament}-FFAA00)",
            '![PHP](https://img.shields.io/badge/PHP-'.rawurlencode($php).'-777BB4?logo=php)',
            "![PostgreSQL](https://img.shields.io/badge/PostgreSQL-{$pg}-4169E1?logo=postgresql)",
        ]);
    }

    private function tableBlock(): string
    {
        $rows = [
            ['Laravel', $this->locked('laravel/framework'), 'major derivada do Filament'],
            ['Filament', $this->locked('filament/filament'), 'pacote limitante'],
            ['Livewire', $this->locked('livewire/livewire'), 'transitivo via Filament — **nunca fixar direto**'],
            ['Pest', $this->locked('pestphp/pest'), 'framework único de testes'],
            ['PHP', '`'.$this->composerPhp().'`', 'piso; validado em 8.3 e 8.4 no CI (o Docker usa 8.4)'],
            ['PostgreSQL', $this->composeImageTag('postgres'), 'banco padrão; pgvector opcional'],
            ['Node', $this->ciNode(), 'build de assets'],
        ];

        $lines = ['| Componente | Versão | Observação |', '|-----------|--------|------------|'];
        foreach ($rows as [$c, $v, $o]) {
            $lines[] = "| {$c} | {$v} | {$o} |";
        }

        return implode("\n", $lines);
    }

    private function locked(string $package): string
    {
        static $packages = null;
        if ($packages === null) {
            $lock = json_decode((string) file_get_contents(base_path('composer.lock')), true) ?: [];
            $packages = [];
            foreach (array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []) as $p) {
                $packages[$p['name']] = ltrim($p['version'], 'v');
            }
        }

        return $packages[$package] ?? '?';
    }

    /** "13.18.0" → "13.18" para os badges. */
    private function minor(string $version): string
    {
        return preg_match('/^(\d+\.\d+)/', $version, $m) ? $m[1] : $version;
    }

    private function composerPhp(): string
    {
        $data = json_decode((string) file_get_contents(base_path('composer.json')), true);

        return $data['require']['php'] ?? '^8.3';
    }

    private function composeImageTag(string $image): string
    {
        $file = base_path('docker-compose.yml');
        if (is_file($file) && preg_match('/'.preg_quote($image, '/').':(\S+)/', (string) file_get_contents($file), $m)) {
            return trim($m[1]);
        }

        return '16';
    }

    private function ciNode(): string
    {
        $file = base_path('.github/workflows/ci.yml');
        if (is_file($file) && preg_match('/node-version:\s*[\'"]?(\d+)/', (string) file_get_contents($file), $m)) {
            return $m[1];
        }

        return '22';
    }
}

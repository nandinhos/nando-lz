<?php

namespace App\Console\Commands;

use App\Support\Ports;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;
use function Laravel\Prompts\warning;

/**
 * Wizard de personalização do starter (rebrand) para quem clona o nando-lz
 * para começar um projeto novo.
 *
 * - Renomeia identidade: pacote Composer, APP_NAME, banco de dados, URL do repo
 *   porta pública e todas as referências textuais ao starter.
 * - Sugere uma porta ALTA livre (detecta o que está ativo) para não conflitar.
 * - Separa os papéis: a automação de manutenção é do MANTENEDOR do nando-lz;
 *   num projeto novo o wizard a desanexa (o CI permanece).
 * - Reversível: por padrão as mudanças ficam no working tree (revira com
 *   `git restore .`). O reset do histórico é opt-in e avisado.
 *
 * Preview: `--preview` mostra o plano e não altera nada.
 * Não-interativo (CI/testes): passe as opções + --no-interaction.
 */
#[Signature('app:setup
    {--name= : Nome da aplicação (humano)}
    {--package= : Pacote Composer no formato autor/nome}
    {--db= : Nome do banco de dados}
    {--url= : URL do repositório (http/https); use vazio para definir depois}
    {--port= : Porta pública (Docker)}
    {--reset-git : Resetar o histórico git (irreversível)}
    {--preview : Mostrar o plano sem alterar nada}
    {--force : Ignorar a guarda de "já personalizado"}')]
#[Description('Personaliza o starter (rebrand), sugere porta livre e desanexa a automação do starter.')]
class SetupProject extends Command
{
    private const STARTER_PACKAGE = 'nandinhos/nando-lz';

    public function handle(): int
    {
        $interactive = $this->input->isInteractive();
        $preview = (bool) $this->option('preview');

        intro('nando-lz · setup do projeto'.($preview ? ' (preview)' : ''));

        if (! $this->option('force') && $this->currentPackage() !== self::STARTER_PACKAGE) {
            note('Este projeto já parece personalizado ('.$this->currentPackage().'). Use --force para rodar mesmo assim.');

            return self::SUCCESS;
        }

        // Identidade (opção da CLI vence; senão prompt; senão default).
        $appName = $this->option('name') ?: ($interactive
            ? text('Nome da aplicação', placeholder: 'Acme CRM', required: true)
            : 'App');

        $slug = Str::slug($appName);
        $db = $this->option('db') ?: ($interactive
            ? text('Banco de dados', default: str_replace('-', '_', $slug), validate: fn ($v) => preg_match('/^[a-z_][a-z0-9_]*$/', $v) ? null : 'Use apenas letras minúsculas, números e _ (começando por letra ou _).')
            : str_replace('-', '_', $slug));

        $packageDefault = $slug.'/'.$slug;
        $package = $this->option('package') ?: ($interactive
            ? text(
                'Pacote Composer (vendor/nome)',
                default: $packageDefault,
                validate: fn ($v) => preg_match('#^[a-z0-9]([_.-]?[a-z0-9]+)*/[a-z0-9]([_.-]?[a-z0-9]+)*$#', $v) ? null : 'Formato inválido — use vendor/nome em minúsculas.',
                hint: '"vendor" é autor/organização, não a pasta vendor/.'
            )
            : $packageDefault);

        $vendor = explode('/', $package)[0];

        $urlOption = $this->option('url');
        $url = is_string($urlOption) ? $urlOption : ($interactive
            ? (confirm('Já existe repositório Git remoto?', default: false)
                ? text('URL do repositório', default: 'https://github.com/'.$package, validate: fn ($v) => str_starts_with($v, 'http') ? null : 'Informe uma URL http(s) válida.')
                : '')
            : 'https://github.com/'.$package);
        $url = rtrim($url, '/');
        if ($url !== '' && ! str_starts_with($url, 'http')) {
            $this->error('Informe uma URL http(s) válida ou deixe --url vazio para definir depois.');

            return self::FAILURE;
        }

        // Porta ALTA livre — detecta o que está ativo (banco, sistema, serviços).
        $suggested = Ports::suggest($this->currentPort()) ?? $this->currentPort();
        $port = (int) ($this->option('port') ?: ($interactive
            ? text('Porta pública (Docker)', default: (string) $suggested, hint: 'sugerida por estar livre agora', validate: fn ($v) => preg_match('/^\d+$/', $v) && (int) $v >= 1024 && (int) $v <= 65535 ? null : 'Use uma porta entre 1024 e 65535.')
            : $suggested));

        if (! Ports::isFree($port)) {
            warning("A porta {$port} parece ocupada agora — pode conflitar no `docker compose up`.");
        }

        $resetGit = (bool) $this->option('reset-git') || ($interactive && confirm(
            label: 'Resetar o histórico git?',
            default: false,
            hint: 'torna a personalização IRREVERSÍVEL via git',
        ));

        // Precedência resolvida pelo strtr (chaves mais longas primeiro, sem reprocessar).
        $repoReplacement = $url !== '' ? $url : 'https://github.com/'.$package;
        $tokens = [
            'https://github.com/nandinhos/nando-lz' => $repoReplacement,
            'nandinhos/nando-lz' => $package,
            'nando_lz_testing' => $db.'_testing',
            'nando_lz' => $db,
            'nando-lz' => $slug,
            'nandinhos' => $vendor,
        ];

        $changed = $this->processFiles($tokens, apply: false);
        $willRemove = $this->maintenancePaths();
        $existingRemovals = array_values(array_filter($willRemove, fn ($p) => is_file(base_path($p))));

        // ---- Preview do plano ----
        table(['Campo', 'Valor'], [
            ['Nome', $appName],
            ['Pacote', $package],
            ['Banco', $db],
            ['Repositório', $url !== '' ? $url : 'Ainda não informado'],
            ['Porta', (string) $port],
            ['Manutenção', 'somente CI'],
            ['Reset git', $resetGit ? 'sim (irreversível)' : 'não (reversível via git)'],
        ]);
        note(count($changed).' arquivo(s) serão reescritos'.($changed ? ":\n  ".implode("\n  ", array_slice($changed, 0, 40)).(count($changed) > 40 ? "\n  …" : '') : '.'));
        if ($existingRemovals) {
            note(count($existingRemovals).' arquivo(s) da automação do starter serão removidos:'."\n  ".implode("\n  ", $existingRemovals));
        }

        if ($preview) {
            outro('Preview — nada foi alterado. Rode sem --preview para aplicar.');

            return self::SUCCESS;
        }

        if ($interactive && ! confirm('Aplicar a personalização?', default: true)) {
            outro('Cancelado — nada foi alterado.');

            return self::SUCCESS;
        }

        // ---- Aplicação ----
        $this->processFiles($tokens, apply: true);
        $this->fixEnvLine('APP_NAME', str_contains($appName, ' ') ? '"'.$appName.'"' : $appName);
        $this->fixEnvLine('APP_GITHUB_URL', $url);
        $this->fixEnvLine('APP_PORT', (string) $port);
        $removed = $this->removeMaintenance($existingRemovals);

        // O name do composer.json entra no content-hash do lock — re-hash para o
        // `composer validate --strict` do CI continuar verde. Best-effort.
        if (is_dir(base_path('vendor'))) {
            exec('cd '.escapeshellarg(base_path()).' && composer update --lock --no-interaction 2>/dev/null');
        }

        note('Reescritos: '.count($changed).' arquivos.'.($removed ? " Removidos: {$removed} da automação do starter." : ''));

        if ($resetGit) {
            $this->resetGit($appName);
            note('Histórico git resetado (commit inicial criado).');
        } elseif (is_dir(base_path('.git'))) {
            note('Reversível: confira com `git diff` e desfaça tudo com `git restore .` se precisar.');
        }

        outro("Projeto \"{$appName}\" pronto.\n  php artisan migrate && php artisan serve\n  (o comando app:setup já cumpriu seu papel — pode remover app/Console/Commands/SetupProject.php)");

        return self::SUCCESS;
    }

    private function currentPackage(): string
    {
        $data = json_decode((string) file_get_contents(base_path('composer.json')), true);

        return $data['name'] ?? '';
    }

    /** Porta atual do .env/.env.example, ou o padrão alto do projeto. */
    private function currentPort(): int
    {
        foreach (['.env', '.env.example'] as $rel) {
            $path = base_path($rel);
            if (is_file($path) && preg_match('/^APP_PORT=(\d+)/m', (string) file_get_contents($path), $m)) {
                return (int) $m[1];
            }
        }

        return Ports::DEFAULT_PREFERRED;
    }

    /**
     * Aplica (ou apenas coleta) as substituições de token nos arquivos de texto.
     *
     * @return list<string> caminhos relativos que mudam
     */
    private function processFiles(array $tokens, bool $apply): array
    {
        $finder = (new Finder)
            ->files()
            ->in(base_path())
            ->exclude(['.git', 'vendor', 'node_modules', 'storage', 'bootstrap/cache', 'public/build', 'docs/images'])
            ->notPath('composer.lock')
            ->notPath('package-lock.json')
            ->notName('*.png')->notName('*.jpg')->notName('*.webp')->notName('*.ico')->notName('*.woff*')
            // Preserva os defaults canônicos no código do próprio wizard e do Stack.
            ->notPath('app/Console/Commands/SetupProject.php')
            ->notPath('app/Support/Stack.php')
            ->notPath('config/app.php')
            ->ignoreDotFiles(false);

        $changed = [];
        foreach ($finder as $file) {
            $path = $file->getRealPath();
            $original = (string) file_get_contents($path);
            $new = strtr($original, $tokens);
            if ($new !== $original) {
                $changed[] = $file->getRelativePathname();
                if ($apply) {
                    file_put_contents($path, $new);
                }
            }
        }

        // config/app.php e Stack.php: só a URL padrão de fallback é atualizada.
        foreach (['config/app.php', 'app/Support/Stack.php'] as $rel) {
            $path = base_path($rel);
            $c = (string) file_get_contents($path);
            $n = str_replace('https://github.com/nandinhos/nando-lz', $tokens['https://github.com/nandinhos/nando-lz'], $c);
            if ($n !== $c) {
                $changed[] = $rel;
                if ($apply) {
                    file_put_contents($path, $n);
                }
            }
        }

        sort($changed);

        return $changed;
    }

    /** Reescreve uma variável no .env e no .env.example. */
    private function fixEnvLine(string $key, string $value): void
    {
        foreach (['.env', '.env.example'] as $rel) {
            $path = base_path($rel);
            if (! is_file($path)) {
                continue;
            }
            $c = (string) file_get_contents($path);
            $line = $key.'='.$value;
            // Escapa \ e $ na substituição (evita interpretação de backreference).
            $replacement = addcslashes($line, '\\$');
            $c = preg_match('/^'.$key.'=.*/m', $c)
                ? preg_replace('/^'.$key.'=.*/m', $replacement, $c)
                : rtrim($c, "\n")."\n".$line."\n";
            file_put_contents($path, $c);
        }
    }

    /** Pontos de entrada da automação do starter. */
    private function maintenancePaths(): array
    {
        return [
            '.github/workflows/auto-update.yml',
            '.github/workflows/compat-watch.yml',
            'scripts/resolve-stack.sh',
            'scripts/update-stack.sh',
            'renovate.json',
        ];
    }

    private function removeMaintenance(array $existing): int
    {
        $removed = 0;
        foreach ($existing as $rel) {
            if (@unlink(base_path($rel))) {
                $removed++;
            }
        }

        return $removed;
    }

    private function resetGit(string $appName): void
    {
        $root = base_path();
        $run = fn (string $cmd) => exec('cd '.escapeshellarg($root).' && '.$cmd.' 2>/dev/null');

        exec('rm -rf '.escapeshellarg($root.'/.git'));
        $run('git init -b main');
        $run('git add -A');
        $run('git commit -m '.escapeshellarg('chore: projeto inicial a partir do nando-lz ('.$appName.')'));
    }
}

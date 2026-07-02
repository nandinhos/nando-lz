<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\note;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\select;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;

/**
 * Wizard de personalização do starter (rebrand) para quem clona o nando-lz
 * para começar um projeto novo.
 *
 * - Renomeia identidade: pacote Composer, APP_NAME, banco de dados, URL do repo
 *   e todas as referências textuais ao starter.
 * - Separa os papéis: a automação de manutenção (auto-update, compat-watch,
 *   Renovate, resolve/update-stack) é do MANTENEDOR do nando-lz. Num projeto
 *   novo o wizard a desanexa (o CI permanece).
 *
 * Não-interativo (CI/testes): passe as opções + --no-interaction.
 */
#[Signature('app:setup
    {--name= : Nome da aplicação (humano)}
    {--package= : Pacote Composer no formato vendor/nome}
    {--db= : Nome do banco de dados}
    {--url= : URL do repositório (GitHub)}
    {--maintenance= : detach | renovate | maintainer}
    {--reset-git : Resetar o histórico git}
    {--force : Ignorar a guarda de "já personalizado"}')]
#[Description('Personaliza o starter (rebrand) e desanexa a automação do mantenedor.')]
class SetupProject extends Command
{
    private const STARTER_PACKAGE = 'nandinhos/nando-lz';

    public function handle(): int
    {
        $interactive = $this->input->isInteractive();

        intro('nando-lz · setup do projeto');

        if (! $this->option('force') && $this->currentPackage() !== self::STARTER_PACKAGE) {
            note('Este projeto já parece personalizado ('.$this->currentPackage().'). Use --force para rodar mesmo assim.');

            return self::SUCCESS;
        }

        $maintenance = $this->option('maintenance') ?: ($interactive ? select(
            label: 'Qual o papel deste checkout?',
            options: [
                'detach' => 'Projeto novo — desanexar a automação do starter (só CI)',
                'renovate' => 'Projeto novo — manter Renovate + CI (deps do meu projeto)',
                'maintainer' => 'Sou mantenedor do nando-lz — não mexer em nada',
            ],
            default: 'detach',
        ) : 'detach');

        if ($maintenance === 'maintainer') {
            outro('Modo mantenedor: nada foi renomeado nem removido.');

            return self::SUCCESS;
        }

        // Coleta de identidade (opção da CLI vence; senão prompt; senão default).
        $appName = $this->option('name') ?: ($interactive
            ? text('Nome da aplicação', placeholder: 'Acme CRM', required: true)
            : 'App');

        $slug = Str::slug($appName);
        $db = $this->option('db') ?: ($interactive
            ? text('Banco de dados', default: str_replace('-', '_', $slug), validate: fn ($v) => preg_match('/^[a-z_][a-z0-9_]*$/', $v) ? null : 'Use apenas letras minúsculas, números e _ (começando por letra ou _).')
            : str_replace('-', '_', $slug));

        $package = $this->option('package') ?: ($interactive
            ? text('Pacote Composer (vendor/nome)', default: 'vendor/'.$slug, validate: fn ($v) => preg_match('#^[a-z0-9]([_.-]?[a-z0-9]+)*/[a-z0-9]([_.-]?[a-z0-9]+)*$#', $v) ? null : 'Formato inválido — use vendor/nome em minúsculas.')
            : 'vendor/'.$slug);

        $vendor = explode('/', $package)[0];

        $url = $this->option('url') ?: ($interactive
            ? text('URL do repositório', default: 'https://github.com/'.$package, validate: fn ($v) => str_starts_with($v, 'http') ? null : 'Informe uma URL http(s) válida.')
            : 'https://github.com/'.$package);
        $url = rtrim($url, '/');

        $resetGit = $this->option('reset-git') || ($interactive && confirm('Resetar o histórico git (novo começo para o seu projeto)?', default: true));

        table(['Campo', 'Valor'], [
            ['Nome', $appName],
            ['Pacote', $package],
            ['Banco', $db],
            ['Repositório', $url],
            ['Manutenção', $maintenance === 'renovate' ? 'Renovate + CI' : 'somente CI'],
            ['Reset git', $resetGit ? 'sim' : 'não'],
        ]);

        if ($interactive && ! confirm('Aplicar a personalização?', default: true)) {
            outro('Cancelado — nada foi alterado.');

            return self::SUCCESS;
        }

        // strtr aplica as chaves mais longas primeiro e não reprocessa trechos já
        // substituídos — resolve a precedência (URL > pacote > *_testing > db > slug).
        $tokens = [
            'https://github.com/nandinhos/nando-lz' => $url,
            'nandinhos/nando-lz' => $package,
            'nando_lz_testing' => $db.'_testing',
            'nando_lz' => $db,
            'nando-lz' => $slug,
            'nandinhos' => $vendor,
        ];

        $count = $this->rewriteFiles($tokens);
        $this->fixEnvAppName($appName);

        $removed = $this->detachMaintenance($maintenance);

        // O name do composer.json entra no content-hash do lock — re-hash para o
        // `composer validate --strict` do CI continuar verde. Best-effort.
        if (is_dir(base_path('vendor'))) {
            exec('cd '.escapeshellarg(base_path()).' && composer update --lock --no-interaction 2>/dev/null');
        }

        note("Reescritos: {$count} arquivos.".($removed ? " Removidos: {$removed} da automação do mantenedor." : ''));

        if ($resetGit) {
            $this->resetGit($appName);
            note('Histórico git resetado (commit inicial criado).');
        }

        outro("Projeto \"{$appName}\" pronto.\n  php artisan migrate && php artisan serve\n  (o comando app:setup já cumpriu seu papel — pode remover app/Console/Commands/SetupProject.php)");

        return self::SUCCESS;
    }

    private function currentPackage(): string
    {
        $data = json_decode((string) file_get_contents(base_path('composer.json')), true);

        return $data['name'] ?? '';
    }

    /** Substitui os tokens em todos os arquivos de texto do projeto. */
    private function rewriteFiles(array $tokens): int
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

        $changed = 0;
        foreach ($finder as $file) {
            $path = $file->getRealPath();
            $original = (string) file_get_contents($path);
            $new = strtr($original, $tokens);
            if ($new !== $original) {
                file_put_contents($path, $new);
                $changed++;
            }
        }

        // config/app.php e Stack.php: só a URL padrão de fallback é atualizada.
        foreach (['config/app.php', 'app/Support/Stack.php'] as $rel) {
            $path = base_path($rel);
            $c = (string) file_get_contents($path);
            $n = str_replace('https://github.com/nandinhos/nando-lz', $tokens['https://github.com/nandinhos/nando-lz'], $c);
            if ($n !== $c) {
                file_put_contents($path, $n);
                $changed++;
            }
        }

        return $changed;
    }

    /** APP_NAME deve ser o nome humano (com aspas), não o slug gerado pelo strtr. */
    private function fixEnvAppName(string $appName): void
    {
        foreach (['.env', '.env.example'] as $rel) {
            $path = base_path($rel);
            if (! is_file($path)) {
                continue;
            }
            $value = str_contains($appName, ' ') ? '"'.$appName.'"' : $appName;
            $c = preg_replace('/^APP_NAME=.*$/m', 'APP_NAME='.$value, (string) file_get_contents($path));
            file_put_contents($path, $c);
        }
    }

    /** Remove os pontos de entrada da automação do mantenedor. Retorna quantos apagou. */
    private function detachMaintenance(string $mode): int
    {
        $paths = [
            '.github/workflows/auto-update.yml',
            '.github/workflows/compat-watch.yml',
            'scripts/resolve-stack.sh',
            'scripts/update-stack.sh',
        ];
        if ($mode === 'detach') {
            $paths[] = 'renovate.json';
        }

        $removed = 0;
        foreach ($paths as $rel) {
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

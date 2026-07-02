<?php

namespace App\Support;

/**
 * Resolve o identificador de build exibido no rodapé da sidebar dos painéis (PRD §5.6).
 *
 * Precedência: (1) config app.build (env APP_BUILD) → (2) build.json na raiz →
 * (3) hash curto do commit Git → 'dev' como último recurso.
 *
 * Lê .git/HEAD diretamente para não depender do binário git em runtime/produção,
 * e memoriza o resultado para não repetir I/O a cada request.
 */
class Build
{
    protected static ?string $cached = null;

    public static function id(): string
    {
        return static::$cached ??= static::resolve();
    }

    /** Zera o cache — usado apenas em testes. */
    public static function flush(): void
    {
        static::$cached = null;
    }

    protected static function resolve(): string
    {
        $configured = config('app.build');
        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        $file = base_path('build.json');
        if (is_file($file)) {
            $json = json_decode((string) file_get_contents($file), true);
            // Só escalares: um array/objeto em 'build' derrubaria os painéis no cast.
            if (is_array($json) && isset($json['build']) && is_scalar($json['build']) && $json['build'] !== '') {
                return (string) $json['build'];
            }
        }

        return static::gitHash() ?? 'dev';
    }

    protected static function gitHash(): ?string
    {
        $head = base_path('.git/HEAD');
        if (! is_file($head)) {
            return null;
        }

        $ref = trim((string) file_get_contents($head));

        // HEAD aponta para uma branch: "ref: refs/heads/main"
        if (str_starts_with($ref, 'ref: ')) {
            $refFile = base_path('.git/'.substr($ref, 5));

            return is_file($refFile)
                ? substr(trim((string) file_get_contents($refFile)), 0, 8)
                : null;
        }

        // HEAD destacado: contém o próprio hash
        return $ref !== '' ? substr($ref, 0, 8) : null;
    }
}

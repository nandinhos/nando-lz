#!/usr/bin/env bash
#
# resolve-stack.sh — Resolvedor de compatibilidade da stack (PRD §4.1).
#
# Princípio: o Filament é o pacote LIMITANTE. A major do Laravel é DERIVADA do
# que o Filament estável suporta, nunca escolhida isoladamente.
#
# Ordem (§4.1): 1) última major estável do Filament → 2) constraint illuminate/*
# dessa versão → 3) maior major do Laravel suportada → 4) PHP mínimo → 5) Livewire
# (transitivo) e Pest → 6) PostgreSQL.
#
# A constraint illuminate/* é lida do composer.lock (metadados exatos do pacote
# instalado, sem sofrer com a compressão delta da API p2 da Packagist).
#
# Saída: JSON em stdout. Não altera nada. Exit codes:
#   0 ok    1 erro de rede/parse    2 PHP indisponível
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

PHP_BIN="${PHP_BIN:-php}"
command -v "$PHP_BIN" >/dev/null 2>&1 || { echo "PHP indisponível (defina PHP_BIN)" >&2; exit 2; }

fetch() { curl -fsSL "https://repo.packagist.org/p2/$1.json" 2>/dev/null || { echo "falha ao consultar Packagist: $1" >&2; exit 1; }; }

{ fetch "filament/filament"; echo "@@@"; fetch "laravel/framework"; } \
  | "$PHP_BIN" -r '
    $raw = stream_get_contents(STDIN);
    [$filamentJson, $laravelJson] = array_map("trim", explode("@@@", $raw));

    // Primeira versão estável (exclui alpha/beta/RC/dev); lista vem do mais novo ao mais antigo.
    $firstStable = function (array $versions): ?array {
        foreach ($versions as $v) {
            if (preg_match("/(alpha|beta|RC|dev)/i", $v["version"])) continue;
            return $v;
        }
        return null;
    };
    $decode = fn (string $j, string $name) => json_decode($j, true)["packages"][$name] ?? [];
    $ver = fn (array $v) => ltrim($v["version"], "v");

    $filament = $firstStable($decode($filamentJson, "filament/filament"));
    $laravel  = $firstStable($decode($laravelJson, "laravel/framework"));
    if (! $filament || ! $laravel) { fwrite(STDERR, "não foi possível resolver versões estáveis\n"); exit(1); }

    // Metadados do que está travado localmente.
    $lock = is_file("composer.lock") ? json_decode(file_get_contents("composer.lock"), true) : ["packages" => [], "packages-dev" => []];
    $allLocked = array_merge($lock["packages"] ?? [], $lock["packages-dev"] ?? []);
    $lockedVersion = function (string $name) use ($allLocked) {
        foreach ($allLocked as $p) if ($p["name"] === $name) return ltrim($p["version"], "v");
        return "none";
    };
    $lockedRequire = function (string $name) use ($allLocked): array {
        foreach ($allLocked as $p) if ($p["name"] === $name) return $p["require"] ?? [];
        return [];
    };

    // §4.1(2): constraint illuminate/* do pacote limitante (filament/support).
    $supportReq = $lockedRequire("filament/support");
    $illum = $supportReq["illuminate/contracts"] ?? ($supportReq["illuminate/support"] ?? "");
    // Major = primeiro número de cada segmento OR (ex.: "^11.28|^12.0|^13.0" → 11,12,13).
    $majors = [];
    foreach (preg_split("/\s*\|\|?\s*/", $illum, -1, PREG_SPLIT_NO_EMPTY) as $seg) {
        if (preg_match("/(\d+)/", $seg, $mm)) $majors[] = (int) $mm[1];
    }
    $majors = array_values(array_unique($majors));
    sort($majors);
    $maxSupported = $majors ? max($majors) : null;

    // §4.1(3): maior major do Laravel suportada vs. última estável disponível.
    $laravelMajor = (int) explode(".", $ver($laravel))[0];
    $targetMajor  = $maxSupported ? min($maxSupported, $laravelMajor) : $laravelMajor;
    $blocked      = $maxSupported !== null && $maxSupported < $laravelMajor;

    // §4.1(4): PHP mínimo derivado do Laravel.
    $phpMin = $laravel["require"]["php"] ?? "^8.3";

    echo json_encode([
        "limiting_package" => "filament/filament",
        "filament" => [
            "current" => $lockedVersion("filament/filament"),
            "latest_stable" => $ver($filament),
        ],
        "laravel" => [
            "current" => $lockedVersion("laravel/framework"),
            "latest_stable" => $ver($laravel),
            "illuminate_constraint" => $illum,
            "max_supported_major" => $maxSupported,
            "target_major" => $targetMajor,
        ],
        "livewire" => ["current" => $lockedVersion("livewire/livewire"), "note" => "transitivo via Filament"],
        "pest" => ["current" => $lockedVersion("pestphp/pest")],
        "php" => ["running" => PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION, "min" => $phpMin],
        "postgres" => ["min" => "16"],
        "blocked_upstream" => $blocked,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    echo "\n";
  '

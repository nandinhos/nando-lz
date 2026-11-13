<?php

declare(strict_types=1);

require_once __DIR__.'/../../scripts/classify-update.php';

it('classifica como AUTO quando há apenas patch/minor em pacotes principais dentro do escopo', function () {
    $old = [
        'laravel/framework' => 'v13.25.0',
        'guzzlehttp/guzzle' => '7.15.3',
    ];
    $new = [
        'laravel/framework' => 'v13.40.0',
        'guzzlehttp/guzzle' => '7.15.4',
    ];

    $analysis = UpdateClassifier::analyze(
        $old,
        $new,
        ['composer.lock', 'README.md', 'docs/reports/auto-update/2026-11-09.md']
    );

    expect($analysis['classification'])->toBe('AUTO')
        ->and($analysis['primary_updates'])->toContain('`laravel/framework`: `v13.25.0` → `v13.40.0` (minor)')
        ->and($analysis['transitive_majors'])->toBeEmpty()
        ->and($analysis['review_reasons'])->toBeEmpty();
});

it('distingue pacotes principais de majors transitivas e explica motivo real do REVIEW', function () {
    $old = [
        'filament/filament' => 'v5.7.6',
        'laravel/framework' => 'v13.25.0',
        'brick/math' => '0.18.0',
        'guzzlehttp/guzzle' => '7.15.3',
    ];
    $new = [
        'filament/filament' => 'v5.11.2',
        'laravel/framework' => 'v13.40.0',
        'brick/math' => '1.0.0',
        'guzzlehttp/guzzle' => '8.3.1',
    ];

    $analysis = UpdateClassifier::analyze(
        $old,
        $new,
        [
            'composer.lock',
            'public/css/filament/filament/app.css',
            'public/js/filament/filament/app.js',
        ]
    );

    expect($analysis['classification'])->toBe('REVIEW')
        ->and($analysis['primary_updates'])->toHaveCount(2)
        ->and($analysis['transitive_majors'])->toContain(
            '`brick/math`: `0.18.0` → `1.0.0`',
            '`guzzlehttp/guzzle`: `7.15.3` → `8.3.1`'
        )
        ->and($analysis['review_reasons'])->toHaveCount(2);

    $markdown = UpdateClassifier::formatMarkdown($analysis);
    expect($markdown)
        ->toContain('Classificação efetiva: **REVIEW**')
        ->toContain('Majors transitivas (`composer.lock`):')
        ->toContain('Arquivos fora do escopo autônomo (2)');
});

it('classifica como REVIEW quando composer.json é alterado ou há falha de gate', function () {
    $analysis = UpdateClassifier::analyze(
        ['laravel/framework' => 'v13.25.0'],
        ['laravel/framework' => 'v13.25.0'],
        ['composer.json', 'composer.lock'],
        gatesFailed: true
    );

    expect($analysis['classification'])->toBe('REVIEW')
        ->and($analysis['review_reasons'])->toContain(
            'Um ou mais gates de validação falharam durante o ciclo.',
            'Manifesto alterado (`composer.json`).'
        );
});

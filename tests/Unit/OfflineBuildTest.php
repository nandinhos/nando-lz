<?php

test('o build não depende de provedores externos de fontes', function () {
    $paths = [
        base_path('vite.config.js'),
        resource_path('css/app.css'),
        resource_path('views/welcome.blade.php'),
        resource_path('views/project-welcome.blade.php'),
    ];

    $content = implode("\n", array_map(static fn (string $path): string => (string) file_get_contents($path), $paths));

    expect($content)
        ->not->toContain('laravel-vite-plugin/fonts')
        ->not->toContain('fonts.googleapis.com')
        ->not->toContain('fonts.gstatic.com')
        ->not->toContain("bunny('");
});

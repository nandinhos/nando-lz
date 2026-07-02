<?php

namespace App\Providers;

use App\Support\Build;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // `php artisan serve` só repassa ao worker HTTP as variáveis do allowlist.
        // No modo Docker as credenciais de banco vêm do ambiente do container
        // (não do .env), então precisam ser repassadas explicitamente. Inócuo
        // em local/produção, onde já vêm do .env. Garante paridade Local↔Docker.
        ServeCommand::$passthroughVariables = array_values(array_unique(array_merge(
            ServeCommand::$passthroughVariables,
            ['DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'],
        )));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Rodapé com o identificador de build em TODOS os painéis (§5.6),
        // registrado uma única vez em vez de repetir por painel.
        FilamentView::registerRenderHook(
            PanelsRenderHook::SIDEBAR_FOOTER,
            fn (): string => '<div class="px-6 py-3 text-xs text-gray-400 dark:text-gray-500">build '.e(Build::id()).'</div>',
        );
    }
}

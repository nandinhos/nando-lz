<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\OpsPanelProvider;
use App\Providers\Filament\SupportPanelProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    OpsPanelProvider::class,
    SupportPanelProvider::class,
];

<?php

use App\Support\Stack;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view(Stack::isStarter() ? 'welcome' : 'project-welcome', ['stack' => Stack::snapshot()]);
});

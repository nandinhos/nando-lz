<?php

use App\Support\Stack;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome', ['stack' => Stack::snapshot()]);
});

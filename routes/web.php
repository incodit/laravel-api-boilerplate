<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

// Auth routes moved to api.php for CSRF-free API access
// require __DIR__.'/auth.php';

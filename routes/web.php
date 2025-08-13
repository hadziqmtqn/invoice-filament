<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->to('/panel/login'));

Route::get('callback-messages', function () {
    \Illuminate\Support\Facades\Log::info('Callback messages received', [
        'request' => request()->all(),
    ]);
});


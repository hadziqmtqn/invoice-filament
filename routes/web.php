<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->to('/panel/login'));

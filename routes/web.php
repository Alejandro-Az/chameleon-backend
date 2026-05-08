<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'ok' => true,
        'data' => [
            'name' => config('kaan.name', 'Kaan Core Backend'),
            'version' => config('kaan.version', '0.2.0-alpha'),
            'docs' => url('/api/documentation'),
        ],
    ]);
});

<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'status' => 'online',
        'app' => config('app.name', 'Starforce HRMS API'),
        'timestamp' => now()->toISOString(),
        'health' => url('/up'),
    ]);
});

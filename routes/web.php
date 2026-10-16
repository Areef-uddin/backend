<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;

Route::get('/', function () {
    return response()->json([
        'status' => 'online',
        'app' => config('app.name', 'Starforce HRMS API'),
        'timestamp' => now()->toISOString(),
        'health' => url('/up'),
        'db_check' => url('/db-check'),
    ]);
});

Route::get('/db-check', function () {
    try {
        DB::connection()->getPdo();
        $hasUsersTable = DB::getSchemaBuilder()->hasTable('users');
        $userCount = $hasUsersTable ? DB::table('users')->count() : 0;
        return response()->json([
            'status' => 'connected',
            'database' => DB::connection()->getDatabaseName(),
            'users_table_exists' => $hasUsersTable,
            'users_count' => $userCount,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'host' => config('database.connections.mysql.host'),
            'port' => config('database.connections.mysql.port'),
            'database' => config('database.connections.mysql.database'),
        ], 500);
    }
});

Route::get('/migrate-run', function (Request $request) {
    $expectedKey = config('app.key');
    if (!$expectedKey || $request->query('key') !== $expectedKey) {
        return response()->json(['error' => 'Unauthorized. Provide valid ?key=APP_KEY'], 403);
    }
    try {
        Artisan::call('migrate --seed --force');
        return response()->json([
            'status' => 'success',
            'message' => 'Migration and seed completed successfully!',
            'output' => Artisan::output(),
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'output' => Artisan::output(),
        ], 500);
    }
});

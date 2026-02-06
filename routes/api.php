<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\VpnClientController;
use App\Jobs\TestRedisQueue;
use App\Services\OpenVpn\OpenVpnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

Route::name('auth.')->prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::post('register', [AuthController::class, 'register']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout-all', [AuthController::class, 'logoutAll']);
    });
});

Route::name('vpn-clients.')->prefix('vpn-clients')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [VpnClientController::class, 'index']);
    Route::post('/', [VpnClientController::class, 'add'])->middleware('throttle:vpn-create');
    Route::get('/{id}', [VpnClientController::class, 'show']);
    Route::put('/{id}', [VpnClientController::class, 'update']);
    Route::delete('/{id}', [VpnClientController::class, 'destroy']);
    Route::post('/{id}/provision', [VpnClientController::class, 'provision'])->middleware('throttle:vpn-provision');
    Route::post('/{id}/revoke', [VpnClientController::class, 'revoke'])->middleware('throttle:vpn-revoke');
});

//secure for admin later
Route::get('/health/redis', function () {
    try {
        return response()->json(['redis' => Redis::ping(), 'success' => true]);
    } catch (Throwable $e) {
        return response()->json([
            'redis' => 'down',
            'error' => $e->getMessage(),
        ], 500);
    }
});

//dev routes. Remove or secure later
Route::post('/openvpn/clients', function (Request $r, OpenVpnService $vpn) {
    $name = $r->string('name')->toString();
    $vpn->createClient($name);
    return response()->json(['status' => 'created', 'name' => $name]);
});

Route::get('/openvpn/clients/{name}/ovpn', function (string $name, OpenVpnService $vpn) {
    $ovpn = $vpn->getClientOvpn($name);
    return response($ovpn, 200)->header('Content-Type', 'text/plain');
});

Route::delete('/openvpn/clients/{name}', function (string $name, OpenVpnService $vpn) {
    $vpn->revokeClient($name);
    return response()->json(['status' => 'revoked', 'name' => $name]);
});

Route::get('/test', function () {
    TestRedisQueue::dispatch();
    return response()->json(['status' => 'dispatched']);
});

/*
 * Notes:
 *     Redis::set('test_key', 'test_value'); //laravel-database-test_key
    Redis::set('test_key2', 'test_value2'); //laravel-database-test_key2
    return Redis::get('test_key2'); // KEYS *test_key* in Redis CLI
 */

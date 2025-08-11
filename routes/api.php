<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/test', function () {
    return response()->json(['message' => 'API is working!', 'time' => now()]);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/active-sessions', [AuthController::class, 'activeSessions']);
    Route::delete('/revoke-token', [AuthController::class, 'revokeToken']);
});
<?php

use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\UserController;
use App\Http\Resources\SessionResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
    ->middleware('throttle:password-recovery');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])
    ->middleware('throttle:password-recovery');

Route::middleware(['auth:sanctum', 'sanctum.session', 'active'])->group(function () {
    Route::get('/user', function (Request $request) {
        return response()->json(SessionResource::make($request->user())->resolve($request));
    });

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::put('/user/password', [AuthController::class, 'changePassword']);

    Route::middleware('password.changed')->group(function () {
        Route::get('audit-logs', [AuditLogController::class, 'index'])
            ->name('audit-logs.index');

        Route::patch('users/{user}/role', [UserController::class, 'changeRole'])
            ->name('users.role');
        Route::patch('users/{user}/status', [UserController::class, 'changeStatus'])
            ->name('users.status');
        Route::apiResource('users', UserController::class)
            ->only(['index', 'store', 'show', 'update']);

        Route::get('books/low-stock', [BookController::class, 'lowStock']);
        Route::apiResource('books', BookController::class);
    });
});

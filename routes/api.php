<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Middleware\StaticApiToken;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('api.login');

Route::get('/products/stats', [ProductController::class, 'stats'])
    ->middleware(StaticApiToken::class)
    ->name('products.stats');

Route::apiResource('products', ProductController::class)
    ->only(['index', 'show'])
    ->middleware(StaticApiToken::class);

Route::apiResource('products', ProductController::class)
    ->only(['store', 'update', 'destroy'])
    ->middleware(StaticApiToken::class);

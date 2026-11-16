<?php

use App\Http\Controllers\AdminController;
use App\Http\Middleware\AdminAuth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

// Di belakang proxy HTTPS (Coolify/Traefik) paksa URL redirect memakai https
if (env('FORCE_HTTPS')) {
    URL::forceScheme('https');
}

Route::redirect('/', '/admin');

Route::get('/login', [AdminController::class, 'loginForm']);
Route::post('/login', [AdminController::class, 'login'])->middleware('throttle:10,1');
Route::post('/logout', [AdminController::class, 'logout']);

Route::middleware(AdminAuth::class)->group(function () {
    Route::get('/admin', [AdminController::class, 'orders']);
    Route::post('/admin/orders/{id}/status', [AdminController::class, 'updateStatus'])->whereNumber('id');

    Route::get('/admin/menu', [AdminController::class, 'menu']);
    Route::post('/admin/menu', [AdminController::class, 'menuStore']);
    Route::post('/admin/menu/{id}', [AdminController::class, 'menuUpdate'])->whereNumber('id');
    Route::post('/admin/menu/{id}/delete', [AdminController::class, 'menuDelete'])->whereNumber('id');
});

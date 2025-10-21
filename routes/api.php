<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\KategoriMenuController;
use App\Http\Controllers\Api\PesananController;

Route::post('/pesanan', [PesananController::class, 'store']);

Route::group(['prefix' => 'auth'], function () {
    Route::post('/login', [AuthController::class, 'login']); // Login route
    Route::Group(['middleware' => 'auth:api'], function () {
        Route::post('/logout', [AuthController::class, 'logout']); // Logout route
        Route::post('/refresh', [AuthController::class, 'refresh']); // Refresh route
        Route::get('/me', [AuthController::class, 'me']); // User route
    });
});

Route::group(['middleware' => 'auth:api'], function () {
    Route::apiResource('/staff', StaffController::class)->middleware('role:Admin');
    Route::apiResource('/tenant', TenantController::class)->middleware('role:Admin');
    Route::apiResource('/kategori-menu', KategoriMenuController::class)->middleware('role:Admin,Pemilik Tenant');
    Route::apiResource('/menu-items', MenuItemController::class);
});


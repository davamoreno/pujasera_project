<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\KategoriMenuController;
use App\Http\Controllers\Api\MenuItemController;
use App\Http\Controllers\Api\PesananController;
use App\Http\Controllers\Api\PembayaranController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\XenditController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\SesiPembeliController;


Route::post('/webhook/payment', [WebhookController::class, 'handle']);

Route::group(['prefix' => 'auth', 'middleware' => 'is_active'], function () {

    Route::post('/login', [AuthController::class, 'login']); // Login route

    Route::Group(['middleware' => 'auth:api'], function () {
        Route::post('/logout', [AuthController::class, 'logout']); // Logout route
        Route::post('/refresh', [AuthController::class, 'refresh']); // Refresh route
        Route::get('/refresh-user-data', [AuthController::class, 'refreshUserData']); // Refresh user data route
        Route::get('/me', [AuthController::class, 'me']); // User route
    });
});

// Public routes
Route::group(['prefix' => 'public'], function () {
    // Tenant routes
    Route::get('/tenants', [TenantController::class, 'indexPublic']);
    Route::get('/tenants/{tenant}', [TenantController::class, 'showPublic']);
    Route::get('/tenants/{tenant}/menu-items', [TenantController::class, 'getMenuItem']);

    // Sesi Pembeli routes
    Route::post('/sesi-pembeli', [SesiPembeliController::class, 'store']);
    Route::get('/sesi-pembeli/{identifier}', [SesiPembeliController::class, 'show']); // identifier = id or kode
    Route::put('/sesi-pembeli/{identifier}/close', [SesiPembeliController::class, 'close']);

    // Pesanan routes
    Route::post('/orders', [PesananController::class, 'store']);
    Route::get('/orders/history', [PesananController::class, 'history']);
    Route::get('/orders/{kode}', [PesananController::class, 'showByKode']);
    
    // Route::get('/pesanan/{kode_pesanan}', [PesananController::class, 'showByKodePesanan']);
    // Xendit routes
    Route::post('/pembayaran/{pesanan:kode_pesanan}/link', [XenditController::class, 'createInvoicesLink']);
});

// Protected routes
Route::group(['middleware' => ['auth:api', 'is_active']], function () {

    // Dashboard routes
    Route::group(['prefix' => 'dashboard', 'middleware' => 'role:Admin,Pemilik Tenant'], function () {
        Route::get('/stats', [DashboardController::class, 'stats'])->middleware('role:Admin,Pemilik Tenant'); 
        Route::get('/recap', [DashboardController::class, 'recap'])->middleware('role:Admin,Pemilik Tenant'); 
        Route::get('/months', [DashboardController::class, 'availableMonths'])->middleware('role:Admin,Pemilik Tenant');
    });

    // Admin and Pemilik Tenant routes
    Route::group(['middleware' => 'role:Admin,Pemilik Tenant'], function () {
        Route::apiResource('/kategori-menu', KategoriMenuController::class)->middleware('role:Admin,Pemilik Tenant');
        Route::apiResource('/menu-items', MenuItemController::class)->middleware('role:Admin,Pemilik Tenant');
        Route::apiResource('/pesanan', PesananController::class)->only(['index', 'show', 'update'])->middleware('role:Admin,Pemilik Tenant');
        Route::post('/pembayaran/{pesanan}/konfirmasi', [PembayaranController::class, 'konfirmasiManual'])->middleware('role:Admin,Pemilik Tenant');
    });

    // Admin routes
    Route::group(['middleware' => 'role:Admin'], function () {
        Route::apiResource('/staff', StaffController::class)->middleware('role:Admin');
        Route::apiResource('/tenants', TenantController::class)->middleware('role:Admin');
        Route::apiResource('/roles', RoleController::class)->only(['index'])->middleware('role:Admin');
    });

    // Pemilik Tenant routes
    Route::group(['middleware' => 'role:Pemilik Tenant'], function () {
        //
    });

    // Non middleware zone
    Route::put('/tenant/status', [TenantController::class, 'updateStatus']);
});


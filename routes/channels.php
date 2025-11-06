<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Di sinilah Anda dapat mendaftarkan semua channel siaran event
| yang didukung oleh aplikasi Anda.
|
*/

// PERBAIKAN: Tambahkan ['middleware' => 'auth:api']
// Ini memberitahu Laravel untuk menggunakan guard JWT kita untuk rute auth siaran
Broadcast::routes(['middleware' => 'auth:api']);

// Channel ini akan memvalidasi apakah staff yang sedang login
// berhak mendengarkan notifikasi untuk tenant ID tertentu.

Broadcast::channel('tenant.{tenantId}', function ($staff, $tenantId) {
    // Logika ini sudah benar
    // $staff adalah user yang didapat dari token JWT
    return $staff->role->nama === 'Pemilik Tenant' && $staff->tenant->id == $tenantId;
}, ['guards' => ['api']]);
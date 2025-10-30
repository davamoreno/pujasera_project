<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::routes();

/**
 * Channel ini akan memvalidasi apakah staff yang sedang login
 * berhak mendengarkan notifikasi untuk tenant ID tertentu.
 */
Broadcast::channel('tenant.{tenantId}', function ($staff, $tenantId) {
    // $staff adalah user yang sedang login (didapat dari token)
    // $tenantId adalah ID dari URL channel
    
    // Pastikan user adalah 'Pemilik Toko' dan ID tenant-nya cocok
    return $staff->role->nama == 'Pemilik Tenant' && $staff->tenant->id == $tenantId;
});

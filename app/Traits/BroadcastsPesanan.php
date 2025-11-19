<?php

namespace App\Traits;

use App\Models\Pesanan;
use App\Events\PesananMasukUntukTenant; // Pastikan ini ShouldBroadcastNow
use Illuminate\Support\Collection;

trait BroadcastsPesanan
{
    /**
     * Helper function untuk mem-broadcast event ke tenant.
     */
    private function broadcastPesananMasuk(Pesanan $pesanan)
    {
        // Load relasi (plural, sesuai modelmu)
        $pesananLengkap = $pesanan->load('detailPesanans.menuItem.tenant');

        // Kelompokkan (plural)
        $itemsByTenant = $pesananLengkap->detailPesanans->groupBy(function ($detail) {
            // Keamanan: Pastikan relasi ada
            if (!$detail->menuItem || !$detail->menuItem->tenant) {
                return 'unknown';
            }
            return $detail->menuItem->tenant->id;
        });

        foreach ($itemsByTenant as $tenantId => $detailItems) {
            if ($tenantId === 'unknown') continue;
            
            // Buat payload manual
            $itemsPayload = $detailItems->map(fn($detail) => [
                'id' => $detail->id,
                'jumlah' => $detail->jumlah,
                'catatan' => $detail->catatan,
                'harga_saat_pesan' => $detail->harga_saat_pesan,
                'menuItem' => ['nama' => $detail->menuItem->nama]
            ])->all();

            event(new PesananMasukUntukTenant(
                $tenantId, $itemsPayload, $pesananLengkap->kode_pesanan
            ));
        }
    }
}
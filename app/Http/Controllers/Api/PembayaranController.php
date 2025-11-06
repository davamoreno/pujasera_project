<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pesanan;
use Illuminate\Support\Facades\DB;
use App\Events\PesananMasukUntukTenant;

class PembayaranController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Handle manual payment confirmation for a specific order.
     */
    public function konfirmasiManual(Request $request, Pesanan $pesanan)
    {
        // Cek apakah pesanan sudah dibayar
        if ($pesanan->status_pembayaran === 'Lunas') {
            return response()->json(['message' => 'Pesanan sudah dibayar.'], 422);
        } 

        try{
            DB::transaction(function () use ($pesanan) {
                // Perbarui status pembayaran pada tabel pembayaran dan pesanan
                $pesanan->pembayaran->update([
                    'status_pembayaran' => 'Lunas', 
                    'waktu_bayar' => now()
                ]);

                // Perbarui status pesanan menjadi 'Diproses'
                $pesanan->update(['status_pembayaran' => 'Diproses']);
            });

            // Muat ulang relasi untuk mendapatkan detail pesanan lengkap
            $pesananLengkap = $pesanan->load('detailPesanans.menuItem.tenant');

            // Kelompokkan item berdasarkan tenant
            $itemsByTenant = $pesananLengkap->detailPesanans->groupBy(function ($detail) {
                return $detail->menuItem->tenant->id;
            });

            // Emit event untuk setiap tenant yang terkait dengan pesanan
            foreach ($itemsByTenant as $tenantId => $items) {
                // Kirim event PesananMasukUntukTenant
                event(new PesananMasukUntukTenant(
                    $tenantId,
                    $items,
                    $pesanan->kode_pesanan
                ));
            }

            // Berhasil mengonfirmasi pembayaran
            return response()->json([
                'message' => 'Konfirmasi pembayaran berhasil. pesanan diteruskan di dapur',
                'pesanan' => $pesanan->load('pembayaran')
            ], 200);
        } catch (\Exception $e) {
            // Tangani kesalahan selama proses konfirmasi pembayaran
            return response()->json([
                'message' => 'Konfirmasi pembayaran gagal.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

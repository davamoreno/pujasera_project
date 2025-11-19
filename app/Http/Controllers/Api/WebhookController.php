<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\PesananMasukUntukTenant; 
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class WebhookController extends Controller
{
    public function handle(Request $request){
           // --- TAHAP 1: Verifikasi Webhook
        $payload = $request->all();
        $kodePesanan = $payload['order_id'];
        $statusTransaksi = $payload['transaction_status'];
            // --- TAHAP 2: Proses Bisnis ---

        $pesanan= Pesanan::where('kode_pesanan', $kodePesanan)->first();
        if(!$pesanan){
            return response()->json(['message'=>'Pesanan tidak ditemukan'],404);
        }
        
        if($statusTransaksi == 'settlement' || $statusTransaksi == 'capture'){
            if($pesanan->pembayaran->status_pembayran === 'lunas') {
                return response()->json(['message'=> 'Pesanan sudah lunas'],200);
            }
            try{
                DB::transaction(function() use ($pesanan){
                    $pesanan->pembayran->update([
                        'status_pembayaran' => 'lunas',
                        'waktu_bayar' => now(),
                    ]);
                    $pesanan->update([
                        'status_pesanan' => 'diproses',
                    ]);
                });
            } catch(\Exception $e){
                return response()->json(['message'=>'Gagal update database'],500);
            }
            $this->broadcastPesananMasuk($pesanan);

            return respons()->json(['message'=>'webhook berhasil diproses'],200);
        } 
        return response()->json(['message' => 'Status transaksi tidak diproses.']);
    }

    private function broadcastPesananMasuk(Pesanan $pesanan){
         $pesananLengkap = $pesanan->load('detailPesanans.menuItem.tenant');

         $itemsByTenant = $pesananLengkap->detailPesanans->groupBy(function($detail){
            return $detail->menuItem->tenant->id;
         });

         foreach($itemsByTenant as $tenantId => $detailItems){
            $itemPayload = $detailItems->map(function($detail){
                return [
                    'id'=>$detail->id,
                    'jumlah'=>$detail->jumlah,
                    'catatan'=>$detail->catatan,
                    'harga_saat_pesan'=>$detail->harga_saat_pesan,
                    'menuItem' => [
                        'nama' => $detail->menuItem->nama,
                    ]
                    ];
            })->all();
             // Kirim event (pastikan event-mu pakai ShouldBroadcastNow)
             event(new PesananMasukUntukTenant(
                $tenantId,
                $itemPayload,
                $pesananLengkap->kode_pesanan
             ));
         }
    }
}

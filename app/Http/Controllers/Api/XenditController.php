<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\PesananMasukUntukTenant;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;

class XenditController extends Controller
{
    public function generateQRCode(Request $request, Pesanan $pesanan){
        $secretKey = env('XENDIT_SECRET_KEY');

        $payload = [
            'external_id' => pesanan->kode_pesanan,
            'type' => 'DYNAMIC', // Penting: QR code hanya untuk 1x bayar
            'amount' => $pesanan->total_harga,
            'currency' => 'IDR',
            'callback_url' => url('/api/webhooks/xendit'), // Beritahu Xendit ke mana harus kirim webhook
        ];

        $response = http::withBasicAuth($secretKey, '')
            ->post('https://api.xendit.co/qr_codes', $payload);
        if($response->failed()){
            \Log::error('Xendit QR Error: ', $response->json());
            return response()->json(['message' => 'Gagal membuat QR Code Xendit'], 500);
        }
          return response()->json([
            'qr_string' => $response->json()['qr_string'],
            'external_id' => $response->json()['external_id']
        ]);
    }
    public function handleWebhook(Request $request){
        $webhookToken = $request->header('x-callback-token');
        if($webhookToken !==env('XENDIT_WEBHOOK_TOKEN')){
            return response()->json(['message'=>'Unauthorized'],401);
        }

        $payload =  $request->all();

        // Ambil ID dari payload. Untuk QR, Xendit mengirim 'data', 
        // tapi untuk invoice 'external_id'. Kita cek keduanya.
        $kodePesanan = $payload['external_id'] ?? $payload['data']['external_id'] ?? null;
        // PERBAIKAN: Status QR Code adalah 'COMPLETED', Invoice adalah 'PAID'
        $statusTransaksi = $payload['status'] ?? $payload['data']['status'] ?? null;

        $pesanan = Pesanan::where('kode_pesanan', $kodePesanan)->first();

        if (!$pesanan) {
            return response()->json(['message' => 'Pesanan tidak ditemukan'], 404);
        }

        // Cek apakah statusnya 'PAID' (dari Invoice) atau 'COMPLETED' (dari QR Code)
        if ($statusTransaksi === 'PAID' || $statusTransaksi === 'COMPLETED') {
            
            if ($pesanan->pembayaran->status_pembayaran === 'lunas') {
                return response()->json(['message' => 'Pesanan sudah lunas'], 200);
            }

            try {
                DB::transaction(function () use ($pesanan) {
                    $pesanan->pembayaran->update([
                        'status_pembayaran' => 'lunas',
                        'waktu_bayar' => now(),
                    ]);
                    $pesanan->update([
                        'status_pesanan' => 'diproses',
                    ]);
                });
            } catch (\Exception $e) {
                return response()->json(['message' => 'Gagal update database'], 500);
            }

            // PICU EVENT REAL-TIME (Logika yang sama persis)
            $this->broadcastPesananMasuk($pesanan);

            return response()->json(['message' => 'Webhook berhasil diproses']);
        }

        return response()->json(['message' => 'Status transaksi tidak diproses']);

    }

    private function broadcastPesananMasuk(Pesanan $pesanan){
       $pesananLengkap = $pesanan->load('detailPesanans.menuItem.tenant');
        $itemsByTenant = $pesananLengkap->detailPesanans->groupBy(fn($detail) => $detail->menuItem->tenant->id);

        foreach ($itemsByTenant as $tenantId => $detailItems) {
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

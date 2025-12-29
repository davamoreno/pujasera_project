<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\PesananMasukUntukTenant;
use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Traits\BroadcastsPesanan;
class XenditController extends Controller
{
    use BroadcastsPesanan;

    public function createInvoicesLink(Request $request, Pesanan $pesanan){
        if ($pesanan->pembayaran->xendit_invoice_url && $pesanan->pembayaran->xendit_invoice_status !== 'EXPIRED') 
        {
            return response()->json([
                'invoice_url' => $pesanan->pembayaran->xendit_invoice_url
            ]);
        }
        
        $secretKey = env('XENDIT_SECRET_KEY');
        $frontendUrl = 'http://localhost:3000'; // Ganti dengan URL frontend Anda

        $payload = [
            'external_id' => $pesanan->kode_pesanan,
            'amount' => $pesanan->total_harga,
            'currency' => 'IDR',
            'invoice_duration' => 1800, // 30 menit
            'success_redirect_url' => $frontendUrl . '/orders/' . $pesanan->kode_pesanan,
            'failure_redirect_url' => $frontendUrl . '/orders/' . $pesanan->kode_pesanan,
        ];

        $response = Http::withBasicAuth($secretKey, '')
            ->post('https://api.xendit.co/v2/invoices', $payload);
        if($response->failed()){
            Log::error('Xendit Invoices Error: ', $response->json());
            return response()->json([
                'message' => 'Gagal membuat Link pembayaran Xendit',
                'error' => $response->json()
            ], 500);
        }

        $responseData = $response->json();
        $pesanan->pembayaran()->update([
            'xendit_invoice_id' => $responseData['id'],
            'xendit_invoice_url' => $responseData['invoice_url'],
            'xendit_invoice_status' => 'pending',
        ]);
        return response()->json([
            'invoice_url' => $responseData['invoice_url']
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
}

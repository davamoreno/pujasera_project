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
use Carbon\Carbon;

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
            'invoice_duration' => 3600, // 1 jam
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
            'xendit_expires_at' => Carbon::parse($responseData['expiry_date'])
                ->setTimezone('Asia/Makassar')
                ->format('Y-m-d H:i:s'),
            'xendit_invoice_status' => 'pending',
        ]);
        return response()->json([
            'invoice_url' => $responseData['invoice_url']
        ]);
    }

    public function handleWebhook(Request $request){

        // 1. CEK APAKAH REQUEST MASUK?
        Log::info('🔔 Webhook Xendit Masuk!', $request->all());

        $webhookToken = $request->header('x-callback-token');

        // 2. CEK TOKEN
        if($webhookToken !== env('XENDIT_WEBHOOK_TOKEN')){
            Log::error('❌ Token Tidak Cocok! Dikirim: ' . $webhookToken);
            return response()->json(['message'=>'Unauthorized'], 401);
        }

        $payload = $request->all();
        $kodePesanan = $payload['external_id'] ?? $payload['data']['external_id'] ?? null;
        $statusTransaksi = $payload['status'] ?? $payload['data']['status'] ?? null;

        Log::info("🔍 Cek Pesanan: $kodePesanan | Status: $statusTransaksi");

        $pesanan = Pesanan::where('kode_pesanan', $kodePesanan)->first();

        if (!$pesanan) {
            Log::error('❌ Pesanan tidak ditemukan di DB');
            return response()->json(['message' => 'Pesanan tidak ditemukan'], 404);
        }

        if ($statusTransaksi === 'PAID' || $statusTransaksi === 'COMPLETED' || $statusTransaksi === 'SUCCEEDED') {
            try {
                DB::transaction(function () use ($pesanan) {
                    $pesanan->pembayaran->update([
                        'status_pembayaran' => 'lunas',
                        'waktu_bayar' => now(),
                        'xendit_invoice_status' => 'PAID',
                        'xendit_expires_at' => null,
                    ]);
                    $pesanan->update([
                        'status_pesanan' => 'diproses',
                    ]);
                });

                Log::info('✅ Berhasil Update DB ke Diproses!');

                $this->broadcastPesananMasuk($pesanan);
                return response()->json(['message' => 'Webhook berhasil diproses']);

            } catch (\Exception $e) {
                Log::error('❌ Gagal Update DB: ' . $e->getMessage());
                return response()->json(['message' => 'Gagal update database'], 500);
            }
        } else {
            Log::warning('⚠️ Status transaksi bukan PAID/COMPLETED: ' . $statusTransaksi);
        }

        return response()->json(['message' => 'Status transaksi tidak diproses']);
    }
}

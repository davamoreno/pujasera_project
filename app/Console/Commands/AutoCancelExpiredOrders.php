<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pesanan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AutoCancelExpiredOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:auto-cancel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Membatalkan pesanan yang sudah expired (Tunai > 2 jam, E-Money > 1 jam)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        // 1. Batalkan Pesanan TUNAI yang pending > 2 Jam
        $expiredTunai = Pesanan::where('status_pesanan', 'pending')
            ->whereHas('pembayaran', function($q) {
                // Asumsi ID 1 adalah Tunai. Sesuaikan dengan ID di database Anda
                $q->where('metode_pembayaran_id', 1); 
            })
            ->where('created_at', '<=', $now->copy()->subHours(2))
            ->get();

        foreach ($expiredTunai as $pesanan) {
            $pesanan->update(['status_pesanan' => 'dibatalkan']);
            $pesanan->pembayaran->update(['status_pembayaran' => 'gagal']); // Atau status 'expired'
            Log::info("Auto Cancel Tunai: Pesanan {$pesanan->kode_pesanan} dibatalkan karena expired.");
        }

        // 2. Batalkan Pesanan E-MONEY yang pending > 1 Jam
        $expiredEmoney = Pesanan::where('status_pesanan', 'pending')
            ->whereHas('pembayaran', function($q) {
                // Asumsi ID != 1 adalah E-Money
                $q->where('metode_pembayaran_id', '!=', 1);
            })
            ->where('created_at', '<=', $now->copy()->subHours(1))
            ->get();

        foreach ($expiredEmoney as $pesanan) {
            $pesanan->update(['status_pesanan' => 'dibatalkan']);
            
            // Khusus Xendit, mungkin statusnya 'EXPIRED' di API mereka, 
            // tapi di DB kita set 'gagal' atau 'expired'
            $pesanan->pembayaran->update([
                'status_pembayaran' => 'gagal',
                'xendit_invoice_status' => 'EXPIRED' 
            ]);
            Log::info("Auto Cancel E-Money: Pesanan {$pesanan->kode_pesanan} dibatalkan karena expired.");
        }

        $this->info('Pengecekan pesanan expired selesai.');
    }
}
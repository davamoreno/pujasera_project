<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StorePesananRequest;
use App\Models\DetailPesanan;
use App\Models\MenuItem;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Models\SesiPembeli;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class PesananController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Pesanan::with(['sesiPembeli', 'detailPesanans.menuItem', 'pembayaran']);
        if($user->role->nama === 'Pemilik Tenant'){
            $tenantId= $user->tenant->id;
            $query->whereHas('detailPesanans.menuItem', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            });
        }

        $pesanan = $query->latest()->paginate(20);  
        return response()->json($pesanan);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePesananRequest $request)
    {
        /**
         * Menyimpan pesanan baru beserta detailnya dalam satu transaksi database.
         */
        $data = $request->validated();
        
        try {
            // Mulai transaksi database
            // Memastikan semua operasi database berhasil atau tidak sama sekali
            $result = DB::transaction(function () use ($data) {

                // Buat atau perbarui sesi pembeli berdasarkan kode transaksi
                // UpdateOrCreate memastikan tidak ada duplikasi sesi pembeli
                $sesi_pembeli = SesiPembeli::updateOrCreate(
                    ['kode_sesi' => $data['kode_sesi']],
                    ['nama' => $data['nama_pelanggan']]
                );

                // Variabel untuk menyimpan total harga pesanan
                $totalHargaPesanan = 0;
                $itemsToLock = []; // ID Item yang akan di-kunci
                $itemsDetails = []; // Detail item yang sudah diproses
                
                // Proses Item & Kunci Stok Menu Item(Pessimistik Locking)
                foreach ($data['items'] as $item) {
                    // Kunci baris menu item agar tidak ada proses lain yang mengganggunya
                    // Ini adalah langkah anti-race condition(mencegah konflik data saat banyak proses berjalan bersamaan)
                    $menuItem = MenuItem::where('id', $item['menu_item_id'])->lockForUpdate()->first();

                    // Cek ketersediaan stok
                    if ($menuItem->qty < $item['jumlah'] || !$menuItem->is_tersedia) {
                        // Jika stock tidak cukup, gagalkan seluruh transaksi
                        throw new \Exception("Stok tidak mencukupi untuk item: " . $menuItem->nama);
                    }
                    
                    // Hitung total harga pesanan
                    $hargaItem = $menuItem->harga * $item['jumlah'];
                    $totalHargaPesanan += $hargaItem;

                    // Simpan item untuk dikurangi stoknya nanti
                    $itemsToLock[] = [
                        'menu_item' => $menuItem,
                        'jumlah' => $item['jumlah']
                    ];

                    // Siapkan detail item untuk dimasukkan ke pesanan
                    $itemsDetails[] = [
                        'menu_item_id' => $menuItem->id,
                        'jumlah' => $item['jumlah'],
                        'catatan' => $item['catatan'] ?? null,
                        'harga_saat_pesan' => $hargaItem,
                    ];
                }

                // Buat pesanan(Master)
                $pesanan = Pesanan::create([
                    'sesi_pembeli_id' => $sesi_pembeli->id,
                    'kode_pesanan' => 'ORD-' . Str::random(8),
                    'total_harga' => $totalHargaPesanan,
                    'status' => 'pending',
                ]);

                // Buat detail pesanan(Child)
                // attach() atau createMany() lebih efisien untuk memasukkan banyak data sekaligus
                $pesanan->detailPesanans()->createMany($itemsDetails);

                // Buat entri pembayaran terkait pesanan
                $pembayaran = Pembayaran::create([
                    'pesanan_id' => $pesanan->id,
                    'metode_pembayaran_id' => $data['metode_pembayaran_id'],
                    'jumlah_bayar' => $totalHargaPesanan,
                    'status' => 'pending',
                ]);

                // Kurangi stok menu item setelah semua pengecekan berhasil
                // ini dilakukan di akhir transaksi untuk memastikan konsistensi data
                foreach ($itemsToLock as $itemLock) {
                    $itemLock['menu_item']->decrement('qty', $itemLock['jumlah']);
                }

                // Kembalikan data pesanan beserta pembayarannya
                return [
                    'pesanan' => $pesanan->load('detailPesanans'),
                    'pembayaran' => $pembayaran,
                ];
            });

            // Kembalikan response sukses dengan data pesanan dan pembayaran
            return response()->json($result, 201);

        } 
        // Tangani error selama transaksi
        catch (\Exception $e) {
            // Tangani error dan kembalikan response gagal
            return response()->json([
                'message' => 'Gagal Membuat Pesanan', 'error' => $e->getMessage()], 422);
                // 422 Unprocessable Entity menandakan ada masalah dengan data yang dikirim
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Pesanan $pesanan)
    {
        return response()->json($pesanan->load(['sesiPembeli', 'detailPesanans.menuItem.tenant', 'pembayaran']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pesanan $pesanan)
    {
        $validated = $request->validate([
            'status_pesanan' => 'required|string|in:diproses,selesai,dibatalkan',
        ]);

        $pesanan->update($validated);

        return response()->json($pesanan);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

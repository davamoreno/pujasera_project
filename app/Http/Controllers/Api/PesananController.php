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

        // Jika user adalah Pemilik Tenant, batasi pesanan hanya untuk tenant tersebut
        if($user->role->nama === 'Pemilik Tenant'){
            $tenantId= $user->tenant->id;
            $query->whereHas('detailPesanans.menuItem', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            });
        }

        // Filter berdasarkan status_pesanan jika diberikan
        if ($request->has('status_pesanan') && $request->input('status_pesanan') != 'semua') {
            $query->where('status_pesanan', $request->input('status_pesanan'));
        }

        // Tambahkan filter lain sesuai kebutuhan
        $pesanan = $query->latest()->paginate(20);  
        return response()->json($pesanan);
    }

    /**
     * Store a newly created resource in storage.
     */
   public function store(StorePesananRequest $request)
    {
        $data = $request->validated();

        try {
            $result = DB::transaction(function () use ($data) {

                // Asumsi kolom di tabel sesi_pembeli adalah 'kode_sesi'
                // Jika kolomnya 'kode_transaksi', ganti di bawah ini
                $sesi_pembeli = SesiPembeli::updateOrCreate(
                    ['kode_sesi' => $data['kode_sesi']], 
                    ['nama' => $data['nama_pelanggan']]
                );

                $totalHargaPesanan = 0;
                $itemsToLock = [];
                $itemsDetails = [];

                foreach ($data['items'] as $item) {
                    $menuItem = MenuItem::where('id', $item['menu_item_id'])->lockForUpdate()->first();

                    if ($menuItem->qty < $item['jumlah'] || !$menuItem->is_tersedia) {
                        throw new \Exception("Stok tidak mencukupi untuk item: " . $menuItem->nama);
                    }

                    $hargaItem = $menuItem->harga * $item['jumlah'];
                    $totalHargaPesanan += $hargaItem;

                    $itemsToLock[] = [
                        'menu_item' => $menuItem,
                        'jumlah' => $item['jumlah']
                    ];

                    $itemsDetails[] = [
                        'menu_item_id' => $menuItem->id,
                        'jumlah' => $item['jumlah'],
                        'catatan' => $item['catatan'] ?? null,
                        'harga_saat_pesan' => $menuItem->harga, 
                    ];
                }

                $pesanan = Pesanan::create([
                    'sesi_pembeli_id' => $sesi_pembeli->id,
                    'kode_pesanan' => 'ORD-' . Str::random(8),
                    'total_harga' => $totalHargaPesanan,
                    'status_pesanan' => 'pending', 
                ]);

                // --- PERBAIKAN DI SINI ---
                // Ganti 'detailPesanan' (singular) menjadi 'detailPesanans' (plural)
                $pesanan->detailPesanans()->createMany($itemsDetails);

                $pembayaran = Pembayaran::create([
                    'pesanan_id' => $pesanan->id,
                    'metode_pembayaran_id' => $data['metode_pembayaran_id'],
                    'jumlah_bayar' => $totalHargaPesanan,
                    'status_pembayaran' => 'pending',
                ]);

                foreach ($itemsToLock as $itemLock) {
                    $itemLock['menu_item']->decrement('qty', $itemLock['jumlah']);
                }

                return [
                    // --- PERBAIKAN DI SINI ---
                    // Load relasi 'detailPesanans' (plural)
                    'pesanan' => $pesanan->load('detailPesanans'),
                    'pembayaran' => $pembayaran,
                ];
            });

            // Jika transaksi sukses, kirim response 201 Created
            return response()->json($result, 201);

        } 
        catch (\Exception $e) {
            // Jika ada error (misal stok habis atau error lain)
            return response()->json([
                'message' => 'Gagal Membuat Pesanan', 'error' => $e->getMessage()], 422);
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

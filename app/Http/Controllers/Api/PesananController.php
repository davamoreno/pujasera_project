<?php
// namespace App\Http\Controllers\Api;

// use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;
// use App\Http\Requests\StorePesananRequest;
// use App\Models\DetailPesanan;
// use App\Models\MenuItem;
// use App\Models\Pembayaran;
// use App\Models\Pesanan;
// use App\Models\SesiPembeli;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Str;
// use Illuminate\Support\Facades\Auth;
// use Illuminate\Http\JsonResponse;

// class PesananController extends Controller
// {
//     /**
//      * Display a listing of the resource.
//      */
//     public function index(Request $request) : JsonResponse
//     {
//         $user = Auth::user();
//         $query = Pesanan::with(['sesiPembeli', 'detailPesanans.menuItem', 'pembayaran']);

//         // Jika user adalah Pemilik Tenant, batasi pesanan hanya untuk tenant tersebut
//         if($user->role->nama === 'Pemilik Tenant'){
//             $tenantId= $user->tenant->id;
//             $query->whereHas('detailPesanans.menuItem', function ($query) use ($tenantId) {
//                 $query->where('tenant_id', $tenantId);
//             });
//         }

//         // Filter berdasarkan status_pesanan jika diberikan
//         if ($request->has('status_pesanan') && $request->input('status_pesanan') != 'semua') {
//             $query->where('status_pesanan', $request->input('status_pesanan'));
//         }

//         // Tambahkan filter lain sesuai kebutuhan
//         $pesanan = $query->latest()->paginate(20);  
//         return response()->json($pesanan);
//     }

//     /**
//      * Store a newly created resource in storage.
//      */
//    public function store(StorePesananRequest $request) : JsonResponse
//     {
//         $data = $request->validated();

//         try {
//             $result = DB::transaction(function () use ($data) {

//                 // Asumsi kolom di tabel sesi_pembeli adalah 'kode_sesi'
//                 // Jika kolomnya 'kode_transaksi', ganti di bawah ini
//                 $sesi_pembeli = SesiPembeli::updateOrCreate(
//                     ['kode_sesi' => $data['kode_sesi']], 
//                     ['nama' => $data['nama_pelanggan']]
//                 );
                
//                 $totalHargaPesanan = 0;
//                 $itemsToLock = [];
//                 $itemsDetails = [];

//                 foreach ($data['items'] as $item) {
//                     $menuItem = MenuItem::where('id', $item['menu_item_id'])->lockForUpdate()->first();
                    
//                     if ($menuItem->qty < $item['jumlah'] || !$menuItem->is_tersedia) {
//                         throw new \Exception("Stok tidak mencukupi untuk item: " . $menuItem->nama);
//                     }

//                     $hargaItem = $menuItem->harga * $item['jumlah'];
//                     $totalHargaPesanan += $hargaItem;

//                     $itemsToLock[] = [
//                         'menu_item' => $menuItem,
//                         'jumlah' => $item['jumlah']
//                     ];

//                     $itemsDetails[] = [
//                         'menu_item_id' => $menuItem->id,
//                         'jumlah' => $item['jumlah'],
//                         'catatan' => $item['catatan'] ?? null,
//                         'harga_saat_pesan' => $menuItem->harga,
//                         'nama_menu_snapshot' => $menuItem->nama,
//                     ];
//                 }

//                 $pesanan = Pesanan::create([
//                     'sesi_pembeli_id' => $sesi_pembeli->id,
//                     'kode_pesanan' => Pesanan::randomKodePesanan(),
//                     'total_harga' => $totalHargaPesanan,
//                     'tenant_id' => $data['tenant_id'],
//                     'status_pesanan' => 'pending', 
//                     'nama_pembeli_snapshot' => $sesi_pembeli->nama,
//                 ]);

//                 // --- PERBAIKAN DI SINI ---
//                 // Ganti 'detailPesanan' (singular) menjadi 'detailPesanans' (plural)
//                 $pesanan->detailPesanans()->createMany($itemsDetails);

//                 $pembayaran = Pembayaran::create([
//                     'pesanan_id' => $pesanan->id,
//                     'reference_id' => 'PAY-' . Str::random(10),
//                     'metode_pembayaran_id' => $data['metode_pembayaran_id'],
//                     'jumlah_bayar' => $totalHargaPesanan,
//                     'status_pembayaran' => 'pending',
//                     'external_id' => 'EXT-' . Str::random(12),
//                 ]);

//                 foreach ($itemsToLock as $itemLock) {
//                     $itemLock['menu_item']->decrement('qty', $itemLock['jumlah']);
//                 }

//                 return [
//                     // --- PERBAIKAN DI SINI ---
//                     // Load relasi 'detailPesanans' (plural)
//                     'pesanan' => $pesanan->load('detailPesanans'),
//                     'pembayaran' => $pembayaran,
//                 ];
//             });

//             // Jika transaksi sukses, kirim response 201 Created
//             return response()->json($result, 201);

//         } 
//         catch (\Exception $e) {
//             // Jika ada error (misal stok habis atau error lain)
//             return response()->json([
//                 'message' => 'Gagal Membuat Pesanan', 
//                 'error' => $e->getMessage()], 422);
//         }
//     }

//     /**
//      * Display the specified resource.
//      */
//     public function show(Pesanan $pesanan) : JsonResponse
//     {
//         return response()->json($pesanan->load(['sesiPembeli', 'detailPesanans.menuItem.tenant', 'pembayaran']));
//     }

//     /**
//      * Update the specified resource in storage.
//      */
//     public function update(Request $request, Pesanan $pesanan) : JsonResponse
//     {
//         $validated = $request->validate([
//             'status_pesanan' => 'required|string|in:diproses,selesai,dibatalkan',
//         ]);

//         $pesanan->update($validated);

//         return response()->json($pesanan);
//     }

//     /**
//      * Remove the specified resource from storage.
//      */
//     public function destroy(string $id)
//     {
//         //
//     }

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StorePesananRequest;
use App\Models\MenuItem;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Models\SesiPembeli;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;


class PesananController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request) : JsonResponse
    {
        $user = Auth::user();
        $query = Pesanan::with(['sesiPembeli', 'detailPesanans.menuItem', 'pembayaran']);

        // Jika user adalah Pemilik Tenant, batasi pesanan hanya untuk tenant tersebut
        if($user->role->nama === 'Pemilik Tenant' && $user->tenant){
            $tenantId = $user->tenant->id;
            
            // Filter pesanan milik tenant ini saja
            $query->where('tenant_id', $tenantId); 
            // ATAU pakai whereHas jika ingin lebih spesifik ke itemnya
        }

        // Filter berdasarkan status_pesanan jika diberikan
        if ($request->has('status_pesanan') && $request->input('status_pesanan') != 'semua') {
            $query->where('status_pesanan', $request->input('status_pesanan'));
        }

        // Filter tanggal (Opsional, sangat berguna buat admin)
        if ($request->has('date')) {
             $query->whereDate('created_at', $request->date);
        }

        $pesanan = $query->latest()->paginate(20);  
        return response()->json($pesanan);
    }

    /**
     * Store a newly created resource in storage.
     */
   public function store(StorePesananRequest $request) : JsonResponse
    {
        // Pastikan StorePesananRequest memvalidasi 'kode_sesi' & 'nama_pelanggan'
        $data = $request->validated();

        try {
            $result = DB::transaction(function () use ($data) {

                // 1. Handle Sesi Pembeli (Update or Create)
                $sesi_pembeli = SesiPembeli::updateOrCreate(
                    ['kode_sesi' => $data['kode_sesi']], 
                    ['nama' => $data['nama_pelanggan']]
                );
                
                $totalHargaPesanan = 0;
                $itemsToLock = [];
                $itemsDetails = [];

                // 2. Loop Items & Lock Stok
                foreach ($data['items'] as $item) {
                    // Lock for update mencegah race condition saat stok menipis
                    $menuItem = MenuItem::where('id', $item['menu_item_id'])->lockForUpdate()->first();
                    
                    if (!$menuItem) {
                        throw new \Exception("Menu item tidak ditemukan.");
                    }

                    if ($menuItem->qty < $item['jumlah'] || !$menuItem->is_tersedia) {
                        throw new \Exception("Stok tidak mencukupi atau habis untuk item: " . $menuItem->nama);
                    }

                    $hargaItem = $menuItem->harga * $item['jumlah'];
                    $totalHargaPesanan += $hargaItem;

                    $itemsToLock[] = [
                        'menu_item' => $menuItem,
                        'jumlah' => $item['jumlah']
                    ];

                    // Siapkan data detail untuk bulk insert
                    $itemsDetails[] = [
                        'menu_item_id' => $menuItem->id,
                        'jumlah' => $item['jumlah'],
                        'catatan' => $item['catatan'] ?? null,
                        // SNAPSHOT HARGA & NAMA (Penting!)
                        'harga_saat_pesan' => $menuItem->harga,
                        'nama_menu_snapshot' => $menuItem->nama,
                    ];
                }

                // 3. Buat Pesanan Utama
                $pesanan = Pesanan::create([
                    'sesi_pembeli_id' => $sesi_pembeli->id,
                    'kode_pesanan' => Pesanan::randomKodePesanan(), // Pastikan method ini ada di Model Pesanan
                    'total_harga' => $totalHargaPesanan,
                    'tenant_id' => $data['tenant_id'],
                    'status_pesanan' => 'pending', 
                    // SNAPSHOT NAMA PEMBELI
                    'nama_pembeli_snapshot' => $sesi_pembeli->nama,
                ]);

                // 4. Simpan Detail Pesanan
                $pesanan->detailPesanans()->createMany($itemsDetails);

                // 5. Buat Data Pembayaran Awal
                $pembayaran = Pembayaran::create([
                    'pesanan_id' => $pesanan->id,
                    'reference_id' => 'PAY-' . strtoupper(Str::random(10)),
                    'metode_pembayaran_id' => $data['metode_pembayaran_id'],
                    'jumlah_bayar' => $totalHargaPesanan,
                    'status_pembayaran' => 'pending',
                    'external_id' => 'EXT-' . strtoupper(Str::random(12)), // Biasanya dari Xendit/Midtrans
                ]);

                // 6. Kurangi Stok (Decrement)
                foreach ($itemsToLock as $itemLock) {
                    $itemLock['menu_item']->decrement('qty', $itemLock['jumlah']);
                }

                return [
                    'pesanan' => $pesanan->load('detailPesanans'), // Load relasi detail
                    'pembayaran' => $pembayaran,
                ];
            });

            return response()->json([
                'message' => 'Pesanan berhasil dibuat',
                'data' => $result
            ], 201);

        } 
        catch (\Exception $e) {
            // Rollback otomatis terjadi jika ada Exception di dalam DB::transaction
            return response()->json([
                'message' => 'Gagal Membuat Pesanan', 
                'error' => $e->getMessage()
            ], 422); // Unprocessable Entity
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Pesanan $pesanan) : JsonResponse
    {
        // Pastikan user berhak melihat pesanan ini (Opsional tapi direkomendasikan)
        // $this->authorize('view', $pesanan); 

        return response()->json($pesanan->load(['sesiPembeli', 'detailPesanans.menuItem.tenant', 'pembayaran']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pesanan $pesanan) : JsonResponse
    {
        $validated = $request->validate([
            'status_pesanan' => 'required|string|in:diproses,selesai,dibatalkan',
        ]);

        // Logic tambahan jika dibatalkan -> Kembalikan stok
        if ($validated['status_pesanan'] === 'dibatalkan' && $pesanan->status_pesanan !== 'dibatalkan') {
            foreach ($pesanan->detailPesanans as $detail) {
                $detail->menuItem->increment('qty', $detail->jumlah);
            }
        }

        $pesanan->update($validated);

        return response()->json([
            'message' => 'Status pesanan diperbarui',
            'data' => $pesanan
        ]);
    }

    public function showByKode($kode)
    {
        $pesanan = Pesanan::where('kode_pesanan', $kode)
            ->with(['detailPesanans.menuItem', 'pembayaran', 'tenant']) // Load relasi penting
            ->first();

        if (!$pesanan) {
            return response()->json(['message' => 'Pesanan tidak ditemukan'], 404);
        }

        return response()->json(['data' => $pesanan]);
    }

    public function history(Request $request)
    {
        // Kita ambil kode_sesi dari query parameter ?kode_sesi=...
        $kodeSesi = $request->query('kode_sesi');

        $sesi = SesiPembeli::where('kode_sesi', $kodeSesi)->first();

        if (!$sesi) {
            return response()->json(['message' => 'Kode sesi diperlukan'], 400);
        }

        try {
            // PERHATIKAN NAMA RELASI DI SINI
            // Pastikan di Model Pesanan.php, nama functionnya adalah 'detailPesanans' (camelCase)
            // Jika di Model Anda namanya 'detail_pesanans' (snake_case), ganti di bawah ini.
            
            $pesanans = Pesanan::where('sesi_pembeli_id', $sesi->id)
                ->with([
                    'tenant', 
                    'detailPesanans.menuItem', // <--- Cek ini, pastikan 'detailPesanans' ada di Model Pesanan
                    'pembayaran'
                ]) 
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json(['data' => $pesanans]);
            
        } catch (\Exception $e) {
            // Ini akan menampilkan pesan error asli biar kita tau salahnya dimana
            return response()->json([
                'message' => 'Server Error',
                'error' => $e->getMessage() 
            ], 500);
        }
    }
}

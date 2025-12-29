<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Pembayaran;
use App\Models\Tenant;
use App\Models\MenuItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // Helper private untuk filter tanggal
    private function applyDateFilter($query, $request, $column = 'waktu_bayar')
    {
        if ($request->has('month') && $request->has('year')) {
            $query->whereMonth($column, $request->month)
                  ->whereYear($column, $request->year);
        } elseif ($request->has('year')) {
             $query->whereYear($column, $request->year);
        }
        return $query;
    }

    /**
     * Endpoint baru: Mendapatkan daftar bulan yang ada transaksinya.
     * Gunakan ini untuk mengisi Dropdown di Frontend.
     */
    public function availableMonths()
    {
        $months = Pembayaran::select(
                DB::raw('YEAR(waktu_bayar) as year'),
                DB::raw('MONTH(waktu_bayar) as month')
            )
            ->where('status_pembayaran', 'lunas')
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'value' => $item->month,
                    'year' => $item->year,
                    'label' => Carbon::create()->month($item->month)->translatedFormat('F') . ' ' . $item->year
                ];
            });

        return response()->json($months);
    }

    public function stats(Request $request) : JsonResponse
    {
        $user = Auth::user();
        
        // Query dasar pembayaran lunas
        $pembayaranQuery = Pembayaran::where('status_pembayaran', 'lunas');

        // Terapkan Filter Bulan/Tahun
        $this->applyDateFilter($pembayaranQuery, $request, 'waktu_bayar');

        // Variabel untuk lock low stock
        $lowStockItems = [];

        if($user->role->nama === 'Admin')
        {
            // Clone query agar tidak bentrok saat count dan sum
            $totalIncome = (clone $pembayaranQuery)->sum('jumlah_bayar');
            $totalTransactions = (clone $pembayaranQuery)->count();
            
            $totalTenants = Tenant::count();
            $totalMenu = MenuItem::count();
        }
        else {
            if(!$user->tenant){
                return response()->json(['message' => 'User tidak terhubung dengan tenant.'], 400);
            }

            $tenantId = $user->tenant->id;
            
            $lowStockItems[] = MenuItem::where('tenant_id', $tenantId)
            ->where('qty', '<', 5)
            ->where('is_tersedia', true)
            ->select('nama', 'qty')->get();

            // Filter pembayaran spesifik tenant ini
            $pembayaranQuery->whereHas('pesanan', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            });

            $totalIncome = (clone $pembayaranQuery)->sum('jumlah_bayar');
            $totalTransactions = (clone $pembayaranQuery)->count();
            
            $totalMenu = MenuItem::where('tenant_id', $tenantId)->count();
            $totalTenants = 1; 
        }

        return response()->json([
            'total_income' => (float) $totalIncome,
            'total_tenants' => $totalTenants,
            'total_menu_items' => $totalMenu,
            'total_transactions' => $totalTransactions,
            'low_stock_items' => $lowStockItems
        ], 200);
    }

    public function recap(Request $request)
    {
        $user = Auth::user();
        
        // Ambil parameter filter
        $month = $request->input('month');
        $year = $request->input('year');

        // Query Tenant
        $query = Tenant::with(['staff'])
            ->with(['pesanans' => function($q) use ($month, $year) {
                // Filter Pesanan berdasarkan Pembayaran Lunas & Tanggal
                $q->whereHas('pembayaran', function($p) use ($month, $year) {
                    $p->where('status_pembayaran', 'lunas');
                    
                    // Filter Tanggal di sini
                    if ($month && $year) {
                        $p->whereMonth('waktu_bayar', $month)->whereYear('waktu_bayar', $year);
                    } elseif ($year) {
                        $p->whereYear('waktu_bayar', $year);
                    }
                })->with('detailPesanans'); 
            }]);

        // Filter Tenant spesifik jika bukan Admin
        if ($user->role->nama !== 'Admin') {
            if(!$user->tenant) return response()->json(['message' => 'No tenant'], 400);
            $query->where('id', $user->tenant->id);
        }

        // Pagination
        $tenants = $query->paginate(10);

        $recapData = $tenants->map(function($tenant) use ($request) {
            // Hitung Total Penjualan (Hanya dari pesanan yang sudah difilter di 'with' di atas)
            $totalPenjualan = $tenant->pesanans->sum('total_harga');

            // Cari Menu Populer
            $allSoldItems = $tenant->pesanans->flatMap->detailPesanans;

            $bestSeller = $allSoldItems->groupBy('menu_item_id')
                ->map(function($group) {
                    return [
                        'nama' => $group->first()->nama_menu_snapshot ?? 'Item',
                        'total_qty' => $group->sum('jumlah'),
                        'harga' => $group->first()->harga_saat_pesan
                    ];
                })
                ->sortByDesc('total_qty')
                ->first();

            if (!$bestSeller) {
                $bestSeller = ['nama' => '-', 'total_qty' => 0, 'harga' => 0];
            }
            
            // Nama bulan dinamis
            $bulanLabel = ($request->month && $request->year) 
                ? Carbon::create((int) $request->year, (int) $request->month, 1)->translatedFormat('F') 
                : 'Semua Waktu';

            return [
                'bulan' => $bulanLabel,
                'tenant_name' => $tenant->nama,
                'menu_populer' => $bestSeller['nama'],
                'harga_menu' => $bestSeller['harga'],
                'terjual' => $bestSeller['total_qty'],
                'total_penjualan' => $totalPenjualan
            ];
        });

        // Sorting array hasil mapping (bukan query DB)
        $sortedRecap = $recapData->sortByDesc('total_penjualan')->values();

        return response()->json($sortedRecap);
    }
}
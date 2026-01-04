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

        // Variabel default
        $lowStockItems = [];
        $topItems = [];

        if($user->role->nama === 'Admin')
        {
            // Admin melihat global (opsional)
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
            
            // --- PERBAIKAN 1: LOW STOCK ---
            // Wajib select 'id' agar v-for di Vue berfungsi normal (key="item.id")
            $lowStockItems = MenuItem::where('tenant_id', $tenantId)
                ->where('qty', '<', 5)
                ->where('is_tersedia', true)
                ->select('id', 'nama', 'qty') // <--- TAMBAHKAN 'id' DISINI
                ->get();

            // Filter pembayaran spesifik tenant ini
            $pembayaranQuery->whereHas('pesanan', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            });

            $totalIncome = (clone $pembayaranQuery)->sum('jumlah_bayar');
            $totalTransactions = (clone $pembayaranQuery)->count();
            $totalMenu = MenuItem::where('tenant_id', $tenantId)->count();
            $totalTenants = 1; 

            // --- PERBAIKAN 2: CHART (TOP ITEMS) ---
            // Kita ambil ID pesanan yang lunas dulu biar query lebih ringan
            $lunasOrderIds = (clone $pembayaranQuery)->pluck('pesanan_id')->toArray();

            if (!empty($lunasOrderIds)) {
                $topItems = MenuItem::where('tenant_id', $tenantId)
                    ->whereHas('detailPesanans', function($q) use ($lunasOrderIds) {
                         $q->whereIn('pesanan_id', $lunasOrderIds);
                    })
                    ->withSum(['detailPesanans' => function($q) use ($lunasOrderIds) {
                        $q->whereIn('pesanan_id', $lunasOrderIds);
                    }], 'jumlah')
                    ->orderByDesc('detail_pesanans_sum_jumlah')
                    ->take(5)
                    ->get()
                    ->map(function($item) {
                        return [
                            'nama' => $item->nama,
                            'total_qty' => (int) ($item->detail_pesanans_sum_jumlah ?? 0)
                        ];
                    });
            } else {
                $topItems = []; // Kalau belum ada transaksi lunas, kosongkan
            }
        }

        return response()->json([
            'total_income' => (float) $totalIncome,
            'total_tenants' => $totalTenants,
            'total_menu_items' => $totalMenu,
            'total_transactions' => $totalTransactions,
            'low_stock_items' => $lowStockItems,
            'top_items' => $topItems 
        ], 200);
    }

    public function recap(Request $request)
    {
        $user = Auth::user();
        
        $month = $request->input('month');
        $year = $request->input('year');

        $query = Tenant::with(['staff'])
            ->with(['pesanans' => function($q) use ($month, $year) {
                $q->whereHas('pembayaran', function($p) use ($month, $year) {
                    $p->where('status_pembayaran', 'lunas');
                    
                    if ($month && $year) {
                        $p->whereMonth('waktu_bayar', $month)->whereYear('waktu_bayar', $year);
                    } elseif ($year) {
                        $p->whereYear('waktu_bayar', $year);
                    }
                })->with('detailPesanans'); 
            }]);

        if ($user->role->nama !== 'Admin') {
            if(!$user->tenant) return response()->json(['message' => 'No tenant'], 400);
            $query->where('id', $user->tenant->id);
        }

        $tenants = $query->paginate(10);

        $recapData = $tenants->map(function($tenant) use ($request) {
            $totalPenjualan = $tenant->pesanans->sum('total_harga');
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

        $sortedRecap = $recapData->sortByDesc('total_penjualan')->values();

        return response()->json($sortedRecap);
    }
}
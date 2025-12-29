<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Tenant;

class ReportController extends Controller
{
    public function incomeByTenant(Request $request, Tenant $tenant) : JsonResponse
    {
        $totalIncome = $tenant->pembayarans()
            ->where('status_pembayaran', 'lunas')
            ->sum('jumlah_bayar');
        
        return response()->json([
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->nama,
            'total_income' => (float) $totalIncome
        ], 200);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSesiPembeliRequest;
use App\Http\Resources\SesiPembeliResource;
use App\Models\SesiPembeli;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SesiPembeliController extends Controller
{
    public function store(StoreSesiPembeliRequest $request)
    {
        $sesi = SesiPembeli::create([
            'kode_sesi' => Str::upper(Str::random(8)),
            'nama'      => $request->nama,
            'is_closed' => false,
            // Best Practice: Gunakan now()->addHours() yang jelas
            'expired_at' => now()->addHours(5), 
        ]);

        return response()->json([
            'message' => 'Sesi pembeli dibuat',
            'data'    => new SesiPembeliResource($sesi)
        ], 201);
    }

    public function show(Request $request, $identifier)
    {
        $sesi = $this->findSession($identifier);

        if (!$sesi) {
            return response()->json(['message' => 'Sesi tidak ditemukan'], 404);
        }

        // Logic: Lazy Expiration (Cek kedaluwarsa saat data diakses)
        // Cek apakah expired_at sudah lewat dari waktu sekarang
        if ($sesi->expired_at && now()->greaterThan($sesi->expired_at)) {
            
            // Hanya update jika belum closed, biar gak query update berkali-kali
            if (!$sesi->is_closed) {
                $sesi->update(['is_closed' => true]);
            }

            return response()->json([
                'message' => 'Sesi telah kedaluwarsa', 
                'data'    => new SesiPembeliResource($sesi)
            ], 410); // 410 Gone sangat tepat untuk resource yang dulunya ada tapi sekarang hilang/expired
        }

        return new SesiPembeliResource($sesi);
    }

    public function close(Request $request, $identifier)
    {
        $sesi = $this->findSession($identifier);

        if (!$sesi) {
            return response()->json(['message' => 'Sesi tidak ditemukan'], 404);
        }

        $sesi->update(['is_closed' => true]);

        return response()->json([
            'message' => 'Sesi ditutup', 
            'data'    => new SesiPembeliResource($sesi)
        ]);
    }

    // --- Helper Private untuk DRY (Don't Repeat Yourself) ---
    private function findSession($identifier)
    {
        // Support ID (integer) atau Kode (string)
        return is_numeric($identifier)
            ? SesiPembeli::find($identifier)
            : SesiPembeli::where('kode_sesi', $identifier)->first();
    }
}
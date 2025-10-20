<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use Illuminate\Http\Request;
use App\Http\Requests\StoreStaffRequest;
use App\Http\Requests\UpdateStaffRequest;
use Illuminate\Support\Facades\Hash; 
use Illuminate\Support\Facades\Auth;

class StaffController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $staff = Staff::with('role')->get();
        return response()->json($staff);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStaffRequest $request)
    {
        $validatedData = $request->validated();
        $validatedData['password'] = Hash::make($validatedData['password']);
        $staff = Staff::create($validatedData);     
        return response()->json($staff, 201); 
    }

    /**
     * Display the specified resource.
     */
    public function show(Staff $staff)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStaffRequest $request, Staff $staff)
    {
       $validatedData = $request->validated();

        // Cek apakah ada password baru yang dikirim. Jika ada, hash password tersebut.
        if (isset($validatedData['password'])) {
            $validatedData['password'] = Hash::make($validatedData['password']);
        }

        // Update data staff.
        $staff->update($validatedData);

        // Kembalikan data staff yang sudah di-update beserta relasi rolenya.
        return response()->json($staff->load('role'));
    }
    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Staff $staff)
    {
        // Berkat Route Model Binding, Laravel otomatis menemukan staff berdasarkan ID.
        
        // Cek agar user tidak bisa menghapus dirinya sendiri (opsional tapi best practice)
        if (Auth::id() === $staff->id) {
            return response()->json(['message' => 'Anda tidak bisa menghapus akun Anda sendiri.'], 403);
        }

        // Hapus data staff dari database.
        $staff->delete();

        // Kembalikan response kosong dengan status 204 No Content.
        // Ini adalah status HTTP yang tepat untuk menandakan aksi delete berhasil.
        return response()->json(['message' => 'Akun ' . $staff->nama . ' Berhasil dihapus'], 201);
    }
}

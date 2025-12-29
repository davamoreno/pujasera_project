<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use Illuminate\Http\Request;
use App\Http\Requests\StoreStaffRequest;
use App\Http\Requests\UpdateStaffRequest;
use Illuminate\Support\Facades\Hash; 
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\Models\Role;

class StaffController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Staff::with('role');
        if($request->has('search') && $request->input('search') != '')
        {
            $search = $request->query('search');
            $query->where('nama', 'like', "%{$search}%");
        }

        if($request->has('sort_by') && $request->has('sort_dir'))
        {
            $sortBy = $request->input('sort_by', 'id');
            $sortDir = $request->input('sort_dir', 'asc');
            $allowedSorts = ['id', 'nama', 'role.nama', 'created_at', 'updated_at'];
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortDir);
            }
        }

        if($request->boolean('available_for_tenant')) {
            $tenantRole = Role::where('nama', 'Pemilik Tenant')->first();
            if ($tenantRole) {
                $query->where('role_id', $tenantRole->id);
            }
            $query->whereDoesntHave('tenant');
        }

        $per_page = $request->input('per_page', 10);
        if($per_page) {
            $staff = $query->paginate($per_page);
            $staff->appends($request->only(['search', 'sort_by', 'sort_dir', 'per_page']));
            return response()->json($staff);
        }

        $staff = $query->paginate($per_page);
        return response()->json($staff);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStaffRequest $request)
    {
        $validatedData = $request->validated();
        $validatedData['password'] = Hash::make($validatedData['password']);
        if ($request->hasFile('gambar_url')) {
            $path = $request->file('gambar_url')->store('public/staff_images');
            $validatedData['gambar_url'] = Storage::url($path);
        }
        $staff = Staff::create($validatedData);     
        return response()->json($staff, 201); 
    }

    /**
     * Display the specified resource.
     */
    public function show(Staff $staff)
    {
        return response()->json($staff->load('role'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStaffRequest $request, Staff $staff)
    {
       $validatedData = $request->validated();

        // Cek apakah ada password baru yang dikirim. Jika ada, hash password tersebut.
        if ($request->hasFile('gambar_url')) {
            // Hapus gambar lama jika ada
            if ($staff->gambar_url) {
                $oldImagePath = str_replace(Storage::url(''), '', $staff->gambar_url);

                Storage::disk('public')->delete($oldImagePath);
            }
            // Simpan gambar baru   
            $path = $request->file('gambar_url')->store('public/staff_images');
            $validatedData['gambar_url'] = Storage::url($path);
        }

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

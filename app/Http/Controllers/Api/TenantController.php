<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Models\Pembayaran;
use App\Http\Requests\StoreTenantRequest;
use App\Http\Requests\UpdateTenantRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Enums\TenantStatus;
use App\Http\Resources\TenantResource;

class TenantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Build the query with eager loading
        $query = Tenant::query()->with('staff.role');

        // Apply search filter
        if($request->has('search') && $request->input('search') != '')
        {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhereHas('staff', function ($staffQuery) use ($search) {
                      $staffQuery->where('nama', 'like', "%{$search}%");
                  });
            });
        }

        if($request->has('sort_by') && $request->has('sort_dir'))
        {
            // Apply sorting
            $sortBy = $request->input('sort_by', 'id');
            //  Default sort direction is ascending
            $sortDir = $request->input('sort_dir', 'asc');
            // Prevent sorting by non-allowed fields
            $allowedSorts = ['id', 'nama', 'created_at', 'updated_at'];
            //  If the requested sortBy is not in allowedSorts, default to 'id'
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortDir);
            }
        }

        // Apply pagination
        $perPage = $request->input('per_page', 10);
        //  Get paginated results
        $tenants = $query->paginate($perPage);
        $tenants->appends($request->only(['search', 'sort_by', 'sort_dir', 'per_page']));
        return response()->json($tenants);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTenantRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('gambar_url')) {
            $data['gambar_url'] = $request->file('gambar_url')->store('tenants_images', 'public');
        }
        
        $tenant = Tenant::create($data);
        return response()->json($tenant->load('staff.role'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Tenant $tenant)
    {
        return response()->json($tenant->load('staff.role'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTenantRequest $request, Tenant $tenant)
    {
        $data = $request->validated();
       // Handle update file gambar
        if ($request->hasFile('gambar_url')) {
            // 1. Hapus gambar lama jika ada
            if ($tenant->gambar_url) {
                Storage::disk('public')->delete($tenant->gambar_url);
            }

            // 2. Upload gambar baru
            $data['gambar_url'] = $request->file('gambar_url')->store('tenants_images', 'public');
        }


        $tenant->update($data);
        return response()->json($tenant->load('staff.role'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tenant $tenant)
    {
        $tenant->delete();
        return response()->json(null, 204);
    }

    /**
     * Display a listing of the resource for public access.
     */
    public function indexPublic(Request $request)
    {
        $query = Tenant::with('staff.role');

        // Search by nama
        if ($request->search) {
            $query->where('nama', 'like', '%' . $request->search . '%');
        }

        // Filter status halal
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Filter status operasional
        if ($request->operasional) {
            $query->where('status_operasional', $request->operasional);
        }

        // Pagination
        $tenants = $query->paginate(8);

        return TenantResource::collection($tenants);
    }

    public function updateStatus(Request $request)
    {
        $user = Auth::user();
        $tenant = $user->tenant;

        if(!$tenant) {
            return response()->json(['message' => 'Tenant tidak ditemukan untuk user ini.'], 404);
        }

        $request->validate([
            'status_operasional' => 'required|in:' . implode(',', [TenantStatus::OPEN->value, TenantStatus::CLOSED->value, TenantStatus::BREAK->value, TenantStatus::BUSY->value, TenantStatus::PERMANENTLY_CLOSED->value])
        ]);

        $tenant->update(['status_operasional' => $request->status_operasional]);

        return response()->json([
            'message' => 'Status toko berhasil diperbarui.',
            'status_operasional' => $tenant->status_operasional
        ]);
    }

    public function getMenuItem(Tenant $tenant)
    {
        $menuItems = $tenant->menuItems()->where('is_tersedia', true)->get();
        return response()->json($menuItems);    
    }

    public function showPublic(Tenant $tenant)
    {
        $tenant->load([
            'staff.role', 
            'menuItems' => function($query) {
                // Opsional: Urutkan menu terbaru atau berdasarkan kategori
                $query->orderBy('kategori_id', 'asc') 
                      ->orderBy('is_tersedia', 'desc'); // Yang tersedia di atas
            },
            'menuItems.kategori' // Jangan lupa load Kategori biar bisa difilter di frontend
        ]);

        return new TenantResource($tenant);    
    }
}

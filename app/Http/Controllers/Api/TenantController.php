<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Http\Requests\StoreTenantRequest;
use App\Http\Requests\UpdateTenantRequest;
use Illuminate\Support\Facades\Storage;

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
            $path = $request->file('gambar_url')->store('public/tenant_images');
            $data['gambar_url'] = Storage::url($path);
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
        if ($request->hasFile('gambar_url')) {
            // 1. Hapus gambar lama jika ada
            if ($tenant->gambar_url) {
                $oldPath = str_replace(Storage::url(''), 'public/', $tenant->gambar_url);
                Storage::delete($oldPath);
            }

            // 2. Upload gambar baru
            $path = $request->file('gambar_url')->store('public/tenant_images');
            $data['gambar_url'] = Storage::url($path);
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
    public function indexPublic()
    {
        $tenants = Tenant::with('staff.role')->paginate(10);
        return response()->json($tenants);
    }
}

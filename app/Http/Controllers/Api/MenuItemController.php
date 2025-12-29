<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMenuItemRequest;
use App\Http\Requests\UpdateMenuItemRequest;
use App\Models\MenuItem;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Resources\MenuItemResource;

class MenuItemController extends Controller
{
    /**
     * Instantiate a new MenuItemController instance.
     */
    // public function __construct()
    // {
    //     $this->authorizeResource(MenuItem::class, 'menu_item');
    // }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', MenuItem::class);
        $user = Auth::user();

        $query = MenuItem::query()->with('tenant', 'kategori');
        if($user->role->nama === 'Admin'){
            if ($request->has('tenant_id')) {
                $query->where('tenant_id', $request->query('tenant_id'));
            }
        }else{
            $tenantId = $user->tenant->id;
            $query->where('tenant_id', $tenantId);
        }

        $menuItems = $query->latest()->paginate(10);

        return MenuItemResource::collection($menuItems);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMenuItemRequest $request)
    {
        $this->authorize('create', MenuItem::class);
        $data = $request->validated();
        $user = Auth::user();

        if($user->role->nama === 'Pemilik Tenant'){
            $data['tenant_id'] = $user->tenant->id;
        }

        if ($request->hasFile('gambar_url')) {
            $data['gambar_url'] = $request->file('gambar_url')->store('menu_images', 'public');
        }

        $menuItem = MenuItem::create($data);
        return new MenuItemResource($menuItem->load('tenant','kategori'));
    }

    /**
     * Display the specified resource.
     */
    public function show(MenuItem $menuItem)
    {
        $this->authorize('view', $menuItem);
        return new MenuItemResource($menuItem->load('tenant','kategori'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMenuItemRequest $request, MenuItem $menuItem)
    {
        $this->authorize('update', $menuItem);
        $data = $request->validated();

        // Handle update file gambar
        if ($request->hasFile('gambar_url')) {
            // 1. Hapus gambar lama jika ada
            if ($menuItem->gambar_url) {
                Storage::disk('public')->delete($menuItem->gambar_url);
            }

            // 2. Upload gambar baru
            $data['gambar_url'] = $request->file('gambar_url')->store('menu_images', 'public');
        }

        $menuItem->update($data);

        return new MenuItemResource($menuItem->load('tenant','kategori'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MenuItem $menuItem)
    {  
        $this->authorize('delete', $menuItem);
        if($menuItem->gambar_url){
            Storage::disk('public')->delete($menuItem->gambar_url);
        }

        $menuItem->delete();
        return response()->json(null, 204);
    }
}


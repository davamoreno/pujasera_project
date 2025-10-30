<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMenuItemRequest;
use App\Http\Requests\UpdateMenuItemRequest;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MenuItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    // public function index()
    // {
    //   $user = Auth::user();

    //   if($user->role->nama === 'Admin'){
    //     $menuItems = MenuItem::with('tenant','kategori')->get(); 
    //   }else{
    //     $tenantId= $user->tenant->id;
    //     $menuItems = MenuItem::where('tenant_id',$tenantId)
    //     ->with('kategori')
    //     ->get();
    //   }
    //   return response()->json($menuItems);
    // }

    public function index(Tenant $tenant)
    {
        $menuItems = $tenant->menuItems()->with('kategori')->get();

        if($menuItems->isEmpty() ){
            return response()->json(['message' => 'No menu items found for this tenant.'], 404);
        }
        
        return response()->json($menuItems);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMenuItemRequest $request, Tenant $tenant)
    {
        $this->authorize('create', MenuItem::class);

        $menuItem = $tenant->where('id', $request->id)->first();
        $data = $request->validated();
        $user = Auth::user();

        if($user->role->nama === 'Pemilik Tenant'){
            $data['tenant_id']=$user->tenant->id;
        }

        if ($request->hasFile('gambar_url')) {
            $path = $request->file('gambar_url')->store('public/menu_images');
            $data['gambar_url'] = Storage::url($path);
        }

        $menuItem = MenuItem::create($data);
        return response()->json($menuItem->load('tenant','kategori'),201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Tenant $tenant, MenuItem $menuItem)
    {
        $this->authorize('view', $menuItem);

        $menuItem = $tenant->menuItems()->where('id', $menuItem->id)->first();
        return response()->json($menuItem->load('tenant','kategori'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMenuItemRequest $request, Tenant $tenant, MenuItem $menuItem)
    {
        $this->authorize('update', $menuItem);

        $menuItem = $tenant->menuItems()->where('id', $menuItem->id)->first();

        $data = $request->validated();

        // Handle update file gambar
        if ($request->hasFile('gambar_url')) {
            // 1. Hapus gambar lama jika ada
            if ($menuItem->gambar_url) {
                $oldPath = str_replace(Storage::url(''), 'public/', $menuItem->gambar_url);
                Storage::delete($oldPath);
            }

            // 2. Upload gambar baru
            $path = $request->file('gambar_url')->store('public/menu_images');
            $data['gambar_url'] = Storage::url($path);
        }

        $menuItem->update($data);

        return response()->json($menuItem->load('tenant', 'kategori'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tenant $tenant,MenuItem $menuItem)
    {
        $this->authorize('delete', $menuItem);

        $menuItem = $tenant->menuItems()->where('id', $menuItem->id)->first();

        if($menuItem->gambar_url){
            $oldPath=str_replace(Storage::url(''),'public/', $menuItem->gambar_url);
                Storage::delete($oldPath);
            }

        $menuItem->delete();
        return response()->json(null, 204);
    }
}


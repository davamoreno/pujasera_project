<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMenuItemRequest;
use App\Http\Requests\UpdateMenuItemRequest;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class MenuItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function __construct(){
        $this ->authorizeResource(MenuItem::class, 'menu_item');
    }
    public function index()
    {
      $user =Auth::user();

      if($user->role->nama === 'Admin'){
        $menuItems = MenuItem::with('tenant','kategori')->get(); 
      }else{
        $tenantId= $user->tenant->id;
        $menuItems = MenuItem::where('tenant_id',$tenantId)
        ->with('kategori')
        ->get();
      }
      return response()->json($menuItems);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMenuItemRequest  $request)
    {
        $data = $request->validated();
        $user=Auth::user();

        if($user->role->nama === 'Pemilik Tenant'){
            $data['tenant_id']=$user->tenant->id;
        }
        if ($request->hasFile('gambar')) {
            $path = $request->file('gambar')->store('public/menu_images');
            $data['gambar_url'] = Storage::url($path); 
        }
        $menuItem = MenuItem::create($data);
        return response()->json($menuItem->load('tenant','kategori'),201);
    }

    /**
     * Display the specified resource.
     */
    public function show(MenuItem $menuItem)
    {
        return response()->json($menuItem->load('tenant','kategori'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMenuItemRequest $request, MenuItem $menuItem)
    {
        $data = $request->validated();

 // Handle update file gambar
        if ($request->hasFile('gambar')) {
            // 1. Hapus gambar lama jika ada
            if ($menuItem->gambar_url) {
                $oldPath = str_replace(Storage::url(''), 'public/', $menuItem->gambar_url);
                Storage::delete($oldPath);
            }

            // 2. Upload gambar baru
            $path = $request->file('gambar')->store('public/menu_images');
            $data['gambar_url'] = Storage::url($path);
        }

        $menuItem->update($data);

        return response()->json($menuItem->load('tenant', 'kategori'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MenuItem $menuItem)
    {
        if($menuItem->gambar_url){
            $oldPath=str_replace(Storage::url(''),'public/', $menuItem->gambar_url);
                Storage::delete($oldPath);
            }

        $menuItem->delete();
        return response()->json(null, 204);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KategoriMenu;
use App\Http\Requests\KategoriMenuRequest;
use Illuminate\Http\Request;

class KategoriMenuController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(KategoriMenu::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(KategoriMenuRequest $request)
    {
       $kategori = KategoriMenu::create($request->validated());
       return response()->json($kategori, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(KategoriMenu $kategoriMenu)
    {
         return response()->json($kategoriMenu);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(KategoriMenuRequest $request, KategoriMenu $kategoriMenu)
    {
       $request->validate([
        'nama'=>['required','string','max:255','unique:kategori_menu,nama' , $kategoriMenu->id],
       ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(KategoriMenu $kategoriMenu)
    {
       $kategoriMenu->delete();
       return response()->json(null, 204);

    }
}

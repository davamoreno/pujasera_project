<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Resources\AuthResource;

class AuthController extends Controller
{
    /**
     * Handle a login request.
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        // 1. Validasi input login
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);
        
        // 2. Coba autentikasi user dengan kredensial yang diberikan.
       if (! $token = Auth::guard('api')->attempt($credentials)) {
            // 3. Jika gagal, kirim response error.
            throw ValidationException::withMessages([
                'username' => ['Kredensial yang diberikan tidak cocok.'],
            ]);
        }

        // 4. Jika berhasil, kirim token sebagai response.
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
            'user' => new AuthResource(Auth::guard('api')->user())
        ]);
    }

    /**
    * Handle a logout request to the application.
    *
    * @param  \Illuminate\Http\Request  $request
    * @return \Illuminate\Http\JsonResponse
    */
    public function logout()
    {
        // Invalidate the token
        Auth::guard('api')->logout();

        return response()->json(['message' => 'Logout berhasil']);
    }

    /** 
     * Refresh a token.
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        // Refresh the token
        return $this->createNewToken([
            'message' => 'token refreshed', 
            'token' => Auth::guard('api')->refresh()
        ]);
    }

    /**
     * Get the authenticated User.
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(new AuthResource(Auth::guard('api')->user()));
    }

    /**
     * Create a new token structure.
     * @param  string $token
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() *
    60,
            'user' => new AuthResource(Auth::guard('api')->user())
        ]);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next,...$roles): Response
    {
        $user = auth()->user();
        if ($user && in_array($user->role->nama, $roles)) {
        
            return $next($request);
        }
return response()->json([ 'message' => 'Forbidden. Anda tidak memiliki hak akses untuk sumber daya ini.' ], 403);
    }
}

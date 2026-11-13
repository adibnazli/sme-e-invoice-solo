<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class OwnerAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->role === 'OWNER') {
            return $next($request);
        }

        return redirect()->route('pos')->with('error', 'Access Denied: Admin privileges required.');
    }
}
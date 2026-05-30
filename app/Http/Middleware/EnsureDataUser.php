<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDataUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('data_user')) {
            return redirect()->route('login')->with('error', 'Silakan login sebagai user role data.');
        }

        return $next($request);
    }
}

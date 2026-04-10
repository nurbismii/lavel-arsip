<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class EnsureLoginSession
{
    public function handle($request, Closure $next)
    {
        if (!Auth::check()) {
            $message = 'Sesi login Anda telah berakhir. Silakan login kembali.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'redirect' => route('login'),
                ], 401);
            }

            return redirect()
                ->guest(route('login'))
                ->with('error', $message);
        }

        if (!data_get(Auth::user(), 'is_active', true)) {
            Auth::logout();
            Session::invalidate();
            Session::regenerateToken();

            $message = 'Akun Anda sedang dinonaktifkan. Silakan hubungi admin.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'redirect' => route('login'),
                ], 401);
            }

            return redirect()
                ->guest(route('login'))
                ->with('error', $message);
        }

        return $next($request);
    }
}

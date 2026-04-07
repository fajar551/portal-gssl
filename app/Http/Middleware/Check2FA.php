<?php

// namespace App\Http\Middleware;

// use Closure;
// use Illuminate\Support\Facades\Auth;
// use App\Helpers\TwoFactorAuthentication;

// class Check2FA
// {
//     protected $except = [
//         'login',
//         'logout',
//         '2fa/verify',
//         '2fa/setup',
//         'password/reset',
//         'register',
//         '2fa/enable',
//         '2fa/disable',
//     ];

//     public function handle($request, Closure $next)
//     {
//         // Skip jika route dikecualikan
//         foreach ($this->except as $path) {
//             if ($request->is($path)) {
//                 return $next($request);
//             }
//         }

//         // Cek session 2FA pending
//         if (session()->has('auth.2fa.id')) {
//             $userId = session('auth.2fa.id');
            
//             // Jika sudah di halaman verifikasi, lanjutkan
//             if ($request->is('2fa/verify')) {
//                 return $next($request);
//             }

//             // Jika belum verifikasi, arahkan ke halaman verifikasi
//             if (!session('2fa_verified')) {
//                 return redirect()->route('2fa.verify');
//             }
//         }

//         // Cek user yang sudah login
//         $user = Auth::user();
//         if ($user) {
//             $twofa = new TwoFactorAuthentication();
//             $twofa->setClientID($user->id);

//             if ($twofa->isEnabled() && !session('2fa_verified')) {
//                 Auth::logout();
//                 session([
//                     'auth.2fa.id' => $user->id,
//                     'url.intended' => $request->fullUrl()
//                 ]);
//                 return redirect()->route('2fa.verify');
//             }
//         }

//         return $next($request);
//     }
// }

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Helpers\TwoFactorAuthentication;

class Check2FA
{
    protected $except = [
        'login',
        'logout',
        '2fa/verify',
        '2fa/backup',
        '2fa/setup',
        'password/reset',
        'register',
        '2fa/enable',
        '2fa/disable',
    ];

    public function handle($request, Closure $next)
    {
        // Skip untuk rute yang dikecualikan
        if ($this->isExceptionalRoute($request)) {
            return $next($request);
        }

        // Jika ada session 2FA pending dan belum verifikasi
        if (session()->has('auth.2fa.id') && !session('2fa_verified')) {
            // Jika bukan di halaman verifikasi, redirect ke sana
            if (!$request->is('2fa/verify')) {
                return redirect()->route('2fa.verify');
            }
            // Jika di halaman verifikasi, lanjutkan
            return $next($request);
        }

        // Jika user sudah login
        if (Auth::check()) {
            $user = Auth::user();
            $twofa = new TwoFactorAuthentication();
            $twofa->setClientID($user->id);

            // Jika 2FA aktif tapi belum verifikasi
            if ($twofa->isEnabled() && !session('2fa_verified')) {
                Auth::logout();
                session([
                    'auth.2fa.id' => $user->id,
                    'url.intended' => $request->fullUrl()
                ]);
                return redirect()->route('2fa.verify');
            }
        }

        return $next($request);
    }

    protected function isExceptionalRoute($request)
    {
        foreach ($this->except as $path) {
            if ($request->is($path . '*')) {
                return true;
            }
        }
        return false;
    }
}
<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\TwoFactorAuthentication;
use PragmaRX\Google2FA\Google2FA as BaseGoogle2FA;
   use Illuminate\Support\Facades\Hash;

class TwoFactorController extends Controller
{
//     public function showVerification()
// {
//     // Jika tidak ada session 2FA pending, redirect ke login
//     if (!session()->has('auth.2fa.id')) {
//         return redirect()->route('login');
//     }

//     // Jika sudah verifikasi, redirect ke intended URL
//     if (session('2fa_verified')) {
//         return redirect()->intended(route('home'));
//     }

//     return view('auth.2fa.verify');
// }

// public function verify(Request $request)
// {
//     try {
//         // Validasi input
//         $request->validate([
//             'code' => 'required|numeric|digits:6'
//         ]);

//         // Ambil user ID dari session
//         $userId = session('auth.2fa.id');
//         if (!$userId) {
//             throw new \Exception('No 2FA verification pending');
//         }

//         // Cari user
//         $user = \App\Models\Client::find($userId);
//         if (!$user) {
//             throw new \Exception('User not found');
//         }

//         // Verifikasi kode
//         $twofa = new TwoFactorAuthentication();
//         $twofa->setClientID($userId);

//         // Get user's secret from database
//         $authData = json_decode($user->authdata, true);
//         if (!$authData || !isset($authData['secret'])) {
//             throw new \Exception('2FA not properly configured');
//         }

//         // Verify the code
//         $google2fa = new \PragmaRX\Google2FA\Google2FA();
//         $valid = $google2fa->verifyKey($authData['secret'], $request->code, 2);

//         if (!$valid) {
//             return redirect()->back()->with('error', 'Invalid verification code');
//         }

//         // Login user
//         Auth::login($user);
        
//         // Mark session as 2FA verified
//         session(['2fa_verified' => true]);
        
//         // Clear 2FA session
//         session()->forget('auth.2fa.id');

//         // Redirect to intended URL or home
//         return redirect()->intended(route('home'));

//     } catch (\Exception $e) {
//         \Log::error('2FA Verification Error:', [
//             'message' => $e->getMessage(),
//             'trace' => $e->getTraceAsString()
//         ]);
        
//         return redirect()
//             ->back()
//             ->with('error', 'Verification failed: ' . $e->getMessage());
//     }
// }

// public function showVerification()
// {
//     // Log session values
//     \Log::info('Checking 2FA session values in showVerification:', [
//         'auth.2fa.id' => session('auth.2fa.id'),
//         '2fa_verified' => session('2fa_verified')
//     ]);

//     // Jika tidak ada session 2FA pending, redirect ke login
//     if (!session()->has('auth.2fa.id')) {
//         return redirect()->route('login');
//     }

//     // Jika sudah verifikasi, redirect ke intended URL
//     if (session('2fa_verified')) {
//         return redirect()->intended(route('home'));
//     }

//     return view('auth.2fa.verify');
// }

// public function showVerification()
// {
//     return redirect()->intended(route('home'));
// }

public function verify(Request $request)
{
    try {
        // Log session values before verification
        \Log::info('Before verification:', [
            'auth.2fa.id' => session('auth.2fa.id'),
            '2fa_verified' => session('2fa_verified')
        ]);

        $request->validate([
            'code' => 'required|numeric|digits:6'
        ]);

        $userId = session('auth.2fa.id');
        if (!$userId) {
            throw new \Exception('No 2FA verification pending');
        }

        $user = \App\Models\Client::find($userId);
        if (!$user) {
            throw new \Exception('User not found');
        }

        $twofa = new \App\Helpers\TwoFactorAuthentication();
        $twofa->setClientID($userId);

        // Verify code using Google2FA
        $google2fa = new \PragmaRX\Google2FA\Google2FA();
        $authData = json_decode($user->authdata, true);
        
        if (!$authData || !isset($authData['secret'])) {
            throw new \Exception('2FA not properly configured');
        }

        $valid = $google2fa->verifyKey($authData['secret'], $request->code, 2);

        if (!$valid) {
            return redirect()->back()->with('error', 'Invalid verification code');
        }

        // Log successful verification
        \Log::info('2FA code verified successfully for user:', ['user_id' => $userId]);

        // Login user
        Auth::login($user, session('auth.2fa.remember', false));
        
        // Mark as verified
        session(['2fa_verified' => true]);
        
        // Clear 2FA pending session
        session()->forget(['auth.2fa.id', 'auth.2fa.remember']);

        // Redirect to intended URL or home
        return redirect()->intended(route('home'));

    } catch (\Exception $e) {
        \Log::error('2FA Verification Error:', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return redirect()
            ->back()
            ->with('error', 'Verification failed: ' . $e->getMessage());
    }
}

protected function getSecret()
{
    try {
        $settings = $this->getUserSettings();
        \Log::debug('2FA Settings', [
            'user_id' => $this->clientid,
            'settings' => $settings
        ]);

        if (empty($settings) || !isset($settings['secret'])) {
            \Log::error('No 2FA settings found', [
                'user_id' => $this->clientid
            ]);
            return false;
        }

        return $settings['secret'];

    } catch (\Exception $e) {
        \Log::error('Get Secret Error:', [
            'message' => $e->getMessage(),
            'user_id' => $this->clientid
        ]);
        return false;
    }
}

protected function getUserSettings()
{
    try {
        // Debug query
        $query = \DB::table('tbltwofa')
            ->where('userid', $this->clientid)
            ->first();

        \Log::debug('2FA DB Query', [
            'user_id' => $this->clientid,
            'result' => $query
        ]);

        if (!$query) {
            return [];
        }

        return [
            'secret' => $query->secret,
            'enabled' => (bool)$query->enabled,
            // Add other fields as needed
        ];

    } catch (\Exception $e) {
        \Log::error('Get User Settings Error:', [
            'message' => $e->getMessage(),
            'user_id' => $this->clientid
        ]);
        return [];
    }
}

    public function enable(Request $request)
    {
        try {
            $twofa = new TwoFactorAuthentication();
            $twofa->setClientID(Auth::id());
            $twofa->enable();
            
            return redirect()->back()
                ->with('success', 'Two Factor Authentication berhasil diaktifkan.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal mengaktifkan Two Factor Authentication: ' . $e->getMessage());
        }
    }

    public function disable(Request $request)
    {
        try {
            $twofa = new TwoFactorAuthentication();
            $twofa->setClientID(Auth::id());
            $twofa->disable();
            
            return redirect()->back()
                ->with('success', 'Two Factor Authentication berhasil dinonaktifkan.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menonaktifkan Two Factor Authentication: ' . $e->getMessage());
        }
    }

         public function showBackupCodeForm()
{
    return view('auth.2fa.2fa_backup');
}

   public function loginWithBackupCode(Request $request)
{
    $request->validate([
        'backup_code' => 'required|string'
    ]);

    $auth = Auth::user();
    $backupCode = $request->input('backup_code');

    // Log the backup code for debugging
    \Log::info('Attempting to login with backup code:', ['backup_code' => $backupCode]);

    // Log the stored backup code for comparison
    \Log::info('Stored backup code for user:', ['user_id' => $auth->id, 'stored_backup_code' => $auth->backup_code]);

    // Verify the backup code
    if ($backupCode === $auth->backup_code) {
        // Mark 2FA as verified
        session(['2fa_verified' => true]);

        \Log::info('Backup code verified successfully for user:', ['user_id' => $auth->id]);

        return redirect()->intended(route('home'))->with('success', 'Logged in using backup code.');
    }

    \Log::warning('Invalid backup code attempt for user:', ['user_id' => $auth->id]);

    return redirect()->back()->withErrors(['backup_code' => 'Invalid backup code.']);
}
}
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Client;
use App\Models\Contact;
use App\Helpers\TwoFactorAuthentication;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = RouteServiceProvider::HOME;

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    protected function loggedOut(Request $request)
    {
        // Clear all 2FA related sessions
       session()->forget([
           'auth.2fa.id',
           'auth.2fa.remember',
           '2fa_verified',
           'url.intended'
       ]);
        
        return redirect()->route('login');
    }

    public function logout(Request $request)
    {
        \Log::info('Executing logout, clearing session data.');

        // Clear all 2FA related sessions
        session()->forget([
            'auth.2fa.id',
            'auth.2fa.remember',
            '2fa_verified',
            'url.intended'
        ]);

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    protected function guard()
    {
        return Auth::guard('web');
    }

//     protected function authenticated(Request $request, $user)
// {
//     \Log::info('User authenticated, checking 2FA:', [
//         'user_id' => $user->id,
//         'email' => $user->email
//     ]);

//     $twofa = new TwoFactorAuthentication();
//     $twofa->setClientID($user->id);

//     $is2FAEnabled = $twofa->isEnabled();
//     \Log::info('2FA Status:', [
//         'user_id' => $user->id,
//         'is_enabled' => $is2FAEnabled
//     ]);

//     if ($is2FAEnabled) {
//         \Log::info('2FA is enabled, redirecting to verification:', [
//             'user_id' => $user->id
//         ]);

//         session([
//             'auth.2fa.id' => $user->id,
//             'auth.2fa.remember' => $request->filled('remember'),
//             'url.intended' => route('home')
//         ]);

//         $data = $user->toArray();
//         $data["contactid"] = 0;
//         \App\Helpers\Hooks::run_hook("ClientLogin", $data);
//         \App\Helpers\Hooks::run_hook("UserLogin", ['user' => $user]);

//         return redirect()->route('2fa.verify');
//     }

//     // Check if the user is a sub-account
//     $contact = Contact::where('email', $user->email)->where('subaccount', 1)->first();
//     if ($contact) {
//         \Log::info('Sub-account login detected:', [
//             'contact_id' => $contact->id
//         ]);

//         // Retrieve the main client ID from the userid column
//         $mainClientId = $contact->userid;

//         // Fetch all relevant data for the main client
//         $mainClient = \App\Models\Client::with(['services', 'invoices', 'orders'])
//             ->where('id', $mainClientId)
//             ->first();

//         \Log::info('Main client data:', ['main_client_id' => $mainClientId, 'data' => $mainClient]);

//         $data = $user->toArray();
//         $data["id"] = $mainClientId; // Use the main client ID
//         $data["contactid"] = $contact->id;
//         $data["main_client_data"] = $mainClient; // Include main client data

//         \App\Helpers\Hooks::run_hook("ClientLogin", $data);
//         \App\Helpers\Hooks::run_hook("UserLogin", ['user' => $user]);

//         return redirect()->intended(route('home'));
//     }

//     // Normal login flow
//     $data = $user->toArray();
//     \Log::info('Authenticated user data:', $data);

//     $data["id"] = $user->id; // Ensure ID is included
//     $data["contactid"] = 0;
//     \App\Helpers\Hooks::run_hook("ClientLogin", $data);
//     \App\Helpers\Hooks::run_hook("UserLogin", ['user' => $user]);

//     return redirect()->intended(route('home'));
// }

// protected function authenticated(Request $request, $user)
// {
//     \Log::info('User authenticated, checking 2FA and subaccount:', [
//         'user_id' => $user->id,
//         'email' => $user->email
//     ]);

//     // Check if the user is a subaccount
//     $contact = Contact::where('email', $user->email)->first();
//     if ($contact) {
//         \Log::info('User is a subaccount:', [
//             'contact_id' => $contact->id,
//             'userid' => $contact->userid
//         ]);

//         // Authenticate using the related userid from tblclients
//         $client = Client::find($contact->userid);
//         if ($client) {
//             Auth::login($client);
//         }
//     }

//     // Initialize 2FA
//     $twofa = new TwoFactorAuthentication();
//     $twofa->setClientID($user->id);

//     // Check if user has 2FA enabled
//     $is2FAEnabled = $twofa->isEnabled();
//     \Log::info('2FA Status:', [
//         'user_id' => $user->id,
//         'is_enabled' => $is2FAEnabled
//     ]);

//     if ($is2FAEnabled) {
//         \Log::info('2FA is enabled, redirecting to verification:', [
//             'user_id' => $user->id
//         ]);

//         // Store user ID and intended URL in session
//         session([
//             'auth.2fa.id' => $user->id,
//             'auth.2fa.remember' => $request->filled('remember'),
//             'url.intended' => route('home')
//         ]);

//         // Run hooks
//         $data = $user->toArray();
//         $data["contactid"] = 0;
//         \App\Helpers\Hooks::run_hook("ClientLogin", $data);
//         \App\Helpers\Hooks::run_hook("UserLogin", ['user' => $user]);

//         // Redirect to 2FA verification page
//         return redirect()->route('2fa.verify');
//     }

//     // Normal login flow
//     $data = $user->toArray();
//     $data["contactid"] = 0;
//     \App\Helpers\Hooks::run_hook("ClientLogin", $data);
//     \App\Helpers\Hooks::run_hook("UserLogin", ['user' => $user]);

//     return redirect()->intended(route('home'));
// }

// protected function authenticated(Request $request, $user)
// {
//     \Log::info('User authenticated:', [
//         'user_id' => $user->id,
//         'email' => $user->email
//     ]);

//     // Initialize 2FA
//     $twofa = new TwoFactorAuthentication();
//     $twofa->setClientID($user->id);

//     // Check if user has 2FA enabled
//     $is2FAEnabled = $twofa->isEnabled();
//     \Log::info('2FA Status:', [
//         'user_id' => $user->id,
//         'is_enabled' => $is2FAEnabled
//     ]);

//     if ($is2FAEnabled) {
//         \Log::info('2FA is enabled, redirecting to verification:', [
//             'user_id' => $user->id
//         ]);

//         session([
//             'auth.2fa.id' => $user->id,
//             'auth.2fa.remember' => $request->filled('remember'),
//             'url.intended' => route('home')
//         ]);

//         $data = $user->toArray();
//         $data["contactid"] = 0;
//         \App\Helpers\Hooks::run_hook("ClientLogin", $data);
//         \App\Helpers\Hooks::run_hook("UserLogin", ['user' => $user]);

//         return redirect()->route('2fa.verify');
//     }

//     // Check if the user is a sub-account
//     $contact = \App\Models\Contact::where('email', $user->email)->where('subaccount', 1)->first();
//     if ($contact) {
//         \Log::info('Sub-account login detected:', [
//             'contact_id' => $contact->id
//         ]);

//         // Retrieve the main user ID from the contact
//         $mainUserId = $contact->userid;

//         // Assuming you have a Client model that represents the main user table
//         $mainUser = \App\Models\Client::find($mainUserId);

//         if ($mainUser) {
//             // Log in as the main user
//             Auth::login($mainUser);

//             \Log::info('Switched to main account:', [
//                 'main_user_id' => $mainUser->id,
//                 'email' => $mainUser->email
//             ]);

//             // Use sub-account's firstname and lastname
//             $data = $mainUser->toArray();
//             $data["firstname"] = $contact->firstname;
//             $data["lastname"] = $contact->lastname;
//             $data["contactid"] = $contact->id;

//             \App\Helpers\Hooks::run_hook("ClientLogin", $data);
//             \App\Helpers\Hooks::run_hook("UserLogin", ['user' => $mainUser]);

//             // Redirect directly to the home page
//             return redirect()->route('home');
//         }
//     }

//     // Normal login flow
//     $data = $user->toArray();
//     $data["contactid"] = 0;
//     \App\Helpers\Hooks::run_hook("ClientLogin", $data);
//     \App\Helpers\Hooks::run_hook("UserLogin", ['user' => $user]);

//     return redirect()->intended(route('home'));
// }

protected function authenticated(Request $request, $user)
{
    \Log::info('User authenticated:', [
        'user_id' => $user->id,
        'email' => $user->email
    ]);

    // Initialize 2FA
    $twofa = new TwoFactorAuthentication();
    $twofa->setClientID($user->id);

    // Check if user has 2FA enabled
    $is2FAEnabled = $twofa->isEnabled();
    \Log::info('2FA Status:', [
        'user_id' => $user->id,
        'is_enabled' => $is2FAEnabled
    ]);

    // if ($is2FAEnabled) {
    //     \Log::info('2FA is enabled, redirecting to verification:', [
    //         'user_id' => $user->id
    //     ]);

    //     session([
    //         'auth.2fa.id' => $user->id,
    //         'auth.2fa.remember' => $request->filled('remember'),
    //         'url.intended' => route('home')
    //     ]);

    //     $data = $user->toArray();
    //     $data["contactid"] = 0;
    //     \App\Helpers\Hooks::run_hook("ClientLogin", $data);
    //     \App\Helpers\Hooks::run_hook("UserLogin", ['user' => $user]);

    //     return redirect()->route('2fa.verify');
    // }

    // Check if the user is a sub-account
    $contact = \App\Models\Contact::where('email', $user->email)->where('subaccount', 1)->first();
    if ($contact) {
        \Log::info('Sub-account login detected:', [
            'contact_id' => $contact->id
        ]);

        // Retrieve the main user ID from the contact
        $mainUserId = $contact->userid;

        // Assuming you have a Client model that represents the main user table
        $mainUser = \App\Models\Client::find($mainUserId);

        if ($mainUser) {
            // Log in as the main user
            Auth::login($mainUser);

            \Log::info('Switched to main account:', [
                'main_user_id' => $mainUser->id,
                'email' => $mainUser->email
            ]);

            // Store sub-account details in session
            session([
                'user.firstname' => $contact->firstname,
                'user.lastname' => $contact->lastname,
            ]);

            // Log the updated Auth user instance
            \Log::info('Updated Auth user:', [
                'user_id' => Auth::user()->id,
                'firstname' => $contact->firstname,
                'lastname' => $contact->lastname
            ]);

            $data = $mainUser->toArray();
            $data["firstname"] = $contact->firstname;
            $data["lastname"] = $contact->lastname;
            $data["contactid"] = $contact->id;

            \App\Helpers\Hooks::run_hook("ClientLogin", $data);
            \App\Helpers\Hooks::run_hook("UserLogin", ['user' => $mainUser]);

            // Redirect directly to the home page
            return redirect()->route('home');
        }
    }

    // Normal login flow
    $data = $user->toArray();
    $data["contactid"] = 0;
    \App\Helpers\Hooks::run_hook("ClientLogin", $data);
    \App\Helpers\Hooks::run_hook("UserLogin", ['user' => $user]);

    return redirect()->intended(route('home'));
}



}
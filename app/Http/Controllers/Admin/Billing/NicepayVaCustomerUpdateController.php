<?php

namespace App\Http\Controllers\Admin\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NicepayVaCustomerUpdateController extends Controller
{
    /** Fallback jika NICEPAY_IMID / config tidak ter-load di server (mis. config:cache tanpa env). */
    private const DEFAULT_IMID = 'QWORDS0005';

    private const DEFAULT_MERCHANT_KEY = '0BvSWubMfGqCLNpPC77NcTNVv9lZH+4XwHy2fY2a/NkUzp4BBWWdp2qGyHkF03m7hoiAZNTOQw6JCNiLY3MZ5g==';

    public function __construct()
    {
        $this->middleware(['auth:admin']);
    }

    public function index()
    {
        return view('pages.billing.nicepay_va_update.index');
    }

    public function update(Request $request)
    {
        $request->validate([
            'vacct_no' => 'required|string|max:32',
            'customer_id' => 'nullable|string|max:20',
            'customer_nm' => 'required|string|max:30|min:1',
        ]);

        $iMid = trim((string) config('services.nicepay.i_mid')) ?: self::DEFAULT_IMID;
        $merchantKey = trim((string) config('services.nicepay.merchant_key')) ?: self::DEFAULT_MERCHANT_KEY;

        $vacctDigits = preg_replace('/\D/', '', $request->input('vacct_no'));
        if ($vacctDigits === '') {
            return back()->withErrors(['vacct_no' => 'Nomor VA tidak valid.'])->withInput();
        }

        $customerId = $request->filled('customer_id')
            ? preg_replace('/\D/', '', $request->input('customer_id'))
            : substr($vacctDigits, -8);

        if ($customerId === '' || strlen($customerId) > 8) {
            return back()
                ->withErrors([
                    'customer_id' => 'Customer ID harus 1–8 digit numerik (kosongkan untuk memakai 8 digit terakhir nomor VA).',
                ])
                ->withInput();
        }

        $customerNm = mb_substr(trim($request->input('customer_nm')), 0, 30);

        $merchantToken = hash('sha256', $iMid . $customerId . $merchantKey);

        $payload = [
            'iMid' => $iMid,
            'customerId' => $customerId,
            'customerNm' => $customerNm,
            'merchantToken' => $merchantToken,
            'updateType' => '2',
        ];

        try {
            $response = Http::asForm()
                ->timeout(30)
                ->post('https://www.nicepay.co.id/nicepay/api/vacctCustomerUpdate.do', $payload);
        } catch (\Throwable $e) {
            Log::error('Nicepay VA customer update: HTTP failure', [
                'message' => $e->getMessage(),
            ]);

            return back()
                ->withErrors(['request' => 'Gagal menghubungi NICEPAY: ' . $e->getMessage()])
                ->withInput();
        }

        return back()
            ->withInput($request->only(['vacct_no', 'customer_id', 'customer_nm']))
            ->with('nicepay_va_update_response', [
                'http_status' => $response->status(),
                'raw' => $response->body(),
                'parsed' => $response->json(),
                'sent' => [
                    'iMid' => $iMid,
                    'customerId' => $customerId,
                    'customerNm' => $customerNm,
                    'vacct_no' => $vacctDigits,
                    'updateType' => '2',
                ],
            ]);
    }
}

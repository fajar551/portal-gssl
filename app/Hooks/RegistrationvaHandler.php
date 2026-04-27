<?php

namespace App\Hooks;

use App\Events\ClientAreaRegister;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class RegistrationvaHandler
{
	public function handle($event)
    {
        $userId = $event->user->id;
        Log::info("Processing user ID: $userId");

        $client = DB::table('tblclients')->where('id', $userId)->first(['firstname', 'lastname', 'companyname']);
        Log::info("Client data retrieved: ", (array)$client);

        $name = $client->companyname ?: "{$client->firstname} {$client->lastname}";
        Log::info("Client name determined: $name");

        $vaExists = DB::table('fixedva')->where('clientid', $userId)->exists();
        Log::info("VA exists: " . ($vaExists ? 'Yes' : 'No'));

        if (!$vaExists) {
            $merFixAcctId = str_pad($userId, 7, "0", STR_PAD_LEFT);
            $merFixAcctId = str_pad($merFixAcctId, 8, "2", STR_PAD_LEFT);
            Log::info("Merchant Fixed Account ID: $merFixAcctId");

            $iMid = 'QWORDS0005';
            $iKey = '0BvSWubMfGqCLNpPC77NcTNVv9lZH+4XwHy2fY2a/NkUzp4BBWWdp2qGyHkF03m7hoiAZNTOQw6JCNiLY3MZ5g==';
            $merchantToken = hash('sha256', $iMid . $merFixAcctId . $iKey);
            Log::info("Merchant token generated");

            $client = new Client();
            try {
                $response = $client->post('https://www.nicepay.co.id/nicepay/api/vacctCustomerRegist.do', [
                    'form_params' => [
                        'iMid' => $iMid,
                        'customerId' => $merFixAcctId,
                        'customerNm' => $name,
                        'merchantToken' => $merchantToken,
                    ],
                    'timeout' => 90,
                ]);
                Log::info("API request sent to Nicepay");

                $resultData = json_decode($response->getBody(), true);
                Log::info("API response received", $resultData);

                if ($resultData) {
                    $bankAccounts = [];
                    foreach ($resultData['vacctInfoList'] as $info) {
                        $bankAccounts[$info['bankCd']] = $info['vacctNo'];
                    }
                    Log::info("Bank accounts parsed", $bankAccounts);

                    DB::table('fixedva')->insert([
                        'clientid' => $userId,
                        'bcava' => $bankAccounts['CENA'] ?? null,
                        'mandiriva' => $bankAccounts['BMRI'] ?? null,
                        'bniva' => $bankAccounts['BNIN'] ?? null,
                        'briva' => $bankAccounts['BRIN'] ?? null,
                        'biiva' => $bankAccounts['IBBK'] ?? null,
                        'permatabankva' => $bankAccounts['BBBA'] ?? null,
                        'hanabankva' => $bankAccounts['HNBN'] ?? null,
                        'danamonva' => $bankAccounts['BDIN'] ?? null,
                        'atmbersamava' => $bankAccounts['HNBN'] ?? null,
                        'cimbva' => $bankAccounts['BNIA'] ?? null,
                    ]);
                    Log::info("Bank accounts inserted into database");
                }
            } catch (\Exception $e) {
                Log::error("Error during API request: " . $e->getMessage());
            }
        }
    }
}

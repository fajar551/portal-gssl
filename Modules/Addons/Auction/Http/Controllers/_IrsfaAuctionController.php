<?php

namespace Modules\Addons\Auction\Http\Controllers;

use Illuminate\Routing\Controller;

class _IrsfaAuctionController extends Controller
{
    private $apiUrl = "https://api.irsfa.id";
    private $clientId = "ecc1d21e-f873-4a86-ac37-982adc0fc239";
    private $secretId = "DvMjDv0EaYbBZbfKgJma4u7EN6DL51Dzjy9J46Lh";

    private function sendRequest($url, $method, $token, $data = null)
    {
        $curl = curl_init();

        $postData = is_array($data) ? $data : [];

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => http_build_query($postData),
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $token",
                "Content-Type: application/x-www-form-urlencoded",
                "X-Requested-With: XMLHttpRequest"
            ],
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error) {
            return $error;
        } else {
            return json_decode($response);
        }
    }

    private function authenticate($data)
    {
        $curl = curl_init();

        $postData = is_array($data) ? $data : [];

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->apiUrl . "/oauth/token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => http_build_query($postData),
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response);
    }

    public function getDomainBackorder()
    {
        $authData = [
            "grant_type" => "client_credentials",
            "client_id" => $this->clientId,
            "client_secret" => $this->secretId,
            "scope" => "",
        ];

        try {
            $authResponse = $this->authenticate($authData);

            if (isset($authResponse->access_token)) {
                $requestResponse = $this->sendRequest($this->apiUrl . "/rest/v2/domain/admin/backorder/list", "POST", $authResponse->access_token);
                return $requestResponse;
            } else {
                return ['error' => 'Token akses tidak ditemukan.'];
            }
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
<?php

namespace Modules\Servers\Directadmin\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Exception;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class DirectAdminController extends Controller
{
    // Server password statis
    // const SERVER_PASSWORD = 'vrUP7NCpaEwqkKPauQ';

    public function CreateAccount($params = [])
{
    // Set serverpassword langsung
    $params['serverpassword'];
    // = self::SERVER_PASSWORD;

    // Debugging untuk memastikan parameter diterima
    Log::info('Parameters received for account creation: ', $params);

    // Validasi input
    if (empty($params["username"]) || empty($params["password"]) ||
        empty($params["clientsdetails"]["email"]) || empty($params['domain']) ||
        empty($params['serverpassword'])) {
        return response()->json(['status' => 'error', 'message' => 'Error: All fields are required, including serverpassword.'], 400);
    }

    // Cek apakah username sudah digunakan (sesuai kebutuhan Anda)
    if ($this->isUsernameTaken($params["username"])) {
        return response()->json(['status' => 'error', 'message' => 'Error: Username already taken. Please choose another one.'], 400);
    }

    // Kode untuk membuat akun di DirectAdmin
    $command = "CMD_API_ACCOUNT_USER";
    $fields = [
        'action' => 'create',
        'add' => 'submit',
        'username' => $params["username"],
        'passwd' => $params["password"],
        'passwd2' => $params["password"],
        'email' => $params["clientsdetails"]["email"],
        'domain' => $params["domain"],
        'package' => $params["configoption1"],
        'ip' => $params["serverip"] ?? 'shared',
        'notify' => 'no',
    ];

    try {
        Log::info('Sending request to DirectAdmin', [
            'url' => $this->directadmin_req_url($command),
            'fields' => $fields,
        ]);

        // Kirim permintaan ke DirectAdmin
        $response = $this->directadmin_req($command, $fields, $params);

        Log::info('DirectAdmin Response: ', ['response' => json_encode($response)]);

        // Memeriksa apakah response mengandung error
        if (isset($response['error']) && $response['error'] == '1') {
            Log::error('DirectAdmin API Error: ' . $response['details']);
            return response()->json(['status' => 'error', 'message' => "Error creating account: " . $response['details']], 500);
        }

        // Jika sukses
        if (isset($response['error']) && $response['error'] == '0') {
            $adminLink = $this->directadmin_adminlink($params["username"]);
            return response()->json([
                'status' => 'success',
                'message' => "Akun berhasil dibuat dengan username: " . $params["username"] . ".",
                'admin_link' => $adminLink
            ]);
        }

        // Jika format respons tidak sesuai yang diharapkan
        return response()->json(['status' => 'error', 'message' => "Unexpected response format: " . json_encode($response)], 500);
    } catch (RequestException $e) {
        Log::error('RequestException during DirectAdmin API call: ' . $e->getMessage());
        if ($e->hasResponse()) {
            $responseBody = (string)$e->getResponse()->getBody();
            Log::error('Response body: ' . $responseBody);
        }
        return response()->json(['status' => 'error', 'message' => "DirectAdmin API Error: " . $e->getMessage()], 500);
    } catch (Exception $e) {
        Log::error('Exception during DirectAdmin API call: ' . $e->getMessage());
        return response()->json(['status' => 'error', 'message' => "DirectAdmin API Error: " . $e->getMessage()], 500);
    }
}

    private function isUsernameTaken($username)
    {
        // Ambil daftar username yang sudah terdaftar dari session
        $registeredUsernames = session('registered_usernames', []);
        return in_array($username, $registeredUsernames);
    }

    private function addUsernameToSession($username)
    {
        // Tambahkan username ke daftar di session
        $registeredUsernames = session('registered_usernames', []);
        $registeredUsernames[] = $username;
        session(['registered_usernames' => $registeredUsernames]);
    }

    protected function directadmin_req($command, $fields, $params, $post = true)
    {
        $url = "https://103.28.12.212:2222/". $command;
        $httpClient = new Client();

        try {
            $response = $httpClient->request($post ? 'POST' : 'GET', $url, [
                'auth' => ['admin', $params['serverpassword']],
                'form_params' => $fields,
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'verify' => false,
            ]);

            $responseBody = (string)$response->getBody();
            Log::info('Raw response body: ' . $responseBody);

            parse_str($responseBody, $parsedResponse);
            return $parsedResponse;
        } catch (RequestException $e) {
            return [
                'error' => true,
                'details' => $e->getMessage(),
                'response' => $e->getResponse() ? (string)$e->getResponse()->getBody() : null,
            ];
        }
    }

    public function SuspendAccount($params)
    {
        $params['serverpassword'];
        // = self::SERVER_PASSWORD;

        if (empty($params['username'])) {
            return response()->json(['status' => 'error', 'message' => 'Error: Username is required.'], 400);
        }

        $command = "CMD_API_SELECT_USERS";
        $fields = [
            'location' => 'CMD_SELECT_USERS',
            'suspend' => 'suspend',
            'select0' => $params['username'],
        ];

        try {
            $response = $this->directadmin_req($command, $fields, $params);

            if (isset($response['error']) && $response['error'] == '0') {
                return response()->json(['status' => 'success', 'message' => 'Account successfully suspended.']);
            }

            return response()->json(['status' => 'error', 'message' => 'Error suspending account: ' . $response['details']], 500);
        } catch (Exception $e) {
            Log::error('Exception during DirectAdmin API call: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function UnsuspendAccount($params)
    {
        $params['serverpassword'];
        // = self::SERVER_PASSWORD;

        if (empty($params['username'])) {
            return response()->json(['status' => 'error', 'message' => 'Error: Username is required.'], 400);
        }

        $command = "CMD_API_SELECT_USERS";
        $fields = [
            'location' => 'CMD_SELECT_USERS',
            'suspend' => 'unsuspend',
            'select0' => $params['username'],
        ];

        try {
            $response = $this->directadmin_req($command, $fields, $params);

            if (isset($response['error']) && $response['error'] == '0') {
                return response()->json(['status' => 'success', 'message' => 'Account successfully unsuspended.']);
            }

            return response()->json(['status' => 'error', 'message' => 'Error unsuspending account: ' . $response['details']], 500);
        } catch (Exception $e) {
            Log::error('Exception during DirectAdmin API call: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function TerminateAccount($params)
    {
        $params['serverpassword'];
        // = self::SERVER_PASSWORD;

        if (empty($params['username'])) {
            return response()->json(['status' => 'error', 'message' => 'Error: Username is required.'], 400);
        }

        $command = "CMD_API_SELECT_USERS";
        $fields = [
            'confirmed' => 'Confirm',
            'location' => 'CMD_SELECT_USERS',
            'delete' => 'yes',
            'select0' => $params['username'],
        ];

        try {
            $response = $this->directadmin_req($command, $fields, $params);

            if (isset($response['error']) && $response['error'] == '0') {
                return response()->json(['status' => 'success', 'message' => 'Account successfully terminated.']);
            }

            return response()->json(['status' => 'error', 'message' => 'Error terminating account: ' . $response['details']], 500);
        } catch (Exception $e) {
            Log::error('Exception during DirectAdmin API call: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function ChangePassword($params)
{
    $params['serverpassword'];

    // Validasi parameter
    if (empty($params['username']) || empty($params['password'])) {
        return response()->json(['status' => 'error', 'message' => 'Error: Username and password are required.'], 400);
    }

    $command = "CMD_API_USER_PASSWD";
    $fields = [
        'username' => $params['username'],
        'passwd' => $params['password'],
        'passwd2' => $params['password'],
    ];

    try {
        $response = $this->directadmin_req($command, $fields, $params);

        if (isset($response['error']) && $response['error'] == '0') {
            return response()->json(['status' => 'success', 'message' => 'Password successfully changed.']);
        }

        return response()->json(['status' => 'error', 'message' => 'Error changing password: ' . $response['details']], 500);
    } catch (Exception $e) {
        Log::error('Exception during DirectAdmin API call: ' . $e->getMessage());
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}

public function ChangePackage($params)
{
    // Inisialisasi fields untuk permintaan
    $fields = array();
    $fields["action"] = "package";
    $fields["user"] = $params["username"];
    $fields["package"] = $params["configoption1"]; // Paket baru yang diterima dari configoption1

    // Pilih apakah user adalah reseller atau tidak
    if (isset($params["type"]) && $params["type"] == "reselleraccount") {
        // Kirim permintaan untuk reseller
        $results = $this->directadmin_req("CMD_API_MODIFY_RESELLER", $fields, $params);
    } else {
        // Kirim permintaan untuk user biasa
        $results = $this->directadmin_req("CMD_API_MODIFY_USER", $fields, $params);
    }

    // Memeriksa apakah terjadi error
    if ($results["error"]) {
        $result = $results["details"]; // Menampilkan pesan error jika ada
    } else {
        $result = "success"; // Jika tidak ada error, return success
    }

    return $result; // Mengembalikan hasil
}

    public function ConfigOptions($params)
{
    return [
        "Package Name" => [
            "Type" => "text",
            "Size" => "25",
            "Loader" => function ($params) {
                $return = [];
                $command = "CMD_API_PACKAGES_USER";

                // Permintaan ke DirectAdmin untuk memuat daftar paket
                $result = $this->directadmin_req($command, [], $params);

                if (isset($result["error"]) && $result["error"]) {
                    throw new \Exception($result["details"] ?? "Error retrieving packages");
                }

                // Pastikan response berisi daftar paket
                if (isset($result["list"]) && is_array($result["list"])) {
                    foreach ($result["list"] as $package) {
                        // Format nama paket menjadi lebih terbaca
                        $return[$package] = ucwords(str_replace("_", " ", $package));
                    }
                }

                return $return;
            },
        ],

        "Dedicated IP" => [
            "Type" => "yesno",
            "Description" => "Tick to Auto-Assign Dedicated IP",
        ],

        "Suspend at Limit" => [
            "Type" => "yesno",
            "Description" => "Tick to Auto Suspend Users when reaching Bandwidth Limit",
        ],
    ];
}
    public function directadmin_adminlink($username)
    {
        $baseUrl = "https://103.28.12.212:2222";
        return $baseUrl . "/CMD_SHOW_USER?user=" . urlencode($username);
    }

    protected function directadmin_req_url($command)
    {
        return "https://103.28.12.212:2222/" . $command;
    }
}
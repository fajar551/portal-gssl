<?php

namespace Modules\Registrar\OpenProvider\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use OpenProvider\API\APITools;
use Illuminate\Support\Facades\DB;
// use Modules\Registrar\OpenProvider\API\ApiClient;
// use Modules\Registrar\OpenProvider\API\ApiHelper;
use Illuminate\Support\Facades\Log;
use Modules\Registrar\OpenProvider\API\DomainRegistration as APIDomainRegistration;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class OpenProviderController extends Controller
{
    protected $apiHelper;
    protected $domain;
    protected $username;
    protected $password;

    public function __construct()
    {
        $this->username = DB::table('tblregistrars')
            ->where('registrar', 'openprovider')
            ->where('setting', 'Username')
            ->value('value');

        $this->password = DB::table('tblregistrars')
            ->where('registrar', 'openprovider')
            ->where('setting', 'Password')
            ->value('value');

        if (!$this->username || !$this->password) {
            \Log::error('Kredensial OpenProvider tidak ditemukan atau kosong', [
                'username' => $this->username,
                'password' => $this->password
            ]);
            throw new \Exception('Kredensial OpenProvider tidak ditemukan');
        }

        // $apiClient = new ApiClient($this->username, $this->password, 'https://api.openprovider.eu');
        // $this->apiHelper = new ApiHelper($apiClient);

      
    }

    public function getDomainList()
    {
        try {
            $client = new Client();

            $token = $this->getToken();

            if (!$token) {
                throw new \Exception('Gagal mendapatkan token');
            }

            $response = $client->get('https://api.openprovider.eu/v1beta/domains', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                    'Cookie' => 'PHPSESSID=2mviq9rh6hpc5o3b6v41c54r7g; locale=EN',
                ],
            ]);

            $result = json_decode($response->getBody(), true);

            if (isset($result['code']) && $result['code'] === 0) {
                return response()->json([
                    'success' => true,
                    'data' => $result['data']['results']
                ]);
            } else {
                throw new \Exception($result['desc'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            Log::error('Error getting domain list:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal mendapatkan daftar domain: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getContactList()
    {
        try {
            $client = new Client();
            $token = $this->getToken();

            $response = $client->get('https://api.openprovider.eu/v1beta/contacts', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ],
            ]);

            $result = json_decode($response->getBody(), true);

            if (isset($result['code']) && $result['code'] === 0) {
                return response()->json([
                    'success' => true,
                    'data' => $result['data']['results']
                ]);
            } else {
                throw new \Exception($result['desc'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            \Log::error('Error getting contact list:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal mendapatkan daftar kontak: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getToken()
    {
        try {
            $client = new Client();

            $loginResponse = $client->post('https://api.openprovider.eu/v1beta/auth/login', [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'username' => $this->username,
                    'password' => $this->password,
                ],
            ]);

            $loginData = json_decode($loginResponse->getBody(), true);
            $token = $loginData['data']['token'] ?? null;

            if (!$token) {
                throw new \Exception('Gagal mendapatkan token');
            }

            return $token;
        } catch (\Exception $e) {
            \Log::error('Error getting token:', ['error' => $e->getMessage()]);
            throw new \Exception('Gagal mendapatkan token: ' . $e->getMessage());
        }
    }

    public function getDomainFromDatabase($sld, $tld)
    {
        return DB::table('tbldomains')
            ->where('sld', $sld)
            ->where('tld', $tld)
            ->first();
    }

    public function getDomainIdFromOpenProvider($sld, $tld)
    {
        $client = new Client();
        $token = $this->getToken();

        $response = $client->get('https://api.openprovider.eu/v1beta/domains', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
            'query' => [
                'name' => $sld,
                'extension' => $tld,
            ],
        ]);

        $result = json_decode($response->getBody(), true);

        if (isset($result['data']['results'][0]['id'])) {
            return $result['data']['results'][0]['id'];
        }

        throw new \Exception('Domain ID not found');
    }

    public function lockDomainOpenprovider($sld, $tld)
    {
        try {
            $domainId = $this->getDomainIdFromOpenProvider($sld, $tld);
            return $this->toggleDomainLock($domainId, true);
        } catch (\Exception $e) {
            \Log::error('Error locking domain:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengunci domain: ' . $e->getMessage()
            ], 500);
        }
    }

    public function unlockDomainOpenprovider($sld, $tld)
    {
        try {
            $domainId = $this->getDomainIdFromOpenProvider($sld, $tld);
            return $this->toggleDomainLock($domainId, false);
        } catch (\Exception $e) {
            \Log::error('Error unlocking domain:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal unlock domain: ' . $e->getMessage()
            ], 500);
        }
    }

    public function registerOpenprovider(Request $request)
    {
        $validatedData = $request->validate([
            'domain.name' => 'required|string',
            'domain.extension' => 'required|string',
            'name_servers' => 'required|array',
            'name_servers.*.name' => 'required|string',
            'name_servers.*.ip' => 'nullable|ip',
            'name_servers.*.ip6' => 'nullable|ip',
        ]);

        try {
            $client = new Client();
            $token = $this->getToken();

            $response = $client->post('https://api.openprovider.eu/v1beta/domains', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ],
                'json' => [
                    'domain' => [
                        'name' => $validatedData['domain']['name'],
                        'extension' => $validatedData['domain']['extension'],
                    ],
                    'name_servers' => array_map(function($nameServer) {
                        return [
                            'name' => $nameServer['name'],
                            'ip' => $nameServer['ip'] ?? null,
                            'ip6' => $nameServer['ip6'] ?? null,
                            'seq_nr' => 1, // Sesuaikan jika perlu
                        ];
                    }, $validatedData['name_servers']),
                    // Tambahkan data lain yang diperlukan di sini
                ],
            ]);

            $result = json_decode($response->getBody(), true);

            if (isset($result['code']) && $result['code'] === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Domain registered successfully.',
                    'data' => $result['data']
                ]);
            } else {
                throw new \Exception($result['desc'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            \Log::error('Error registering domain:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal mendaftarkan domain: ' . $e->getMessage()
            ], 500);
        }
    }

    public function renewDomainOpenProvider(Request $request)
    {
        $validatedData = $request->validate([
            'domain.name' => 'required|string',
            'domain.extension' => 'required|string',
            'id' => 'required|integer',
            'period' => 'required|integer|min:1',
        ]);

        try {
            $client = new Client();
            $token = $this->getToken();

            $response = $client->post("https://api.openprovider.eu/v1beta/domains/{$validatedData['id']}/renew", [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ],
                'json' => [
                    'domain' => [
                        'name' => $validatedData['domain']['name'],
                        'extension' => $validatedData['domain']['extension'],
                    ],
                    'id' => $validatedData['id'],
                    'period' => $validatedData['period'],
                ],
            ]);

            $result = json_decode($response->getBody(), true);

            if (isset($result['code']) && $result['code'] === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Domain renewed successfully.',
                    'data' => $result['data']
                ]);
            } else {
                throw new \Exception($result['desc'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            \Log::error('Error renewing domain:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperpanjang domain: ' . $e->getMessage()
            ], 500);
        }
    }

    public function transferDomainOpenProvider(Request $request)
    {
        $validatedData = $request->validate([
            'domain.name' => 'required|string',
            'domain.extension' => 'required|string',
            'auth_code' => 'required|string',
            'admin_handle' => 'required|string',
            'billing_handle' => 'required|string',
            'owner_handle' => 'required|string',
            'tech_handle' => 'required|string',
            'name_servers' => 'required|array',
            'name_servers.*.name' => 'required|string',
            'name_servers.*.ip' => 'nullable|ip',
            'name_servers.*.ip6' => 'nullable|ip',
        ]);

        try {
            $client = new Client();
            $token = $this->getToken();

            $response = $client->post('https://api.openprovider.eu/v1beta/domains/transfer', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ],
                'json' => [
                    'accept_premium_fee' => 17,
                    'additional_data' => [
                        'company_registration_number' => 'XX123456789X03',
                        'customer_uin' => '374892173498127349',
                        'customer_uin_doc_type' => [
                            'description' => 'Singapore Personal Access ID',
                            'doc_type' => 'singpass'
                        ],
                        'domain_name_variants' => ['xn--домен.cat'],
                        'gay_donation_acceptance' => '1',
                        'gay_rights_protection_acceptance' => '1',
                        'idn_script' => 'SPA',
                        'intended_use' => 'generic',
                        'legal_type' => 'Individual',
                        'passport_number' => 'X123458',
                        'self_service' => '1',
                        'trademark' => '1',
                        'vat' => '11843009X',
                    ],
                    'admin_handle' => $validatedData['admin_handle'],
                    'auth_code' => $validatedData['auth_code'],
                    'autorenew' => 'default',
                    'billing_handle' => $validatedData['billing_handle'],
                    'comments' => 'any comment here',
                    'domain' => [
                        'name' => $validatedData['domain']['name'],
                        'extension' => $validatedData['domain']['extension'],
                    ],
                    'name_servers' => array_map(function($nameServer) {
                        return [
                            'name' => $nameServer['name'],
                            'ip' => $nameServer['ip'] ?? null,
                            'ip6' => $nameServer['ip6'] ?? null,
                            'seq_nr' => 1,
                        ];
                    }, $validatedData['name_servers']),
                    'owner_handle' => $validatedData['owner_handle'],
                    'tech_handle' => $validatedData['tech_handle'],
                    'ns_group' => 'dns-openprovider',
                    'ns_template_id' => 30951,
                    'ns_template_name' => 'example',
                    'promo_code' => 'PROMOCODE',
                    'reseller_handle' => 'XX123456-XX',
                    'roid' => '100590-UK',
                    'unit' => 'y',
                    'use_domicile' => false,
                ],
            ]);

            $result = json_decode($response->getBody(), true);

            if (isset($result['code']) && $result['code'] === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Domain transferred successfully.',
                    'data' => $result['data']
                ]);
            } else {
                throw new \Exception($result['desc'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            \Log::error('Error transferring domain:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal mentransfer domain: ' . $e->getMessage()
            ], 500);
        }
    }

    public function modifyNameServer(Request $request, $name)
    {
        $validatedData = $request->validate([
            'ip' => 'required|ip',
            'ip6' => 'required|ip',
        ]);

        try {
            $client = new Client();
            $token = $this->getToken();

            $response = $client->put("https://api.openprovider.eu/v1beta/dns/nameservers/{$name}", [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ],
                'json' => [
                    'ip' => $validatedData['ip'],
                    'ip6' => $validatedData['ip6'],
                    'name' => $name,
                ],
            ]);

            $result = json_decode($response->getBody(), true);

            if (isset($result['code']) && $result['code'] === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'Name server modified successfully.',
                    'data' => $result['data']
                ]);
            } else {
                throw new \Exception($result['desc'] ?? 'Unknown error');
            }
        } catch (\Exception $e) {
            \Log::error('Error modifying name server:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal memodifikasi name server: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Cek ketersediaan domain
     */
    public function checkDomain(Request $request)
    {
        try {
            $validated = $request->validate([
                'sld' => 'required|string',
                'tld' => 'required|string'
            ]);

            $domain = $this->domain;
            $domain->extension = $validated['tld'];
            $domain->name = $validated['sld'];

            $result = $this->apiHelper->checkDomain($domain);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengecek domain: ' . $e->getMessage()
            ], 500);
        }
    }

    public function output()
    {
        $username = DB::table('tblregistrars')
            ->where('registrar', 'openprovider')
            ->where('setting', 'Username')
            ->value('value');

        $password = DB::table('tblregistrars')
            ->where('registrar', 'openprovider')
            ->where('setting', 'Password')
            ->value('value');

        if (!$username || !$password) {
            throw new \Exception('Kredensial OpenProvider tidak ditemukan');
        }

        $credentials = (object)[
            'username' => $username,
            'password' => $password
        ];

        return view('openprovider::test', compact('credentials'));
    }

    public function detailContactOpenprovider($id)
{
    try {
        $client = new Client();
        $token = $this->getToken();

        $response = $client->get("https://api.openprovider.eu/v1beta/contacts/{$id}", [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);

        $result = json_decode($response->getBody(), true);
        Log::info("hasil get openprovider by id: " . json_encode($result));

        if (isset($result['data'])) {
            return view('pages.clients.viewclients.clientdomain.detailContactOpenprovider', [
                'contact' => $result['data']
            ]);
        } else {
            throw new \Exception('Contact not found');
        }
    } catch (\Exception $e) {
        \Log::error('Error fetching contact details:', ['error' => $e->getMessage()]);
        return redirect()->back()->with('error', 'Gagal mendapatkan detail kontak: ' . $e->getMessage());
    }
}

public function updateContact(Request $request, $id)
{
    $validatedData = $request->validate([
        'firstName' => 'required|string',
        'lastName' => 'required|string',
        'email' => 'required|email',
        'companyName' => 'nullable|string',
        'street' => 'required|string',
        'number' => 'required|string',
        'zipcode' => 'required|string',
        'city' => 'required|string',
        'country' => 'required|string',
        'telephone' => 'required|string',
    ]);

    try {
        $client = new Client();
        $token = $this->getToken();

        $response = $client->put("https://api.openprovider.eu/v1beta/contacts/{$id}", [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ],
            'json' => [
                'name' => [
                    'first_name' => $validatedData['firstName'],
                    'last_name' => $validatedData['lastName'],
                ],
                'email' => $validatedData['email'],
                'company_name' => $validatedData['companyName'],
                'address' => [
                    'street' => $validatedData['street'],
                    'number' => $validatedData['number'],
                    'zipcode' => $validatedData['zipcode'],
                    'city' => $validatedData['city'],
                    'country' => $validatedData['country'],
                ],
                'phone' => [
                    'country_code' => '+6', // Adjust as needed
                    'area_code' => '285', // Adjust as needed
                    'subscriber_number' => $validatedData['telephone'],
                ],
            ],
        ]);

        $result = json_decode($response->getBody(), true);

        if (isset($result['code']) && $result['code'] === 0) {
            return redirect()->back()->with('success', 'Contact updated successfully.');
        } else {
            throw new \Exception($result['desc'] ?? 'Unknown error');
        }
    } catch (\Exception $e) {
        \Log::error('Error updating contact:', ['error' => $e->getMessage()]);
        return redirect()->back()->with('error', 'Failed to update contact: ' . $e->getMessage());
    }
}
}
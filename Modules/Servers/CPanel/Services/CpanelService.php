<?php

namespace Modules\Servers\CPanel\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class CpanelService
{
    /** @var string */
    protected $baseUrl;
    
    /** @var array */
    protected $headers;
    
    /** @var string */
    protected $hostname;
    
    /** @var string */
    protected $username;
    
    /** @var string */
    protected $token;

    /** @var Client */
    protected $client;

    /** @var Server */
    protected $server;

    public function __construct($server = null)
    {
        if ($server) {
            $this->server = $server;
            
            // Buat base URL dari data server
            $protocol = $server->secure ? 'https://' : 'http://';
            $this->baseUrl = $protocol . $server->hostname . ':' . $server->port . '/json-api/';
            
            // Set headers berdasarkan data server
            $this->headers = [
                'Authorization' => 'WHM ' . $server->username . ':' . $server->accesshash,
                'Content-Type' => 'application/json'
            ];
        }
    }

    // Create Account
    // public function createAccount($params)
    // {
    //     try {
    //         $serviceId = $params['service_id'] ?? null;
            
    //         // Validasi parameter yang diperlukan
    //         if (empty($params['username']) || empty($params['domain'])) {
    //             return [
    //                 'success' => false,
    //                 'message' => 'Username and domain are required'
    //             ];
    //         }

    //         // Validasi server configuration
    //         if (empty($this->server->hostname) || empty($this->server->username) || empty($this->server->accesshash)) {
    //             return [
    //                 'success' => false,
    //                 'message' => 'Invalid server configuration'
    //             ];
    //         }

    //         // Build API URL
    //         $url = "https://{$this->server->hostname}:{$this->server->port}/json-api/createacct";

    //         // Build POST data
    //         $postFields = http_build_query([
    //             'username' => $params['username'],
    //             'domain' => $params['domain'],
    //             'password' => $params['password'] ?? $this->generatePassword(),
    //             'plan' => $params['plan'] ?? 'default',
    //             'contactemail' => $params['email'] ?? '',
    //             'pkgname' => $params['plan'] ?? 'default'
    //         ]);

    //         // Setup cURL
    //         $ch = curl_init();
    //         curl_setopt($ch, CURLOPT_URL, $url);
    //         curl_setopt($ch, CURLOPT_POST, 1);
    //         curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    //         curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    //         curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    //         curl_setopt($ch, CURLOPT_HTTPHEADER, [
    //             "Authorization: WHM {$this->server->username}:{$this->server->accesshash}"
    //         ]);

    //         // Execute request
    //         $response = curl_exec($ch);
    //         $error = curl_error($ch);
    //         curl_close($ch);

    //         if ($error) {
    //             Log::error('cPanel API Error', [
    //                 'error' => $error,
    //                 'url' => $url
    //             ]);
    //             return [
    //                 'success' => false,
    //                 'message' => "API call failed: $error"
    //             ];
    //         }

    //         // Parse response
    //         $result = json_decode($response, true);
            
    //         Log::debug('cPanel API Response', [
    //             'response' => $result
    //         ]);

    //         // Cek response dengan lebih detail
    //         if (isset($result['result']) && is_array($result['result'])) {
    //             foreach ($result['result'] as $item) {
    //                 if (isset($item['status']) && $item['status'] == 1) {
    //                     // Update database jika perlu
    //                     try {
    //                         \DB::table('tblhosting')
    //                             ->where('id', $params['service_id'])
    //                             ->update([
    //                                 'domainstatus' => 'Active',
    //                                 'username' => $params['username'],
    //                                 'domain' => $params['domain']
    //                             ]);
    //                     } catch (\Exception $e) {
    //                         Log::warning('Failed to update hosting status', [
    //                             'error' => $e->getMessage(),
    //                             'service_id' => $params['service_id']
    //                         ]);
    //                     }

    //                     return [
    //                         'success' => true,
    //                         'message' => $item['statusmsg'] ?? 'Account created successfully'
    //                     ];
    //                 }
    //             }
    //         }

    //         // Jika sampai sini berarti ada error
    //         $errorMsg = '';
    //         if (isset($result['result'][0]['statusmsg'])) {
    //             $errorMsg = $result['result'][0]['statusmsg'];
    //         } elseif (isset($result['error'])) {
    //             $errorMsg = $result['error'];
    //         }

    //         return [
    //             'success' => false,
    //             'message' => $errorMsg ?: 'Unknown error occurred'
    //         ];

    //     } catch (\Exception $e) {
    //         Log::error('Error in createAccount', [
    //             'error' => $e->getMessage(),
    //             'params' => $params
    //         ]);
            
    //         return [
    //             'success' => false,
    //             'message' => "Creation error: " . $e->getMessage()
    //         ];
    //     }
    // }
    public function createAccount($params)
{
    try {
        $serviceId = $params['service_id'] ?? null;
        
        // Validasi parameter wajib
        $requiredParams = ['username', 'domain', 'password'];
        foreach ($requiredParams as $param) {
            if (empty($params[$param])) {
                return [
                    'success' => false,
                    'message' => "Parameter '$param' is required"
                ];
            }
        }

        // Validasi format username
        if (!preg_match('/^[a-z0-9][a-z0-9-]*[a-z0-9]$/', $params['username'])) {
            return [
                'success' => false,
                'message' => 'Username must contain only lowercase letters, numbers, and hyphens'
            ];
        }

        // Validasi panjang username
        if (strlen($params['username']) > 16) {
            return [
                'success' => false,
                'message' => 'Username cannot be longer than 16 characters'
            ];
        }

        // Validasi domain
        if (!filter_var('http://' . $params['domain'], FILTER_VALIDATE_URL)) {
            return [
                'success' => false,
                'message' => 'Invalid domain name format'
            ];
        }

        // Validasi server configuration
        if (empty($this->server->hostname) || empty($this->server->username) || empty($this->server->accesshash)) {
            Log::error('Invalid server configuration', [
                'hostname' => $this->server->hostname ?? 'missing',
                'username' => $this->server->username ?? 'missing',
                'has_accesshash' => !empty($this->server->accesshash)
            ]);
            return [
                'success' => false,
                'message' => 'Invalid server configuration'
            ];
        }

        // Build API URL
        $url = "https://{$this->server->hostname}:{$this->server->port}/json-api/createacct";

        // Build POST data dengan parameter lengkap
        $postFields = http_build_query([
            'username' => $params['username'],
            'domain' => $params['domain'],
            'password' => $params['password'],
            'plan' => $params['plan'] ?? 'default',
            'pkgname' => $params['plan'] ?? 'default',
            'contactemail' => $params['email'] ?? '',
            'dkim' => 1,
            'spf' => 1,
            'forcedns' => 1,
            'mxcheck' => 'auto',
            'max_email_per_hour' => $params['max_email_per_hour'] ?? 'unlimited',
            'max_defer_fail_percentage' => $params['max_defer_fail_percentage'] ?? '80',
            'ip' => $params['ip'] ?? '',
            'cgi' => 1,
            'hasshell' => 0,
            'cpmod' => 'paper_lantern',
            'maxsql' => $params['maxsql'] ?? 'unlimited',
            'maxpop' => $params['maxpop'] ?? 'unlimited',
            'maxlst' => $params['maxlst'] ?? 'unlimited',
            'maxsub' => $params['maxsub'] ?? 'unlimited',
            'maxpark' => $params['maxpark'] ?? 'unlimited',
            'maxaddon' => $params['maxaddon'] ?? 'unlimited',
            'bwlimit' => $params['bwlimit'] ?? 'unlimited',
            'language' => $params['language'] ?? 'en'
        ]);

        // Setup cURL dengan timeout dan error handling
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                "Authorization: WHM {$this->server->username}:{$this->server->accesshash}"
            ]
        ]);

        // Execute request
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Log request details
        Log::info('WHM API Request', [
            'url' => $url,
            'username' => $params['username'],
            'domain' => $params['domain'],
            'http_code' => $httpCode
        ]);

        if ($error) {
            Log::error('cPanel API Error', [
                'error' => $error,
                'url' => $url,
                'http_code' => $httpCode
            ]);
            return [
                'success' => false,
                'message' => "API call failed: $error"
            ];
        }

        // Parse response
        $result = json_decode($response, true);
        
        Log::debug('cPanel API Response', [
            'response' => $result,
            'http_code' => $httpCode
        ]);

        // Cek response dengan lebih detail
        if (isset($result['result']) && is_array($result['result'])) {
            foreach ($result['result'] as $item) {
                if (isset($item['status']) && $item['status'] == 1) {
                    // Update database
                    try {
                        \DB::table('tblhosting')
                            ->where('id', $serviceId)
                            ->update([
                                'domainstatus' => 'Active',
                                'username' => $params['username'],
                                'domain' => $params['domain'],
                                'lastupdate' => now()
                            ]);

                        return [
                            'success' => true,
                            'message' => $item['statusmsg'] ?? 'Account created successfully',
                            'data' => [
                                'username' => $params['username'],
                                'domain' => $params['domain'],
                                'package' => $params['plan'] ?? 'default'
                            ]
                        ];
                    } catch (\Exception $e) {
                        Log::warning('Failed to update hosting status', [
                            'error' => $e->getMessage(),
                            'service_id' => $serviceId
                        ]);
                        
                        return [
                            'success' => true,
                            'message' => 'Account created but database update failed',
                            'warning' => $e->getMessage()
                        ];
                    }
                }
            }
        }

        // Handle error response
        $errorMsg = isset($result['result'][0]['statusmsg']) ? $result['result'][0]['statusmsg'] : 
                   (isset($result['error']) ? $result['error'] : 'Unknown error occurred');

        Log::error('Account creation failed', [
            'error_message' => $errorMsg,
            'raw_response' => $result
        ]);

        return [
            'success' => false,
            'message' => $errorMsg
        ];

    } catch (\Exception $e) {
        Log::error('Error in createAccount', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'params' => $params
        ]);
        
        return [
            'success' => false,
            'message' => "Creation error: " . $e->getMessage()
        ];
    }
}

    protected function generatePassword($length = 12)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $password;
    }

    // Suspend Account
    public function suspendAccount($username, $reason = '')
    {
        try {
            if (empty($username)) {
                return [
                    'success' => false,
                    'message' => 'Username is required'
                ];
            }

            $url = "https://{$this->server->hostname}:{$this->server->port}/json-api/suspendacct";
            
            $postFields = http_build_query([
                'user' => $username,
                'reason' => $reason ?: 'Suspended by admin'
            ]);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: WHM {$this->server->username}:{$this->server->accesshash}"
            ]);

            $response = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                Log::error('cPanel Suspend Error', [
                    'error' => $error,
                    'username' => $username
                ]);
                return [
                    'success' => false,
                    'message' => "API call failed: $error"
                ];
            }

            $result = json_decode($response, true);
            Log::debug('cPanel Suspend Response', ['response' => $result]);

            // Cek jika akun sudah suspended
            if (isset($result['result'][0]['statusmsg']) && 
                strpos($result['result'][0]['statusmsg'], 'already suspended') !== false) {
                return [
                    'success' => true,
                    'message' => 'Account is already suspended'
                ];
            }

            // Cek status suspend berhasil
            if (isset($result['result'][0]['status']) && $result['result'][0]['status'] == 1) {
                try {
                    // Update status di database
                    \DB::table('tblhosting')
                        ->where('username', $username)
                        ->update([
                            'domainstatus' => 'Suspended'
                        ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to update hosting status', [
                        'error' => $e->getMessage(),
                        'username' => $username
                    ]);
                }

                return [
                    'success' => true,
                    'message' => 'Account suspended successfully'
                ];
            }

            return [
                'success' => false,
                'message' => $result['result'][0]['statusmsg'] ?? 'Failed to suspend account'
            ];

        } catch (\Exception $e) {
            Log::error('Error in suspendAccount', [
                'error' => $e->getMessage(),
                'username' => $username
            ]);
            return [
                'success' => false,
                'message' => "Suspension error: " . $e->getMessage()
            ];
        }
    }

    // Unsuspend Account
    public function unsuspendAccount($username)
    {
        try {
            if (empty($username)) {
                return [
                    'success' => false,
                    'message' => 'Username is required'
                ];
            }

            $url = "https://{$this->server->hostname}:{$this->server->port}/json-api/unsuspendacct";
            
            $postFields = http_build_query([
                'user' => $username
            ]);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: WHM {$this->server->username}:{$this->server->accesshash}"
            ]);

            $response = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                Log::error('cPanel Unsuspend Error', [
                    'error' => $error,
                    'username' => $username
                ]);
                return [
                    'success' => false,
                    'message' => "API call failed: $error"
                ];
            }

            $result = json_decode($response, true);
            Log::debug('cPanel Unsuspend Response', ['response' => $result]);

            // Cek jika akun sudah active
            if (isset($result['result'][0]['statusmsg']) && 
                strpos($result['result'][0]['statusmsg'], 'already active') !== false) {
                return [
                    'success' => true,
                    'message' => 'Account is already active'
                ];
            }

            // Cek status unsuspend berhasil
            if (isset($result['result'][0]['status']) && $result['result'][0]['status'] == 1) {
                try {
                    // Update status di database
                    \DB::table('tblhosting')
                        ->where('username', $username)
                        ->update([
                            'domainstatus' => 'Active'
                        ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to update hosting status', [
                        'error' => $e->getMessage(),
                        'username' => $username
                    ]);
                }

                return [
                    'success' => true,
                    'message' => 'Account unsuspended successfully'
                ];
            }

            return [
                'success' => false,
                'message' => $result['result'][0]['statusmsg'] ?? 'Failed to unsuspend account'
            ];

        } catch (\Exception $e) {
            Log::error('Error in unsuspendAccount', [
                'error' => $e->getMessage(),
                'username' => $username
            ]);
            return [
                'success' => false,
                'message' => "Unsuspension error: " . $e->getMessage()
            ];
        }
    }

    // Terminate Account
    public function terminateAccount($username)
    {
        try {
            if (empty($username)) {
                return [
                    'success' => false,
                    'message' => 'Username is required'
                ];
            }

            // $serverConfig = $this->server['server']['stdClass'];
            
            // Akses langsung ke properti server
            $hostname = $this->server->hostname;
            $port = $this->server->port;
            $serverUsername = $this->server->username;
            $accessHash = $this->server->accesshash;
    
            Log::debug('Server Config Details', [
                'hostname' => $hostname,
                'port' => $port,
                'username' => $serverUsername
            ]);

            
            // $hostname = $serverConfig->hostname;
            // $port = $serverConfig->port;
            // $serverUsername = $serverConfig->username;
            // $accessHash = $serverConfig->accesshash;

            // Log::debug('Server Config Details', [
            //     'hostname' => $hostname,
            //     'port' => $port,
            //     'username' => $serverUsername,
            //     'raw_config' => $serverConfig
            // ]);

            $url = "https://{$hostname}:{$port}/json-api/removeacct";
            
            $postFields = http_build_query([
                'api.version' => 1,
                'user' => $username,
                'keepdns' => 0
            ]);

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postFields,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => [
                    "Authorization: WHM {$serverUsername}:{$accessHash}"
                ]
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            Log::debug('cPanel API Request', [
                'url' => $url,
                'http_code' => $httpCode,
                'curl_error' => $error,
                'response' => $response
            ]);

            curl_close($ch);

            if ($error) {
                Log::error('cPanel Terminate Error (CURL)', [
                    'error' => $error,
                    'username' => $username
                ]);
                return [
                    'success' => false,
                    'message' => "API connection failed: $error"
                ];
            }

            // Parse JSON response
            $result = json_decode($response, true);
            
            Log::debug('cPanel Terminate Response', [
                'parsed_response' => $result
            ]);

            // Cek response WHM yang menunjukkan sukses
            if (isset($result['metadata'])) {
                if ($result['metadata']['result'] === 1 || 
                    (isset($result['metadata']['output']['raw']) && 
                     (strpos($result['metadata']['output']['raw'], 'Account Removal Complete') !== false ||
                      strpos($result['metadata']['output']['raw'], 'account removed') !== false))
                ) {
                    return [
                        'success' => true,
                        'message' => 'Account terminated successfully'
                    ];
                }

                // Jika akun sudah tidak ada, anggap sukses
                if (strpos($result['metadata']['output']['raw'], 'does not exist') !== false) {
                    return [
                        'success' => true,
                        'message' => 'Account already removed'
                    ];
                }
            }

            // Jika response menunjukkan akun berhasil dihapus tapi format berbeda
            if (isset($result['result']) && $result['result'] === 1) {
                return [
                    'success' => true,
                    'message' => 'Account terminated successfully'
                ];
            }

            // Jika sampai sini tapi ada indikasi akun sudah terhapus di WHM
            if ($response && (
                strpos($response, 'Account Removal Complete') !== false ||
                strpos($response, 'account removed') !== false ||
                strpos($response, 'does not exist') !== false
            )) {
                return [
                    'success' => true,
                    'whm_success' => true,
                    'message' => 'Account terminated successfully in WHM'
                ];
            }

            // Jika sampai sini berarti ada error
            $errorMessage = 
                $result['metadata']['reason'] ?? 
                $result['metadata']['output']['raw'] ?? 
                $result['error'] ?? 
                $result['message'] ?? 
                'Unknown error occurred during termination';

            Log::error('Termination failed', [
                'username' => $username,
                'error_message' => $errorMessage,
                'full_response' => $result
            ]);

            return [
                'success' => false,
                'message' => $errorMessage
            ];

        } catch (\Exception $e) {
            Log::error('Exception in terminateAccount', [
                'error' => $e->getMessage(),
                'username' => $username,
                'trace' => $e->getTraceAsString(),
                'server' => $this->server
            ]);
            
            return [
                'success' => false,
                'message' => "Termination error: " . $e->getMessage()
            ];
        }
    }

    // Change Password
    public function changePassword($username, $password)
    {
        try {
            if (empty($username) || empty($password)) {
                return [
                    'success' => false,
                    'message' => 'Username and password are required'
                ];
            }

            $serverConfig = $this->server['server']['stdClass'];
            
            $hostname = $serverConfig->hostname;
            $port = $serverConfig->port;
            $serverUsername = $serverConfig->username;
            $accessHash = $serverConfig->accesshash;

            Log::debug('Change Password Config', [
                'hostname' => $hostname,
                'port' => $port,
                'username' => $username
            ]);

            $url = "https://{$hostname}:{$port}/json-api/passwd";
            
            $postFields = http_build_query([
                'api.version' => 1,
                'user' => $username,
                'password' => $password
            ]);

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postFields,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => [
                    "Authorization: WHM {$serverUsername}:{$accessHash}"
                ]
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            Log::debug('cPanel Change Password Response', [
                'http_code' => $httpCode,
                'response' => $response
            ]);

            curl_close($ch);

            if ($error) {
                return [
                    'success' => false,
                    'message' => "API connection failed: $error"
                ];
            }

            $result = json_decode($response, true);

            if (isset($result['metadata']) && $result['metadata']['result'] === 1) {
                return [
                    'success' => true,
                    'message' => 'Password changed successfully'
                ];
            }

            // Handle various error responses
            $errorMessage = 
                $result['metadata']['reason'] ?? 
                $result['metadata']['output']['raw'] ?? 
                $result['error'] ?? 
                $result['message'] ?? 
                'Failed to change password';

            return [
                'success' => false,
                'message' => $errorMessage
            ];

        } catch (\Exception $e) {
            Log::error('Exception in changePassword', [
                'error' => $e->getMessage(),
                'username' => $username
            ]);
            
            return [
                'success' => false,
                'message' => "Change password error: " . $e->getMessage()
            ];
        }
    }

    public function checkAccount($params)
    {
        try {
            $headers = [
                'Authorization' => $this->headers['Authorization']
            ];

            Log::info('Checking cPanel account', [
                'username' => $params['username']
            ]);

            $response = Http::withHeaders($headers)
                ->withOptions([
                    'verify' => false
                ])
                ->get($this->baseUrl . 'listaccts', [
                    'api.version' => 1,
                    'search' => $params['username'],
                    'searchtype' => 'user'
                ]);

            $data = json_decode($response->body(), true);

            Log::info('cPanel Check Account Response', [
                'response' => $data
            ]);

            if (isset($data['data']['acct']) && count($data['data']['acct']) > 0) {
                return [
                    'success' => true,
                    'exists' => true,
                    'data' => $data['data']['acct'][0]
                ];
            }

            return [
                'success' => true,
                'exists' => false
            ];
        } catch (\Exception $e) {
            Log::error('cPanel Check Account Error', [
                'error' => $e->getMessage(),
                'username' => $params['username']
            ]);
            return [
                'success' => false,
                'exists' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    protected function handleResponse($response)
    {
        try {
            $data = $response->json();

            // Log response untuk debugging
            Log::info('Handling cPanel Response', [
                'data' => $data
            ]);

            // Cek status response
            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $data['data'] ?? $data,
                    'message' => $data['message'] ?? 'Success'
                ];
            }

            return [
                'success' => false,
                'message' => $data['errors'][0] ?? $data['message'] ?? 'Unknown error occurred'
            ];
        } catch (\Exception $e) {
            Log::error('Error handling response', [
                'error' => $e->getMessage(),
                'response' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'Error processing response: ' . $e->getMessage()
            ];
        }
    }

    // Change Package
    public function changePackage($username, $newPackage)
    {
        try {
            $whmUsername = config('cpanel.username');
            $whmToken = config('cpanel.token');
            $whmHost = config('cpanel.hostname');

            Log::info('Changing package in WHM', [
                'username' => $username,
                'new_package' => $newPackage
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'WHM ' . $whmUsername . ':' . $whmToken
            ])
            ->withOptions([
                'verify' => false
            ])
            ->post($whmHost . '/json-api/changepackage', [
                'api.version' => 1,
                'user' => $username,
                'pkg' => $newPackage
            ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to change package: ' . $response->body());
            }

            $data = $response->json();

            Log::info('WHM change package response:', [
                'response' => $data
            ]);

            if (isset($data['metadata']) && $data['metadata']['result'] == 0) {
                throw new \Exception($data['metadata']['reason'] ?? 'Unknown error occurred');
            }

            return [
                'success' => true,
                'message' => 'Package changed successfully',
                'data' => $data
            ];
        } catch (\Exception $e) {
            Log::error('Error changing package in WHM', [
                'error' => $e->getMessage(),
                'username' => $username,
                'new_package' => $newPackage,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                    'success' => false,
                    'message' => 'Error changing package: ' . $e->getMessage()
                ];
        }
    }

    protected function validatePackageName($packageName)
    {
        try {
            Log::info('Memvalidasi nama paket', ['package' => $packageName]);

            // Validasi format nama paket
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $packageName)) {
                Log::warning('Format nama paket tidak valid', ['package' => $packageName]);
                return false;
            }

            // Cek keberadaan paket di cPanel
            $response = Http::withHeaders($this->headers)
                ->withOptions(['verify' => false])
                ->get($this->baseUrl . 'listpkgs');

            $data = $response->json();

            if (isset($data['pkg'])) {
                $availablePackages = array_map(function ($pkg) {
                    return $pkg['name'];
                }, $data['pkg']);

                $exists = in_array($packageName, $availablePackages);

                Log::info('Hasil validasi paket', [
                    'package' => $packageName,
                    'exists' => $exists,
                    'available_packages' => $availablePackages
                ]);

                return $exists;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Error validasi nama paket', [
                'error' => $e->getMessage(),
                'package' => $packageName
            ]);
            return false;
        }
    }

    public function getPackages()
    {
        try {
            $whmUsername = config('cpanel.username');
            $whmToken = config('cpanel.token');
            $whmHost = config('cpanel.hostname');

            Log::info('Fetching packages from WHM', [
                'host' => $whmHost,
                'username' => $whmUsername
            ]);

            // Gunakan endpoint yang benar untuk WHM API v1
            $response = Http::withHeaders([
                'Authorization' => 'WHM ' . $whmUsername . ':' . $whmToken
            ])
            ->withOptions([
                'verify' => false
            ])
            ->get($whmHost . '/json-api/listpkgs', [
                'api.version' => 1
            ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to fetch packages: ' . $response->body());
            }

            $data = $response->json();
            
            // Debug response
            Log::debug('WHM API Response:', [
                'response' => $data
            ]);

            // Periksa struktur data yang benar dari WHM API
            if (!isset($data['data']) || !isset($data['data']['pkg'])) {
                throw new \Exception('Invalid package data format from WHM');
            }

            // Extract package names dari struktur yang benar
            $packages = array_map(function($pkg) {
                return $pkg['name'];
            }, $data['data']['pkg']);

            Log::info('Successfully fetched packages from WHM', [
                'packages' => $packages
            ]);

            return [
                'success' => true,
                'data' => $packages
            ];

        } catch (\Exception $e) {
            Log::error('Error getting packages from WHM', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error getting packages: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Test koneksi ke server cPanel
     */
    public function testConnection()
    {
        try {
            // Cek kredensial server
            if (empty($this->server->hostname)) {
                return [
                    'success' => false,
                    'message' => 'Server hostname is empty'
                ];
            }

            // Coba koneksi ke WHM API
            $url = "https://{$this->server->hostname}:{$this->server->port}/json-api/version";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: WHM {$this->server->username}:{$this->server->accesshash}"
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                return [
                    'success' => false,
                    'message' => "Connection failed: $error"
                ];
            }

            if ($httpCode !== 200) {
                return [
                    'success' => false,
                    'message' => "Server returned HTTP code $httpCode"
                ];
            }

            return [
                'success' => true,
                'message' => 'Connection successful'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => "Connection error: " . $e->getMessage()
            ];
        }
    }
}
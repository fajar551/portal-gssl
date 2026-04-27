<?php

namespace Modules\Servers\CPanel\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Servers\CPanel\Services\CpanelService;
use App\Helpers\AdminFunctions;
use App\Helpers\ResponseAPI;
use App\Helpers\Database;
use App\Helpers\LogActivity;
use App\Models\Hosting;
use App\Traits\DatatableFilter;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use App\Models\Product as ProductModel;

class CPanelController extends Controller
{
    use DatatableFilter;
    protected $prefix;
    protected $cpanelService;

    public function __construct(CpanelService $cpanelService = null)
    {
        $this->cpanelService = $cpanelService ?? new CpanelService(); // Jika null, buat instance baru
        $this->middleware(['auth:admin']);
        $this->prefix = Database::prefix();
    }

    public function ConfigOptions($params = [])
    {
        return array(
            "Package Name" => [
                "Type" => "text",
                "Size" => "30",
                "Description" => "",
                "Default" => "default"
            ],
            "Disk Space Quota (MB)" => [
                "Type" => "text",
                "Size" => "10",
                "Description" => "MB (Enter 0 for unlimited)",
                "Default" => "10240"
            ],
            "Monthly Bandwidth Limit (MB)" => [
                "Type" => "text",
                "Size" => "10",
                "Description" => "MB (Enter 0 for unlimited)",
                "Default" => "1048576"
            ],
            "Max FTP Accounts" => [
                "Type" => "text",
                "Size" => "5",
                "Description" => "Enter 0 for unlimited",
                "Default" => "0"
            ],
            "Max Email Accounts" => [
                "Type" => "text",
                "Size" => "5",
                "Description" => "Enter 0 for unlimited",
                "Default" => "0"
            ],
            "Max Mailing Lists" => [
                "Type" => "text",
                "Size" => "5",
                "Description" => "Enter 0 for unlimited",
                "Default" => "0"
            ],
            "Max SQL Databases" => [
                "Type" => "text",
                "Size" => "5",
                "Description" => "Enter 0 for unlimited",
                "Default" => "0"
            ],
            "Max Sub Domains" => [
                "Type" => "text",
                "Size" => "5",
                "Description" => "Enter 0 for unlimited",
                "Default" => "0"
            ],
            "Max Parked Domains" => [
                "Type" => "text",
                "Size" => "5",
                "Description" => "Enter 0 for unlimited",
                "Default" => "0"
            ],
            "Max Addon Domains" => [
                "Type" => "text",
                "Size" => "5",
                "Description" => "Enter 0 for unlimited",
                "Default" => "0"
            ],
            "Max Passenger Applications" => [
                "Type" => "text",
                "Size" => "5",
                "Description" => "Enter 0 for unlimited",
                "Default" => "4"
            ],
            "Maximum Hourly Email by Domain Relayed" => [
                "Type" => "text",
                "Size" => "5",
                "Description" => "Enter 0 for unlimited",
                "Default" => "0"
            ],
            "Maximum percentage of failed or deferred messages a domain may send per hour" => [
                "Type" => "text",
                "Size" => "5",
                "Description" => "Default 100",
                "Default" => "100"
            ],
            "Max Quota per Email Address (MB)" => [
                "Type" => "text",
                "Size" => "10",
                "Description" => "MB (Enter 0 for unlimited)",
                "Default" => "1024"
            ]
        );
    }

    public function index()
    {
        return view('cpanel::index');
    }

    public function moduleCommand(Request $request)
    {
        try {
            $userid = $request->input('id');
            $modop = $request->modop;

            $serviceId = $request->input('id');

            // Validasi permission
            if (!AdminFunctions::checkPermission("Perform Server Operations")) {
                return ResponseAPI::Error([
                    'message' => AdminFunctions::infoBoxMessage('<b>Oh No!</b>', AdminFunctions::getNoPermissionMessage()),
                ]);
            }
            
            $service = Hosting::with(['server'])->findOrFail($serviceId);

            // Tambahkan pengecekan service dan packagename
        // if (!$service || !$service->packagename) {
        //     throw new \Exception("Service or package information not found");
        // }

            $newPackage = $request->input('package');

            

            // Get service details
            // $service = \DB::table('tblhosting')
            //     ->where('id', $serviceId)
            //     ->first();
            
            // Get service details dengan semua data yang dibutuhkan termasuk email client
        $service = \DB::table('tblhosting')
            ->select(
                'tblhosting.*', 
                'tblproducts.configoption1', 
                'tblproducts.name as package_name',
                'tblclients.email'  // Tambahkan email dari tblclients
            )
            ->leftJoin('tblproducts', 'tblhosting.packageid', '=', 'tblproducts.id')
            ->leftJoin('tblclients', 'tblhosting.userid', '=', 'tblclients.id')  // Join dengan tblclients
            ->where('tblhosting.id', $serviceId)
            ->first();

            if (!$service) {
                throw new \Exception("Service not found");
            }

            // Get server details
            $server = \DB::table('tblservers')
                ->where('id', $service->server)
                ->first();

            if (!$server) {
                throw new \Exception("Server not found");
            }

            if ($modop == "getpackages") {
                try {
                    // Ambil daftar paket dari cPanel
                    $result = $this->cpanelService->getPackages();

                    Log::info('CPanel getPackages result', [
                        'result' => $result
                    ]);

                    if (!$result['success']) {
                        throw new \Exception($result['message']);
                    }

                    // Gunakan packagename dari service
                    $currentPackage = $service->packagename ?? 'WireBusiness_Fiber_50'; // default fallback
                    $packages = $result['data'];

                    Log::info('Processing packages', [
                        'current_package' => $currentPackage,
                        'available_packages' => $packages
                    ]);

                    // Build HTML options
                    $options = '<option value="">Pilih paket baru</option>';
                    foreach ($packages as $package) {
                        // Normalize package names for comparison
                        $normalizedPackage = trim(str_replace([' ', '-'], '_', strtolower($package)));
                        $normalizedCurrentPackage = trim(str_replace([' ', '-'], '_', strtolower($currentPackage)));

                        Log::info('Comparing packages', [
                            'normalized_package' => $normalizedPackage,
                            'normalized_current' => $normalizedCurrentPackage,
                            'original_package' => $package,
                            'original_current' => $currentPackage
                        ]);

                        // Skip if this is the current package
                        if ($normalizedPackage === $normalizedCurrentPackage) {
                            continue;
                        }

                        $displayName = str_replace('_', ' ', $package);
                        $options .= sprintf(
                            '<option value="%s">%s</option>',
                            htmlspecialchars($package),
                            htmlspecialchars($displayName)
                        );
                    }

                    return response()->json([
                        'result' => 'success',
                        'data' => [
                            'current_package' => $currentPackage,
                            'package_options' => $options
                        ]
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error getting packages', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);

                    return response()->json([
                        'result' => 'error',
                        'message' => 'Gagal mengambil data paket: ' . $e->getMessage()
                    ]);
                }
            }

            if ($modop === 'changepackage') {
                try {
                    $newPackage = $request->input('packagename');

                    if (empty($newPackage)) {
                        throw new \Exception('Package name cannot be empty');
                    }

                    Log::info('Starting package change', [
                        'username' => $service->username,
                        'new_package' => $newPackage,
                        'request_data' => $request->all()
                    ]);

                    // Validasi package name
                    $packages = $this->cpanelService->getPackages();
                    if (!$packages['success']) {
                        throw new \Exception('Failed to get package list: ' . ($packages['message'] ?? 'Unknown error'));
                    }

                    if (!in_array($newPackage, $packages['data'])) {
                        throw new \Exception("Package '$newPackage' not found in WHM");
                    }

                    // Ubah package di WHM
                    $result = $this->cpanelService->changePackage($service->username, $newPackage);

                    if (!$result['success']) {
                        throw new \Exception($result['message'] ?? 'Failed to change package in WHM');
                    }

                    // Tidak perlu update di database karena packageid tidak berubah
                    Log::info('Package changed successfully in WHM', [
                        'username' => $service->username,
                        'new_package' => $newPackage,
                        'whm_response' => $result
                    ]);

                    return response()->json([
                        'result' => 'success',
                        'message' => 'Package changed successfully'
                    ]);
                } catch (\Exception $e) {
                    Log::error('Change package error', [
                        'error' => $e->getMessage(),
                        'username' => $service->username ?? null,
                        'requested_package' => $newPackage ?? null,
                        'trace' => $e->getTraceAsString()
                    ]);

                    return response()->json([
                        'result' => 'error',
                        'message' => $e->getMessage()
                    ]);
                }
            }

            switch ($modop) {
              case 'create':
                    try {
                        $cpanelService = new CpanelService($server);
                        
                        // Normalisasi nama package
                        $packageName = $service->package_name ?? $service->configoption1 ?? 'Pro_Starter';
                        $plan = str_replace(' ', '_', trim($packageName)); // Ubah spasi menjadi underscore
                        
                        // Log package name untuk debugging
                        Log::info('Creating account with package', [
                            'original_package' => $packageName,
                            'normalized_package' => $plan
                        ]);
                        
                        $result = $cpanelService->createAccount([
                            'username' => $service->username,
                            'password' => $service->password,
                            'domain' => $service->domain,
                            'plan' => $plan,
                            'contactemail' => $service->email ?? 'noreply@example.com',
                            'service_id' => $serviceId
                        ]);
                
                        if ($result['success']) {
                            LogActivity::save("Created hosting account - Service ID: $serviceId");
                            return ResponseAPI::Success([
                                'message' => AdminFunctions::infoBoxMessage(
                                    '<b>Success!</b>',
                                    'Account has been created successfully'
                                )
                            ]);
                        } else {
                            throw new \Exception($result['message']);
                        }
                    } catch (\Exception $e) {
                        throw new \Exception("Failed to create account: " . $e->getMessage());
                    }
                    break;

                case 'suspend':
                    try {
                        $cpanelService = new CpanelService($server);
                        $result = $cpanelService->suspendAccount($service->username);

                        if ($result['success']) {
                            LogActivity::save("Suspended hosting account - Service ID: $serviceId");
                            return ResponseAPI::Success([
                                'message' => AdminFunctions::infoBoxMessage(
                                    '<b>Success!</b>',
                                    'Account has been suspended successfully'
                                )
                            ]);
                        } else {
                            throw new \Exception($result['message']);
                        }
                    } catch (\Exception $e) {
                        throw new \Exception("Failed to suspend account: " . $e->getMessage());
                    }
                    break;

                case 'unsuspend':
                    try {
                        $cpanelService = new CpanelService($server);
                        $result = $cpanelService->unsuspendAccount($service->username);

                        if ($result['success']) {
                            LogActivity::save("Unsuspended hosting account - Service ID: $serviceId");
                            return ResponseAPI::Success([
                                'message' => AdminFunctions::infoBoxMessage(
                                    '<b>Success!</b>',
                                    'Account has been unsuspended successfully'
                                )
                            ]);
                        } else {
                            throw new \Exception($result['message']);
                        }
                    } catch (\Exception $e) {
                        throw new \Exception("Failed to unsuspend account: " . $e->getMessage());
                    }
                    break;

                // case 'terminate':
                //     try {
                //         $cpanel = new CpanelService([
                //             'server' => [
                //                 'stdClass' => $server
                //             ]
                //         ]);
                        
                //         $result = $cpanel->terminateAccount($service->username);
                        
                //         if ($result['success']) {
                //             // Update status di database
                //             \DB::table('tblhosting')
                //                 ->where('id', $serviceId)
                //                 ->update(['domainstatus' => 'Terminated']);
                            
                //             LogActivity::save("Terminated hosting account - Service ID: $serviceId");
                            
                //             return ResponseAPI::Success([
                //                 'message' => AdminFunctions::infoBoxMessage(
                //                     '<b>Success!</b>',
                //                     'Account has been terminated successfully'
                //                 )
                //             ]);
                //         }
                        
                //         // Jika terminasi di WHM berhasil tapi ada error di local
                //         if (isset($result['whm_success']) && $result['whm_success']) {
                //             // Update status di database
                //             \DB::table('tblhosting')
                //                 ->where('id', $serviceId)
                //                 ->update(['domainstatus' => 'Terminated']);
                            
                //             LogActivity::save("Terminated hosting account (WHM success) - Service ID: $serviceId");
                            
                //             return ResponseAPI::Success([
                //                 'message' => AdminFunctions::infoBoxMessage(
                //                     '<b>Success!</b>',
                //                     'Account has been terminated successfully in WHM'
                //                 )
                //             ]);
                //         }

                //         throw new \Exception($result['message']);
                //     } catch (\Exception $e) {
                //         Log::error('Error in terminate command', [
                //             'error' => $e->getMessage(),
                //             'service_id' => $serviceId
                //         ]);
                        
                //         return ResponseAPI::Error([
                //             'message' => AdminFunctions::infoBoxMessage(
                //                 '<b>Error!</b>',
                //                 $e->getMessage()
                //             )
                //         ]);
                //     }
                //     break;
                case 'terminate':
    try {
        // Inisialisasi CpanelService dengan benar
        $cpanelService = new CpanelService($server); // Langsung passing server object
        
        $result = $cpanelService->terminateAccount($service->username);
        
        if ($result['success']) {
            // Update status di database
            \DB::table('tblhosting')
                ->where('id', $serviceId)
                ->update(['domainstatus' => 'Terminated']);
            
            LogActivity::save("Terminated hosting account - Service ID: $serviceId");
            
            return ResponseAPI::Success([
                'message' => AdminFunctions::infoBoxMessage(
                    '<b>Success!</b>',
                    'Account has been terminated successfully'
                )
            ]);
        }
        
        // Jika terminasi di WHM berhasil tapi ada error di local
        if (isset($result['whm_success']) && $result['whm_success']) {
            // Update status di database
            \DB::table('tblhosting')
                ->where('id', $serviceId)
                ->update(['domainstatus' => 'Terminated']);
            
            LogActivity::save("Terminated hosting account (WHM success) - Service ID: $serviceId");
            
            return ResponseAPI::Success([
                'message' => AdminFunctions::infoBoxMessage(
                    '<b>Success!</b>',
                    'Account has been terminated successfully in WHM'
                )
            ]);
        }

        throw new \Exception($result['message']);
    } catch (\Exception $e) {
        Log::error('Error in terminate command', [
            'error' => $e->getMessage(),
            'service_id' => $serviceId
        ]);
        
        return ResponseAPI::Error([
            'message' => AdminFunctions::infoBoxMessage(
                '<b>Error!</b>',
                $e->getMessage()
            )
        ]);
    }
    break;

                case 'changepw':
                    try {
                        // Validasi password
                        $password = $request->input('password');
                        if (!$this->validatePassword($password)) {
                            throw new \Exception("Password harus mengandung minimal 8 karakter dengan kombinasi huruf besar, huruf kecil, angka, dan karakter khusus");
                        }

                        $cpanel = new CpanelService([
                            'server' => [
                                'stdClass' => $server
                            ]
                        ]);
                        
                        $result = $cpanel->changePassword($service->username, $password);
                        
                        if ($result['success']) {
                            // Update password di database
                            \DB::table('tblhosting')
                                ->where('id', $serviceId)
                                ->update(['password' => encrypt($password)]);
                            
                            LogActivity::save("Changed password for hosting account - Service ID: $serviceId");
                            
                            return ResponseAPI::Success([
                                'message' => 'Password has been changed successfully',
                                'password' => $password // Kirim password baru ke frontend
                            ]);
                        }
                        
                        throw new \Exception($result['message']);
                    } catch (\Exception $e) {
                        Log::error('Error in changepw command', [
                            'error' => $e->getMessage(),
                            'service_id' => $serviceId
                        ]);
                        
                        return ResponseAPI::Error([
                            'message' => AdminFunctions::infoBoxMessage(
                                '<b>Error!</b>',
                                $e->getMessage()
                            )
                        ]);
                    }
                    break;

                case 'upgrade':
                    $cpanelService = new CpanelService($server);
                    $newPackage = $request->input('packagename');

                    if (empty($newPackage)) {
                        throw new \Exception('Package name is required');
                    }

                    Log::info('Attempting to change package', [
                        'username' => $service->username,
                        'server' => $server->name,
                        'new_package' => $newPackage
                    ]);

                    $result = $cpanelService->changePackage($service->username, $newPackage);

                    if (!$result['success']) {
                        throw new \Exception($result['message']);
                    }

                    // Update configoption1 di database jika perlu
                    try {
                        \DB::table('tblhosting')
                            ->where('id', $serviceId)
                            ->update([
                                'configoption1' => $newPackage
                            ]);
                    } catch (\Exception $e) {
                        Log::warning('Failed to update package in database', [
                            'error' => $e->getMessage(),
                            'service_id' => $serviceId
                        ]);
                    }

                    return ResponseAPI::Success([
                        'message' => AdminFunctions::infoBoxMessage(
                            '<b>Success!</b>',
                            $result['message']
                        )
                    ]);

                default:
                    throw new \Exception("Invalid command: $modop");
            }
        } catch (\Exception $e) {
            Log::error('Error in moduleCommand', [
                'error' => $e->getMessage(),
                'service_id' => $serviceId ?? null,
                'command' => $modop ?? null,
                'request_data' => $request->all()
            ]);

            // throw new \Exception("Failed to {$modop} account: " . $e->getMessage());
            return ResponseAPI::Error([
            'message' => AdminFunctions::infoBoxMessage(
                '<b>Error!</b>',
                $e->getMessage()
            )
        ]);
        }
    }

    private function create(Request $request)
    {
        $id = $request->id;

        try {
            // Ambil data hosting
            $service = Hosting::with(['server', 'product'])->findOrFail($id);

            // Validasi data hosting
            $this->validateHostingData($service);

            // Validasi server
            if (!$service->server || $service->server->type !== 'cpanel') {
                throw new \Exception('Invalid server configuration');
            }

            // Log attempt create
            Log::info('Creating cPanel account', [
                'service_id' => $id,
                'username' => $service->username,
                'domain' => $service->domain,
                'package' => $service->packagename,
                'server' => $service->server->name
            ]);

            // Inisialisasi CpanelService dengan data server
            $cpanelService = new CpanelService($service->server);

            // Buat akun di cPanel
            $result = $cpanelService->createAccount([
                'username' => $service->username,
                'domain' => $service->domain,
                'password' => $service->password,
                'package' => $service->packagename
            ]);

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            // Update status service
            $service->domainstatus = 'Active';
            $service->save();

            // Log activity
            LogActivity::save("Created cPanel Account - Service ID: $id");
            LogActivity::Save("Created cPanel Account - Service ID: $id", $request->userid);

            return ResponseAPI::Success([
                'message' => AdminFunctions::infoBoxMessage('<b>Success!</b>', 'Account has been created successfully!')
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating cPanel account', [
                'error' => $e->getMessage(),
                'service_id' => $id
            ]);

            return ResponseAPI::Error([
                'message' => AdminFunctions::infoBoxMessage('<b>Error!</b>', $e->getMessage())
            ]);
        }
    }

    private function renew(Request $request)
    {
        $id = $request->id;
        $aid = $request->aid;
        $modop = $request->modop;

        $result = (new \App\Module\Server())->ServerRenew($id, (int) $aid);
        return $this->getResponse($modop, $result);
    }

    public function suspend(Request $request)
    {
        try {
            $id = $request->id;
            $service = Hosting::findOrFail($id);

            Log::info('Attempting to suspend account', [
                'service_id' => $id,
                'username' => $service->username,
                'domain' => $service->domain,
                'reason' => $request->suspreason
            ]);

            $result = $this->cpanelService->suspendAccount(
                $service->username,
                $request->input('suspreason', 'Account suspended via admin area')
            );

            if (!$result['success']) {
                throw new \Exception($result['message'] ?? 'Failed to suspend account');
            }

            // Update status di database
            $service->domainstatus = Hosting::STATUS_SUSPENDED;
            $service->save();

            LogActivity::Save("Suspended cPanel Account - Service ID: $id", $request->userid);

            return ResponseAPI::Success([
                'message' => AdminFunctions::infoBoxMessage(
                    '<b>Success!</b>',
                    'Account has been suspended successfully!'
                )
            ]);
        } catch (\Exception $e) {
            Log::error('Error suspending cPanel account', [
                'service_id' => $id ?? null,
                'error' => $e->getMessage()
            ]);

            return ResponseAPI::Error($e->getMessage());
        }
    }

    private function unsuspend(Request $request)
    {
        $id = $request->id;

        try {
            $service = Hosting::findOrFail($id);
            $cpanel = app(CpanelService::class);

            $result = $cpanel->unsuspendAccount($service->username);

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            // Update service status
            $service->domainstatus = 'Active';
            $service->suspendreason = '';
            $service->save();

            return $this->getResponse('unsuspend', 'success');
        } catch (\Exception $e) {
            return $this->getResponse('unsuspend', $e->getMessage());
        }
    }

    private function terminate(Request $request)
    {
        $id = $request->id;

        try {
            $service = Hosting::findOrFail($id);
            $cpanel = app(CpanelService::class);

            $result = $cpanel->terminateAccount($service->username);

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            // Update service status
            $service->domainstatus = 'Terminated';
            $service->termination_date = now();
            $service->save();

            return $this->getResponse('terminate', 'success');
        } catch (\Exception $e) {
            return $this->getResponse('terminate', $e->getMessage());
        }
    }

    public function changePackage($username, $newPackage)
    {
        try {
            Log::info('Starting package change', [
                'username' => $username,
                'new_package' => $newPackage
            ]);

            // 1. Validasi package baru
            $product = ProductModel::where('name', $newPackage)->first();
            if (!$product) {
                throw new \Exception('Package tidak ditemukan');
            }

            // 2. Update hosting
            $hosting = Hosting::where('username', $username)->first();
            if (!$hosting) {
                throw new \Exception('Hosting tidak ditemukan');
            }

            // 3. Update data hosting
            $hosting->packageid = $product->id;
            $hosting->packagename = $product->name;
            $hosting->save();

            // 4. Panggil API cPanel
            $response = Http::withHeaders([
                'Authorization' => 'WHM ' . config('cpanel.username') . ':' . config('cpanel.token')
            ])
                ->withOptions(['verify' => false])
                ->post(config('cpanel.hostname') . '/json-api/changepackage', [
                    'api.version' => 1,
                    'user' => $username,
                    'pkg' => $newPackage
                ]);

            Log::info(
                'Change package response',
                [
                    'response' => $response->json()
                ]
            );

            return [
                'success' => true,
                'data' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('Error changing package', [
                'error' => $e->getMessage(),
                'username' => $username,
                'new_package' => $newPackage
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function changepw(Request $request)
    {
        $id = $request->id;

        try {
            $service = Hosting::findOrFail($id);
            $cpanel = app(CpanelService::class);

            // Generate random password
            $newPassword = Str::random(12);

            $result = $cpanel->changePassword([
                'username' => $service->username,
                'password' => $newPassword
            ]);

            if (!$result['success']) {
                throw new \Exception($result['message']);
            }

            // Update password in database
            $service->password = $newPassword;
            $service->save();

            return $this->getResponse('changepw', 'success');
        } catch (\Exception $e) {
            return $this->getResponse('changepw', $e->getMessage());
        }
    }
    // Helper method untuk validasi data hosting
    private function validateHostingData($service)
    {
        if (empty($service->username)) {
            throw new \Exception('Username tidak boleh kosong');
        }
        if (empty($service->domain)) {
            throw new \Exception('Domain tidak boleh kosong');
        }
        if (empty($service->password)) {
            throw new \Exception('Password tidak boleh kosong');
        }
        if (empty($service->product->name)) {
            throw new \Exception('Package tidak boleh kosong');
        }
    }

    // Helper method untuk handle custom commands
    private function handleCustomCommand($request, $service)
    {
        $command = $request->input('command');
        $params = $request->input('params', []);

        if (empty($command)) {
            throw new \Exception('Custom command tidak boleh kosong');
        }

        $cpanel = app(CpanelService::class);
        $result = $cpanel->executeCustomCommand($command, array_merge([
            'username' => $service->username
        ], $params));

        if (!$result['success']) {
            throw new \Exception($result['message']);
        }

        return ResponseAPI::Success([
            'message' => AdminFunctions::infoBoxMessage('<b>Success!</b>', 'Custom command berhasil dieksekusi'),
            'data' => $result['data']
        ]);
    }

    // Tambahkan validasi sebelum melakukan suspend
    private function validateServiceData($service)
    {
        // Tambahkan validasi lainnya yang sesuai dengan kebutuhan
        // Misalnya, periksa apakah service memiliki status yang valid untuk suspend
        // atau periksa apakah ada kondisi lain yang perlu diperiksa
        // ...
    }

    public function getProducts(Request $request)
    {
        try {
            $serviceId = $request->input('id');

            // Ambil data service
            $service = Hosting::with(['product'])->findOrFail($serviceId);

            // Ambil daftar produk yang sesuai
            $products = ProductModel::where('servertype', $service->product->servertype)
                ->where('type', $service->product->type)
                ->where('hidden', 0)
                ->orderBy('name', 'asc')
                ->get(['id', 'name']);

            return response()->json([
                'success' => true,
                'data' => $products
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting products', [
                'error' => $e->getMessage(),
                'service_id' => $serviceId ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    // Tambahkan method untuk memastikan CpanelService selalu tersedia
    private function ensureCpanelService($server)
    {
        if (!$this->cpanelService) {
            $this->cpanelService = new CpanelService($server);
        }
        return $this->cpanelService;
    }

    private function validatePassword($password)
    {
        // Minimal 8 karakter
        if (strlen($password) < 8) {
            return false;
        }

        // Harus mengandung huruf besar
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }

        // Harus mengandung huruf kecil
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }

        // Harus mengandung angka
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }

        // Harus mengandung karakter khusus
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            return false;
        }

        return true;
    }
}
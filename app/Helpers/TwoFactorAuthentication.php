<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class TwoFactorAuthentication
{
    protected $settings = [];
    protected $clientmodules = [];
    protected $adminmodules = [];
    protected $adminmodule = "";
    protected $adminsettings = [];
    protected $admininfo = [];
    protected $clientmodule = "";
    protected $clientsettings = [];
    protected $clientinfo = [];
    protected $adminid = "";
    protected $clientid = "";

    public function __construct()
    {
        $this->loadSettings();
    }

    protected function loadSettings()
    {
        $this->settings = (new \App\Helpers\Client)->safe_unserialize(\App\Helpers\Cfg::getValue("2fasettings"));
        if (!isset($this->settings["modules"])) {
            return false;
        }
        foreach ($this->settings["modules"] as $module => $data) {
            if (!empty($data["clientenabled"])) {
                $this->clientmodules[] = $module;
            }
            if (!empty($data["adminenabled"])) {
                $this->adminmodules[] = $module;
            }
        }
        return true;
    }

    public function getModuleSettings($module)
    {
        return is_array($this->settings["modules"][$module]) ? $this->settings["modules"][$module] : [];
    }

    public function getModuleSetting($module, $name)
    {
        $settings = $this->getModuleSettings($module);
        return $settings[$name] ?? null;
    }

    public function setModuleSetting($module, $name, $value)
    {
        $this->settings["modules"][$module][$name] = $value;
        return $this;
    }

    public function isModuleEnabled($module)
    {
        return $this->isModuleEnabledForClients($module) || $this->isModuleEnabledForAdmins($module);
    }

    public function isModuleEnabledForClients($module)
    {
        $settings = $this->getModuleSettings($module);
        return (bool) $settings["clientenabled"];
    }

    public function isModuleEnabledForAdmins($module)
    {
        $settings = $this->getModuleSettings($module);
        return (bool) $settings["adminenabled"];
    }

    public function setModuleClientEnablementStatus($module, $status)
    {
        $this->setModuleSetting($module, "clientenabled", (int) (bool) $status);
        return $this;
    }

    public function setModuleAdminEnablementStatus($module, $status)
    {
        $this->setModuleSetting($module, "adminenabled", (int) (bool) $status);
        return $this;
    }

    public function isForced()
    {
        if ($this->clientid) {
            return $this->isForcedClients();
        }
        if ($this->adminid) {
            return $this->isForcedAdmins();
        }
        return false;
    }

    public function isForcedClients()
    {
        return (bool) $this->settings["forceclient"];
    }

    public function isForcedAdmins()
    {
        return (bool) $this->settings["forceadmin"];
    }

    public function setForcedClients($status)
    {
        $this->settings["forceclient"] = (int) (bool) $status;
        return $this;
    }

    public function setForcedAdmins($status)
    {
        $this->settings["forceadmin"] = (int) (bool) $status;
        return $this;
    }

    public function save()
    {
        \App\Helpers\Cfg::setValue("2fasettings", (new \App\Models\Client)->safe_serialize($this->settings));
        return $this;
    }

    // public function isActiveClients()
    // {
    //     return !empty($this->clientmodules);
    // }

    public function isActiveAdmins()
    {
        return !empty($this->adminmodules);
    }

    public function setClientID($id)
    {
        $this->clientid = $id;
        $this->adminid = "";
        return $this->loadClientSettings();
    }

    public function setAdminID($id)
    {
        $this->clientid = "";
        $this->adminid = $id;
        return $this->loadAdminSettings();
    }

    protected function loadClientSettings()
    {
        $data = \App\Models\Client::where("id", $this->clientid)->where("status", "!=", "Closed")->first();
        if (!$data) {
            return false;
        }
        $data->makeVisible(['authdata']);
        $data = $data->toArray();
        $this->clientmodule = $data["authmodule"];
        $this->clientsettings = (new \App\Helpers\Client)->safe_unserialize($data["authdata"]);
        if (!is_array($this->clientsettings)) {
            $this->clientsettings = [];
        }
        unset($data["authmodule"], $data["authdata"]);
        $data["username"] = $data["email"];
        $this->clientinfo = $data;
        return true;
    }

    protected function loadAdminSettings()
    {
        $data = \App\Models\Admin::where(["id" => $this->adminid, "disabled" => "0"])->first();
        if (!$data) {
            return false;
        }
        $data = $data->toArray();
        $this->adminmodule = $data["authmodule"];
        $this->adminsettings = (new \App\Helpers\Client)->safe_unserialize($data["authdata"]);
        if (!is_array($this->adminsettings)) {
            $this->adminsettings = [];
        }
        unset($data["authmodule"], $data["authdata"]);
        $this->admininfo = $data;
        return true;
    }

    public function getAvailableModules()
    {
        if ($this->clientid) {
            return $this->getAvailableClientModules();
        }
        if ($this->adminid) {
            return $this->getAvailableAdminModules();
        }
        return array_unique(array_merge($this->getAvailableClientModules(), $this->getAvailableAdminModules()));
    }

    protected function getAvailableClientModules()
    {
        return $this->clientmodules;
    }

    protected function getAvailableAdminModules()
    {
        return $this->adminmodules;
    }

    // public function isEnabled()
    // {
    //     if ($this->clientid) {
    //         return $this->isEnabledClient();
    //     }
    //     if ($this->adminid) {
    //         return $this->isEnabledAdmin();
    //     }
    //     return false;
    // }

    public function isEnabled()
{
    try {
        if ($this->clientid) {
            return $this->isEnabledClient();
        }

        if ($this->adminid) {
            return $this->isEnabledAdmin();
        }

        return false;
    } catch (\Exception $e) {
        \Log::error('2FA isEnabled Error:', [
            'message' => $e->getMessage(),
            'user_id' => $this->clientid ?: $this->adminid
        ]);
        return false;
    }
}


    // protected function isEnabledClient()
    // {
    //     return !empty($this->clientmodule);
    // }

    // protected function isEnabledAdmin()
    // {
    //     return !empty($this->adminmodule);
    // }

    // protected function getModule()
    // {
    //     if ($this->clientid) {
    //         return $this->clientmodule;
    //     }
    //     if ($this->adminid) {
    //         return $this->adminmodule;
    //     }
    //     return false;
    // }

    public function moduleCall($function, $module = "", $extraParams = [])
    {
        $mod = new \App\Module\Security();
        $module = $module ?: $this->getModule();
        $loaded = $mod->load($module);
        if (!$loaded) {
            return false;
        }
        $params = $this->buildParams($module);
        $params = array_merge($params, $extraParams);
        return $mod->call($function, $params);
    }

    protected function buildParams($module)
    {
        return [
            "settings" => $this->settings["modules"][$module],
            "user_info" => $this->clientid ? $this->clientinfo : $this->admininfo,
            "user_settings" => $this->clientid ? $this->clientsettings : $this->adminsettings,
            "post_vars" => $_POST,
            "twoFactorAuthentication" => $this
        ];
    }

    public function activateUser($module, $settings = [])
    {
        $encryptionHash = config('portal.hash.cc_encryption_hash');
        if ($this->clientid) {
            $backupCode = sha1($encryptionHash . $this->clientid . time());
            $backupCode = substr($backupCode, 0, 16);
            $settings["backupcode"] = sha1($backupCode);
            \App\Models\Client::where(["id" => $this->clientid])->update(["authmodule" => $module, "authdata" => (new \App\Models\Client)->safe_serialize($settings)]);
            return implode(" ", str_split($backupCode, 4));
        }
        if ($this->adminid) {
            $backupCode = sha1($encryptionHash . $this->adminid . time());
            $backupCode = substr($backupCode, 0, 16);
            $settings["backupcode"] = sha1($backupCode);
            \App\Models\Admin::where(["id" => $this->adminid])->update(["authmodule" => $module, "authdata" => (new \App\Models\Client)->safe_serialize($settings)]);
            return implode(" ", str_split($backupCode, 4));
        }
        return false;
    }

    public function disableUser()
    {
        if ($this->clientid) {
            \App\Models\Client::where(["id" => $this->clientid])->update(["authmodule" => "", "authdata" => ""]);
            return true;
        }
        if ($this->adminid) {
            \App\Models\Admin::where(["id" => $this->adminid])->update(["authmodule" => "", "authdata" => ""]);
            return true;
        }
        return false;
    }

    public function validateAndDisableUser($inputVerifyPassword)
    {
        if (!$this->isEnabled()) {
            throw new \Exception("Not enabled");
        }
        $inputVerifyPassword = \App\Helpers\Sanitize::decode($inputVerifyPassword);
        if ($this->clientid) {
            $databasePassword = \App\Models\Client::where(["id" => $this->clientid])->value('password') ?? "";
            $hasher = new \App\Helpers\Password();
            if (!$hasher->verify($inputVerifyPassword, $databasePassword)) {
                throw new \Exception("Password incorrect. Please try again.");
            }
        } elseif ($this->adminid) {
            $auth = new \App\Helpers\Auth();
            $auth->getInfobyID($this->adminid);
            if (!$auth->comparePassword($inputVerifyPassword)) {
                throw new \Exception("Password incorrect. Please try again.");
            }
        } else {
            throw new \Exception("No user defined");
        }
        $this->disableUser();
        return true;
    }

    public function saveUserSettings($arr)
    {
        if (!is_array($arr)) {
            return false;
        }
        if ($this->clientid) {
            $this->clientsettings = array_merge($this->clientsettings, $arr);
            \App\Models\Client::where(["id" => $this->clientid])->update(["authdata" => (new \App\Models\Client)->safe_serialize($this->clientsettings)]);
            return true;
        }
        if ($this->adminid) {
            $this->adminsettings = array_merge($this->adminsettings, $arr);
            \App\Models\Admin::where(["id" => $this->adminid])->update(["authdata" => (new \App\Models\Client)->safe_serialize($this->adminsettings)]);
            return true;
        }
        return false;
    }

    public function getUserSetting($var)
    {
        if ($this->clientid) {
            return $this->clientsettings[$var] ?? "";
        }
        if ($this->adminid) {
            return $this->adminsettings[$var] ?? "";
        }
        return false;
    }

    public function verifyBackupCode($code)
    {
        $backupCode = $this->getUserSetting("backupcode");
        if (!$backupCode) {
            return false;
        }
        $code = preg_replace("/[^a-z0-9]/", "", strtolower($code));
        return sha1($code) === $backupCode;
    }

    public function generateNewBackupCode()
    {
        $encryptionHash = config('portal.hash.cc_encryption_hash');
        $uid = $this->clientid ?: $this->adminid;
        $backupCode = sha1($encryptionHash . $uid . time() . rand(10000, 99999));
        $backupCode = substr($backupCode, 0, 16);
        $this->saveUserSettings(["backupcode" => sha1($backupCode)]);
        return implode(" ", str_split($backupCode, 4));
    }
    // public function enable()
    // {
    //     try {
    //         if (!$this->clientid && !$this->adminid) {
    //             throw new \Exception("No user defined");
    //         }
    
    //         // Get available modules
    //         $modules = $this->getAvailableModules();
    //         if (empty($modules)) {
    //             throw new \Exception("No 2FA modules available");
    //         }
    
    //         // Use the first available module
    //         $defaultModule = $modules[0];
    
    //         // Generate backup code and activate
    //         $backupCode = $this->activateUser($defaultModule);
    //         if (!$backupCode) {
    //             throw new \Exception("Failed to generate backup code");
    //         }
    
    //         // Log the action
    //         $userId = $this->clientid ?: $this->adminid;
    //         $userType = $this->clientid ? 'Client' : 'Admin';
    //         Log::info("2FA Enabled for {$userType} ID: {$userId}");
    
    //         return [
    //             'success' => true,
    //             'backupCode' => $backupCode,
    //             'module' => $defaultModule
    //         ];
    
    //     } catch (\Exception $e) {
    //         Log::error('2FA Enable Error:', [
    //             'message' => $e->getMessage(),
    //             'user_id' => $this->clientid ?: $this->adminid
    //         ]);
    //         throw $e;
    //     }
    // }

    public function enable($settings = [])
{
    try {
        if (!$this->clientid && !$this->adminid) {
            throw new \Exception("No user defined");
        }

        \Log::debug('2FA Enable Starting', [
            'user_id' => $this->clientid,
            'settings' => $settings
        ]);

        // Prepare settings
        $module = $settings['module'] ?? 'totp';
        $secret = $settings['secret'] ?? null;

        if (!$secret) {
            throw new \Exception('Secret key is required');
        }

        // Generate backup code
        $backupCode = substr(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 16);

        // Prepare auth data as JSON string
        $authData = json_encode([
            'secret' => $secret,
            'backupcode' => sha1($backupCode)
        ]);

        // Save to database
        if ($this->clientid) {
            \App\Models\Client::where('id', $this->clientid)
                ->update([
                    'authmodule' => $module,
                    'authdata' => $authData // Langsung gunakan JSON string
                ]);
        }

        \Log::info('2FA Enabled Successfully', [
            'user_id' => $this->clientid,
            'module' => $module
        ]);

        return [
            'success' => true,
            'backupCode' => $backupCode
        ];

    } catch (\Exception $e) {
        \Log::error('2FA Enable Error:', [
            'message' => $e->getMessage(),
            'user_id' => $this->clientid
        ]);
        throw $e;
    }
}
    
    public function disable()
    {
        try {
            if (!$this->clientid && !$this->adminid) {
                throw new \Exception("No user defined");
            }
    
            // Check if 2FA is enabled
            if (!$this->isEnabled()) {
                throw new \Exception("Two-factor authentication is not enabled");
            }
    
            // Disable 2FA
            $result = $this->disableUser();
            if (!$result) {
                throw new \Exception("Failed to disable two-factor authentication");
            }
    
            // Log the action
            $userId = $this->clientid ?: $this->adminid;
            $userType = $this->clientid ? 'Client' : 'Admin';
            Log::info("2FA Disabled for {$userType} ID: {$userId}");
    
            return true;
    
        } catch (\Exception $e) {
            Log::error('2FA Disable Error:', [
                'message' => $e->getMessage(),
                'user_id' => $this->clientid ?: $this->adminid
            ]);
            throw $e;
        }
    }
    
    public function isActiveClients()
    {
        try {
            // Check if there are any enabled modules for clients
            if (empty($this->clientmodules)) {
                return false;
            }
    
            // Check if forced 2FA is enabled for clients
            if ($this->isForcedClients()) {
                return true;
            }
    
            // Check module settings
            foreach ($this->clientmodules as $module) {
                if ($this->isModuleEnabledForClients($module)) {
                    return true;
                }
            }
    
            return false;
    
        } catch (\Exception $e) {
            Log::error('2FA isActiveClients Error:', [
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }
    
//     protected function isEnabledClient()
// {
//     try {
//         \Log::debug('Checking isEnabledClient:', [
//             'client_id' => $this->clientid,
//             'client_module' => $this->clientmodule,
//             'client_settings' => $this->clientsettings,
//             'auth_data_exists' => !empty($this->clientsettings)
//         ]);

//         // Cek langsung dari database
//         $client = \App\Models\Client::find($this->clientid);
//         if (!$client) {
//             \Log::warning('Client not found:', ['client_id' => $this->clientid]);
//             return false;
//         }

//         $hasAuthModule = !empty($client->authmodule);
//         $hasAuthData = !empty($client->authdata);

//         \Log::debug('Client 2FA status:', [
//             'client_id' => $this->clientid,
//             'auth_module' => $client->authmodule,
//             'has_auth_data' => $hasAuthData
//         ]);

//         // Jika memiliki authmodule dan authdata, maka 2FA aktif
//         return $hasAuthModule && $hasAuthData;

//     } catch (\Exception $e) {
//         \Log::error('isEnabledClient Error:', [
//             'message' => $e->getMessage(),
//             'client_id' => $this->clientid,
//             'trace' => $e->getTraceAsString()
//         ]);
//         return false;
//     }
// }

protected function isEnabledClient()
{
    try {
        // Check if client module is set
        if (empty($this->clientmodule)) {
            return false;
        }

        // Check if module is in available modules
        if (!in_array($this->clientmodule, $this->clientmodules)) {
            return false;
        }

        // Check if module is enabled for clients
        if (!$this->isModuleEnabledForClients($this->clientmodule)) {
            return false;
        }

        // Check client settings or authdata
        if (empty($this->clientsettings) && !$this->hasAuthData()) {
            return false;
        }

        return true;

    } catch (\Exception $e) {
        \Log::error('2FA isEnabledClient Error:', [
            'message' => $e->getMessage(),
            'client_id' => $this->clientid
        ]);
        return false;
    }
}
    
    protected function isEnabledAdmin()
    {
        try {
            // Check if admin module is set
            if (empty($this->adminmodule)) {
                return false;
            }
    
            // Check if module is in available modules
            if (!in_array($this->adminmodule, $this->adminmodules)) {
                return false;
            }
    
            // Check if module is enabled for admins
            if (!$this->isModuleEnabledForAdmins($this->adminmodule)) {
                return false;
            }
    
            // Check admin settings
            if (empty($this->adminsettings)) {
                return false;
            }
    
            return true;
    
        } catch (\Exception $e) {
            Log::error('2FA isEnabledAdmin Error:', [
                'message' => $e->getMessage(),
                'admin_id' => $this->adminid
            ]);
            return false;
        }
    }
    
    // Tambahkan method-method berikut ke class TwoFactorAuthentication
    public function getSecret()
    {
        try {
            if ($this->clientid) {
                return $this->getUserSetting('secret');
            }
            
            if ($this->adminid) {
                return $this->getUserSetting('secret');
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error('2FA getSecret Error:', [
                'message' => $e->getMessage(),
                'user_id' => $this->clientid ?: $this->adminid
            ]);
            return false;
        }
    }
    
    public function verify($code)
    {
        try {
            Log::debug('2FA Verify Starting', [
                'user_id' => $this->clientid ?: $this->adminid,
                'code' => $code,
                'is_enabled' => $this->isEnabled(),
                'client_module' => $this->clientmodule,
                'client_settings' => $this->clientsettings
            ]);
    
            if (!$this->isEnabled()) {
                Log::warning('2FA not enabled for user', [
                    'user_id' => $this->clientid ?: $this->adminid
                ]);
                return false;
            }
    
            // Coba verifikasi dengan backup code terlebih dahulu
            if ($this->verifyBackupCode($code)) {
                Log::info('2FA verified using backup code', [
                    'user_id' => $this->clientid ?: $this->adminid
                ]);
                return true;
            }
    
            // Verifikasi menggunakan module yang aktif
            $module = $this->getModule();
            if (!$module) {
                Log::error('No active 2FA module found', [
                    'user_id' => $this->clientid ?: $this->adminid
                ]);
                return false;
            }
    
            // Bersihkan kode dari spasi atau karakter non-numerik
            $code = preg_replace('/[^0-9]/', '', $code);
    
            // Verifikasi menggunakan module
            $result = $this->moduleCall('verify', $module, [
                'code' => $code,
                'secret' => $this->getUserSetting('secret')
            ]);
    
            Log::info('2FA verification result', [
                'user_id' => $this->clientid ?: $this->adminid,
                'success' => $result,
                'module' => $module
            ]);
    
            return $result;
    
        } catch (\Exception $e) {
            Log::error('2FA Verify Error:', [
                'message' => $e->getMessage(),
                'user_id' => $this->clientid ?: $this->adminid,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
    
    protected function getModule()
    {
        try {
            if ($this->clientid && !empty($this->clientmodule)) {
                return $this->clientmodule;
            }
            
            if ($this->adminid && !empty($this->adminmodule)) {
                return $this->adminmodule;
            }
    
            Log::warning('No 2FA module found', [
                'user_id' => $this->clientid ?: $this->adminid,
                'client_module' => $this->clientmodule,
                'admin_module' => $this->adminmodule
            ]);
            
            return false;
        } catch (\Exception $e) {
            Log::error('2FA getModule Error:', [
                'message' => $e->getMessage(),
                'user_id' => $this->clientid ?: $this->adminid
            ]);
            return false;
        }
    }

    public function getClientID()
{
    return $this->clientid;
}

public function getAdminID()
{
    return $this->adminid;
}

// In app/Helpers/TwoFactorAuthentication.php

public function hasAuthData()
{
    try {
        // Fetch the client from the database
        $client = \App\Models\Client::find($this->clientid);
        if (!$client) {
            \Log::warning('Client not found:', ['client_id' => $this->clientid]);
            return false;
        }

        // Check if authdata exists
        return !empty($client->authdata);

    } catch (\Exception $e) {
        \Log::error('hasAuthData Error:', [
            'message' => $e->getMessage(),
            'client_id' => $this->clientid
        ]);
        return false;
    }
}

}
<?php

namespace Modules\Addons\PrivateNsRegistrar\Helpers;

use Illuminate\Support\Facades\DB;

class PrivateNsRegistrarHelper
{
    public static function getConfig()
    {
        $settings = DB::table('tbladdonmodules')
            ->where('module', 'privatens_registrar')
            ->pluck('value', 'setting')
            ->toArray();

        return [
            'client_id' => $settings['clientid'] ?? null,
            'client_secret' => $settings['secretid'] ?? null,
            'api_url' => $settings['apiurl'] ?? null,
        ];
    }
}
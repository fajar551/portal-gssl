<?php

namespace App\Helpers;

use Illuminate\Support\Facades\App as FacadesApp;
use Illuminate\Support\Facades\Request;
use Symfony\Component\HttpFoundation\Request as Reg;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class HelperApi
{
    public function __construct()
    {
        // Constructor logic if needed
    }

    public function localAPI($command = '', $params = [], $adminusername = "")
    {
        return self::post($command, $params);
    }

    public static function post($command = '', $params = [])
    {
        try {
            request()->merge($params);
            $route = Route::getRoutes()->getByName($command)->action;
            $response = FacadesApp::call($route['controller']);
            return json_decode($response->content(), true);
        } catch (\Exception $e) {
            $response = \App\Helpers\ResponseAPI::Error([
                'message' => $e->getMessage(),
            ]);
            return json_decode($response->content(), true);
        }
    }

    public static function postOLD($api = '', array $param = [])
    {
        $path = str_replace(url('/'), '', route($api));
        $request = Reg::create($path, 'POST', $param, $cookies = [], $files = [], Request::server());
        $res = app()->handle($request);
        $instance = json_decode($res->getContent());
        return $instance;
    }

    public static function get($api = '', array $param = [])
    {
        $path = str_replace(url('/'), '', route($api));
        $request = Reg::create($path, 'GET', $param, $cookies = [], $files = [], Request::server());
        $res = app()->handle($request);
        $instance = json_decode($res->getContent());
        return $instance;
    }
}
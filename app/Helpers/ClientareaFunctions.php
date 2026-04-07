<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ClientareaFunctions
{
    public static function initialiseClientArea($pageTitle, $displayTitle, $tagline, $pageIcon = null, $breadcrumb = null, $smartyValues = [])
    {
        global $_LANG, $smarty, $smartyvalues;

        if ($smartyValues) {
            $smartyvalues = array_merge($smartyvalues, $smartyValues);
        }

        if (defined("PERFORMANCE_DEBUG")) {
            define("PERFORMANCE_STARTTIME", microtime());
        }

        if (is_null($pageIcon) && is_null($breadcrumb)) {
            $pageIcon = $displayTitle;
            $displayTitle = $pageTitle;
            $breadcrumb = $tagline;
            $tagline = "";
        }

        $emptyTemplateParameters = [
            "displayTitle", "tagline", "type", "textcenter", "hide", 
            "additionalClasses", "idname", "errorshtml", "title", 
            "msg", "desc", "errormessage", "livehelpjs"
        ];

        foreach ($emptyTemplateParameters as $templateParam) {
            $smartyvalues[$templateParam] = "";
        }

        $carbonObject = new \App\Helpers\Carbon();
        $smartyvalues = array_merge($smartyvalues, [
            "showbreadcrumb" => false,
            "showingLoginPage" => false,
            "incorrect" => false,
            "kbarticle" => ["title" => ""],
            "language" => "",
            "LANG" => $_LANG,
            "companyname" => \App\Helpers\Cfg::getValue("CompanyName"),
            "logo" => \App\Helpers\Cfg::getValue("LogoURL"),
            "charset" => \App\Helpers\Cfg::getValue("Charset"),
            "pagetitle" => $pageTitle,
            "displayTitle" => $displayTitle,
            "tagline" => $tagline,
            "pageicon" => $pageIcon,
            "filename" => "",
            "breadcrumb" => "",
            "breadcrumbnav" => "",
            "todaysdate" => $carbonObject->format("l, jS F Y"),
            "date_day" => $carbonObject->format("d"),
            "date_month" => $carbonObject->format("m"),
            "date_year" => $carbonObject->format("Y"),
            "token" => csrf_token(),
            "reCaptchaPublicKey" => \App\Helpers\Cfg::getValue("ReCAPTCHAPublicKey"),
            "servedOverSsl" => "",
            "versionHash" => "",
            "systemurl" => config('app.url'),
            "systemsslurl" => config('app.url'),
            "systemNonSSLURL" => config('app.url'),
            "WEB_ROOT" => "",
            "BASE_PATH_CSS" => "",
            "BASE_PATH_JS" => "",
            "BASE_PATH_FONTS" => "",
            "BASE_PATH_IMG" => ""
        ]);

        $currenciesarray = [];
        $result = \App\Models\Currency::orderBy("code", "ASC")->get();
        foreach ($result as $data) {
            $currenciesarray[] = [
                "id" => $data->id,
                "code" => $data->code,
                "default" => $data->default
            ];
        }

        if (count($currenciesarray) == 1) {
            $currenciesarray = [];
        }

        $smartyvalues["currencies"] = $currenciesarray;
    }

    public static function outputClientArea($templatefile, $nowrapper = false, $hookFunctions = [], $smartyValues = [])
    {
        global $CONFIG;
        global $smarty;
        global $smartyvalues;
        global $orderform;
        global $usingsupportmodule;

        if (!empty($smartyValues)) {
            $smartyvalues = array_merge($smartyvalues ?? [], $smartyValues);
        }

        if (!$templatefile) {
            throw new \Exception("Invalid Entity Requested");
        }

        $hookParameters = $smartyvalues;
        $hookFunctions = array_merge(["ClientAreaPage"], $hookFunctions);

        foreach ($hookFunctions as $hookFunction) {
            $hookResponses = \App\Helpers\Hooks::run_hook($hookFunction, $hookParameters);
            foreach ($hookResponses as $hookTemplateVariables) {
                if (is_array($hookTemplateVariables)) {
                    foreach ($hookTemplateVariables as $k => $v) {
                        $hookParameters[$k] = $v;
                        $smartyvalues[$k] = $v;
                    }
                }
            }
        }

        $hookResponses = \App\Helpers\Hooks::run_hook("ClientAreaHeadOutput", $hookParameters);
        $headOutput = "";
        foreach ($hookResponses as $response) {
            if ($response) {
                $headOutput .= $response . "\n";
            }
        }
        $smartyvalues["headoutput"] = $headOutput;

        $hookResponses = \App\Helpers\Hooks::run_hook("ClientAreaHeaderOutput", $hookParameters);
        $headerOutput = "";
        foreach ($hookResponses as $response) {
            if ($response) {
                $headerOutput .= $response . "\n";
            }
        }
        $smartyvalues["headeroutput"] = $headerOutput;

        $hookResponses = \App\Helpers\Hooks::run_hook("ClientAreaFooterOutput", $hookParameters);
        $footerOutput = "";
        foreach ($hookResponses as $response) {
            if ($response) {
                $footerOutput .= $response . "\n";
            }
        }

        if (array_key_exists("credit_card_input", $smartyvalues) && $smartyvalues["credit_card_input"]) {
            $footerOutput .= $smartyvalues["credit_card_input"];
            unset($smartyvalues["credit_card_input"]);
        }

        $smartyvalues["footeroutput"] = $footerOutput;

        return view($templatefile, $smartyvalues);
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;

use App\Models\Client;

use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;

class RegisterController extends Controller
{
    use RegistersUsers;

    protected $redirectTo = RouteServiceProvider::HOME;

    public function __construct()
    {
        $this->middleware('guest');
    }

    protected function validator(array $data)
    {
        $customfields = \App\Helpers\Customfield::getCustomFields("client", "", "", "", "on");
        $messages = [
            'accepttos.required' => 'The Terms of Service is required.',
            'g-recaptcha-response.required' => 'Please complete the captcha verification.',
            'g-recaptcha-response.recaptcha' => 'Captcha verification failed.',
        ];
        $rules = [
            'firstname' => ['nullable', 'string', 'max:255'],
            'lastname' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'password' => ['nullable', 'string', 'min:8'],
            'g-recaptcha-response' => ['required', 'recaptcha'],

            'phonenumber' => ['nullable', 'string'],
            'companyname' => ['nullable', 'string'],
            'address1' => ['nullable', 'string'],
            'address2' => ['nullable', 'string'],
            'city' => ['nullable', 'string'],
            'state' => ['nullable', 'string'],
            'postcode' => ['nullable', 'string'],
            'country' => ['nullable', 'string'],
            'tax_id' => ['nullable', 'string'],
            'currency' => ['nullable', 'string'],
            'securityqid' => ['nullable', 'integer'],
            'securityqans' => ['nullable', 'string'],
            'marketingoptin' => ['nullable'],
            'accepttos' => ['nullable', 'string'],
        ];

         // Custom validator untuk recaptcha
         Validator::extend('recaptcha', function ($attribute, $value, $parameters, $validator) {
            $recaptchaSecretKey = \App\Helpers\Cfg::getValue("ReCAPTCHAPrivateKey");
            $recaptcha = new \ReCaptcha\ReCaptcha($recaptchaSecretKey);
            $response = $recaptcha->verify($value, request()->ip());
            
            return $response->isSuccess();
        });

        foreach ($customfields as $customfield) {
            $id = $customfield['id'];
            $rule = ["nullable"];
            if ($customfield['required']) {
                $rule[] = "required";
            }
            if ($customfield['type'] == 'image') {
                $rule[] = "image";
                $rule[] = "mimes:jpeg,png,jpg";
            } else {
                $rule[] = "string";
            }

            $name = "customfield.$id";
            $rules[$name] = $rule;
            $messages["$name.required"] = 'The ' . $customfield['name'] . ' is required.';
            $messages["$name.mimes"] = 'The ' . $customfield['name'] . ' must be a file of type: :values.';
            $messages["$name.image"] = 'The ' . $customfield['name'] . ' must be an image';
            $messages["$name.string"] = 'The ' . $customfield['name'] . ' must be a string';
        }

        $errormessage = (new \App\Helpers\Client)->checkDetailsareValid("", true);
        if ($errormessage) {
            $rules['message'] = ['required'];
            $messages['message.required'] = $errormessage;
        }

        return Validator::make($data, $rules, $messages);
    }

    protected function create(array $data)
    {
        DB::beginTransaction();
        try {
            // return Client::create([
            //     'uuid' => (string) Str::uuid(),
            //     'firstname' => $data['firstname'],
            //     'lastname' => $data['lastname'],
            //     'email' => $data['email'],
            //     'password' => Hash::make($data['password']),
            //     'currency' => 1,
            // ]);

            $firstname = $data['firstname'] ?? "";
            $lastname = $data['lastname'] ?? "";
            $email = $data['email'] ?? "";
            $password = $data['password'] ?? "";
            $phonenumber = \App\Helpers\Application::formatPostedPhoneNumber();
            $companyname = $data['companyname'] ?? "";
            $address1 = $data['address1'] ?? "";
            $address2 = $data['address2'] ?? "";
            $city = $data['city'] ?? "";
            $state = $data['state'] ?? "";
            $postcode = $data['postcode'] ?? "";
            $country = $data['country'] ?? "";
            $currency = $data['currency'] ?? "";
            $securityqid = $data['securityqid'] ?? "";
            $securityqans = $data['securityqans'] ?? "";
            $marketingoptin = $data['marketingoptin'] ?? "";
            $taxId = $data['tax_id'] ?? "";

            $userid = \App\Helpers\ClientHelper::addClient(
                $firstname,
                $lastname,
                $companyname,
                $email,
                $address1,
                $address2,
                $city,
                $state,
                $postcode,
                $country,
                $phonenumber,
                $password,
                $securityqid,
                $securityqans,
                true,
                ["tax_id" => $taxId],
                "",
                false,
                $marketingoptin
            );

            // Instantiate the Registervanewclient class and call the handle method
            $hookRegisterNewClient = new \App\Hooks\Registervanewclient();
            $request = new \Illuminate\Http\Request(['userid' => $userid]);
            $result = $hookRegisterNewClient->handle($request);

            if ($result) {
                \App\Helpers\Hooks::run_hook("ClientAreaRegister", ["userid" => $userid]);
            }

            DB::commit();
            return Client::find($userid);
        } catch (\Exception $e) {
            DB::rollback();
            // Consider logging the exception or returning an error response
        }
    }

    // public function showRegistrationForm(Request $request)
    // {
    //     $data = [
    //         'firstname' => old("firstname"),
    //         'lastname' => old("lastname"),
    //         'companyname' => old("companyname"),
    //         'email' => old("email"),
    //         'address1' => old("address1"),
    //         'address2' => old("address2"),
    //         'city' => old("city"),
    //         'state' => old("state"),
    //         'postcode' => old("postcode"),
    //         'country' => old("country"),
    //         'phonenumber' => old("phonenumber"),
    //         'password' => old("password"),
    //         'securityqid' => old("securityqid"),
    //         'securityqans' => old("securityqans"),
    //         'customfield' => old("customfield"),
    //         'marketingoptin' => old("marketingoptin"),
    //         'registrationDisabled' => !(bool) \App\Helpers\Cfg::getValue("AllowClientRegister"),
    //         'noregistration' => !\App\Helpers\Cfg::getValue("AllowClientRegister"),
    //         'customfields' => \App\Helpers\Customfield::getCustomFields("client", "", "", "", "on", old("customfield")),
    //         'accepttos' => \App\Helpers\Cfg::getValue("EnableTOSAccept"),
            
    //         'recaptchaSiteKey' => \App\Helpers\Cfg::getValue("ReCAPTCHAPublicKey"),
    //         'recaptchaSecretKey' => \App\Helpers\Cfg::getValue("ReCAPTCHAPrivateKey"),

    //         'tosurl' => \App\Helpers\Cfg::getValue("TermsOfService"),
    //         'uneditablefields' => explode(",", (string) \App\Helpers\Cfg::getValue("ClientsProfileUneditableFields")),
    //         'optionalFields' => explode(",", (string) \App\Helpers\Cfg::getValue("ClientsProfileOptionalFields")),
    //         'phoneNumberInputStyle' => (int) \App\Helpers\Cfg::getValue("PhoneNumberDropdown"),
    //         'showMarketingEmailOptIn' => \App\Helpers\Cfg::getValue("AllowClientsEmailOptOut"),
    //         'clientcountries' => (new \App\Helpers\Country())->getCountryNameArray(),
    //         'clientcountry' => old("country"),
    //         'defaultCountry' => \App\Helpers\Cfg::getValue("DefaultCountry"),
    //         'showTaxIdField' => \App\Helpers\Vat::isUsingNativeField(),
    //         'currencies' => [],
    //         'securityquestions' => (new \App\Helpers\Client())->getSecurityQuestions(),
    //         'marketingEmailOptInMessage' => Lang::get("client.emailMarketing.optInMessage") != "client.emailMarketing.optInMessage" ? Lang::trans("client.emailMarketing.optInMessage") : \App\Helpers\Cfg::getValue("EmailMarketingOptInMessage"),
    //         'marketingEmailOptIn' => $request->has("marketingoptin") ? (bool) $request->get("marketingoptin") : !(bool) \App\Helpers\Cfg::getValue("EmailMarketingRequireOptIn"),
    //     ];

    //     return view('auth.register', $data);
    // }

    public function showRegistrationForm(Request $request)
    {
        $firstname = old("firstname");
        $lastname = old("lastname");
        $companyname = old("companyname");
        $email = old("email");
        $address1 = old("address1");
        $address2 = old("address2");
        $city = old("city");
        $state = old("state");
        $postcode = old("postcode");
        $country = old("country");
        $phonenumber = old("phonenumber");
        $password = old("password");
        $securityqid = old("securityqid");
        $securityqans = old("securityqans");
        $customfield = old("customfield");
        $marketingoptin = old("marketingoptin");

        $data = [];
        $securityquestions = (new \App\Helpers\Client)->getSecurityQuestions();
        $data["registrationDisabled"] = (bool) (!\App\Helpers\Cfg::getValue("AllowClientRegister"));
        $data["noregistration"] = !\App\Helpers\Cfg::getValue("AllowClientRegister") ? true : false;
        $countries = new \App\Helpers\Country();
        $countriesdropdown = \App\Helpers\ClientHelper::getCountriesDropDown($country);
        $customfields = \App\Helpers\Customfield::getCustomFields("client", "", "", "", "on", $customfield);
        $data["customfields"] = $customfields;

        // Ambil ReCaptcha secret key dari konfigurasi
        $recaptchaSecretKey = \App\Helpers\Cfg::getValue("ReCAPTCHAPrivateKey");
        $recaptchaSiteKey = \App\Helpers\Cfg::getValue("ReCAPTCHAPublicKey"); 
        $data["recaptchaSiteKey"] = \App\Helpers\Cfg::getValue("ReCAPTCHAPublicKey");

        $data["accepttos"] = \App\Helpers\Cfg::getValue("EnableTOSAccept");
        $data["tosurl"] = \App\Helpers\Cfg::getValue("TermsOfService");
        $data["uneditablefields"] = explode(",", \App\Helpers\Cfg::getValue("ClientsProfileUneditableFields"));
        $optionalFields = \App\Helpers\Cfg::getValue("ClientsProfileOptionalFields");
        $data["optionalFields"] = explode(",", $optionalFields);
        $data["phoneNumberInputStyle"] = (int) \App\Helpers\Cfg::getValue("PhoneNumberDropdown");
        $data["showMarketingEmailOptIn"] = \App\Helpers\Cfg::getValue("AllowClientsEmailOptOut");
        $data["clientcountries"] = $countries->getCountryNameArray();
        $data["clientcountry"] = $country;
        $data["defaultCountry"] = \App\Helpers\Cfg::getValue("DefaultCountry");
        $data["showTaxIdField"] = \App\Helpers\Vat::isUsingNativeField();
        $data["currencies"] = [];
        $data["securityquestions"] = $securityquestions;
        $data["showMarketingEmailOptIn"] = \App\Helpers\Cfg::getValue("AllowClientsEmailOptOut");
        $data["marketingEmailOptInMessage"] = Lang::get("client.emailMarketing.optInMessage") != "client.emailMarketing.optInMessage" ? Lang::trans("client.emailMarketing.optInMessage") : \App\Helpers\Cfg::getValue("EmailMarketingOptInMessage");
        $data["marketingEmailOptIn"] = $request->has("marketingoptin") ? (bool) $request->get("marketingoptin") : (bool) (!\App\Helpers\Cfg::getValue("EmailMarketingRequireOptIn"));

        return view('auth.register', $data);
    }
}
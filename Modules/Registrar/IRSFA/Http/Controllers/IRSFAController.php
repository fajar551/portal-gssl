<?php

namespace Modules\Registrar\IRSFA\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Routing\Controller;

class IRSFAController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */


    protected $url = "http://api6.irsfa.id";

    // $oauth2 = [
    //     "grant_type" => "client_credentials",
    //     "client_id" => 'ecc1d21e-f873-4a86-ac37-982adc0fc239',
    //     "client_secret" => 'DvMjDv0EaYbBZbfKgJma4u7EN6DL51Dzjy9J46Lh',
    //     "scope" => "",
    //   ];

    public function message($code, $msg)
    {
        return [
            "code" => $code,
            "message" => $msg
        ];
    }

    public function messageWithData($code, $msg, $data)
    {
        return [
            "code" => $code,
            "message" => $msg,
            "data" => $data
        ];
    }

    public function authentication($data)
    {
        $response = Http::asForm()->post($this->url . '/oauth/token', $data);

        return $response->json();
    }

    public function request($url, $method, $oauth2, $datas)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $oauth2,
            'Content-Type' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ])->$method($url, $datas);

        return $response->json();
    }

    public function MetaData(array $params)
    {
        return [
            'DisplayName' => 'Aksaradata',
            'APIVersion' => '2.0',
        ];
    }

    public function getConfigArray(array $params)
    {
        $configArray = array(
            "clientid" => array(
                "FriendlyName" => "Client Id",
                "Type"         => "text", # Text Box
                "Size"         => "255", # Defines the Field Width
                "Description"  => "Client Id API Aksara registrar",
                "Default"      => "",
                "Placeholder"  => "Client Id"
            ),
            "secretid" => array(
                "FriendlyName" => "Secret Id",
                "Type"         => "text", # Text Box
                "Size"         => "255", # Defines the Field Width
                "Description"  => "Secret Id API Aksara registrar",
                "Default"      => "",
                "Placeholder"  => "Secret Id"
            )
        );
        return $configArray;
    }

    public function RegisterDomain(array $params)
    {
        // Setup Oauth2 credentials
        $oauth2 = [
            "grant_type" => "client_credentials",
            "client_id" => 'ecc1d21e-f873-4a86-ac37-982adc0fc239',
            "client_secret" => 'DvMjDv0EaYbBZbfKgJma4u7EN6DL51Dzjy9J46Lh',
            "scope" => "",
        ];

        // Setup the domain registration data
        $data = [
            "domain_name"      => $params['sld'],
            "domain_extension" => $params['tld'],
            "period"      => $params['regperiod'],
            "nameserver"  => [$params['ns1'], $params['ns2'], $params['ns3'], $params['ns4'], $params['ns5']],
            "description" => "WHMCS Register Domain [New Module]",
            "domain_name" => $params['sld'],
            "domain_extension" => $params['tld'],
        ];

        // Registrant Contact Info
        $registrant = [
            'company_name'     => $params['companyname'],
            'initial'          => substr($params['firstname'], 0, 1) . substr($params['lastname'], 0, 1),
            'first_name'       => $params['firstname'],
            'last_name'        => $params['lastname'],
            'gender'           => 'M',
            'street'           => $params['address1'],
            'street2'          => $params['address2'],
            'number'           => 13,
            'city'             => $params['city'],
            'state'            => $params['state'],
            'zip_code'         => $params['postcode'],
            'country'          => $params['country'],
            'email'            => $params['email'],
            'telephone_number' => str_replace('.', '', $params['fullphonenumber']),
            'locale'           => 'en_GB'
        ];

        // Remove empty nameservers
        if (empty($data['nameserver'][0])) {
            unset($data['nameserver']);
        } else {
            $data['nameserver'] = array_filter($data['nameserver']);
        }

        // Merge the domain data and registrant data
        $datas = array_merge($data, $registrant);

        try {
            // Get Access Token
            $auth = $this->authentication($oauth2);

            // Send the request to register domain
            $response = $this->request($this->url . "/api/rest/v3/domain/register", "POST", $auth['access_token'], $datas);

            if ($response['code'] !== 200) {
                return response()->json(['error' => $response['message']], $response['code']);
            }

            return response()->json([
                'code' => $response['code'],
                'message' => $response['message']
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function RenewDomain(array $params)
    {
        // Prepare OAuth2 credentials
        $oauth2 = [
            "grant_type" => "client_credentials",
            "client_id" => 'ecc1d21e-f873-4a86-ac37-982adc0fc239',
            "client_secret" => 'DvMjDv0EaYbBZbfKgJma4u7EN6DL51Dzjy9J46Lh',
            "scope" => "",
        ];

        // Prepare data for domain renewal
        $datas = [
            "domain" => $params['sld'] . "." . $params['tld'],
            "period" => 1,
            "description" => "[WHMCS] Renew Domain [New Module]",
            "domain_name" => $params['sld'],
            "domain_extension" => $params['tld'],
        ];

        try {
            // Authenticate and get access token
            $auth = $this->authentication($oauth2);

            // Send the request to renew the domain
            $response = $this->request($this->url . "/api/rest/v3/domain/renew", "POST", $auth['access_token'], $datas);

            if ($response['code'] !== 200) {
                return response()->json(['error' => $response['message']], $response['code']);
            }

            // Return the message from the API response
            return response()->json(['message' => $response['message']]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function TransferDomain(Request $request)
    {
        // Setup Oauth2 credentials
        $oauth2 = [
            "grant_type" => "client_credentials",
            "client_id" => $request->input('clientid'),
            "client_secret" => $request->input('secretid'),
            "scope" => "",
        ];

        // Setup the domain registration data
        $data = [
            "domain" => $request->input('sld') . "." . $request->input('tld'),
            "auth_code" => $request->input('eppcode'),
            "period" => $request->input('regperiod'),
            "nameserver" => [
                $request->input('ns1'),
                $request->input('ns2'),
                $request->input('ns3'),
                $request->input('ns4'),
                $request->input('ns5')
            ],
            "description" => "[WHMCS] Transfer Domain [New Module]",
            "epp" => $request->input('eppcode'),
            "domain_name" => $request->input('sld'),
            "domain_extension" => $request->input('tld'),
        ];

        // Registrant Contact Info
        $registrant = [
            'company_name' => $request->input('companyname'),
            'initial' => substr($request->input('firstname'), 0, 1) . substr($request->input('lastname'), 0, 1),
            'first_name' => $request->input('firstname'),
            'last_name' => $request->input('lastname'),
            'gender' => 'M',
            'street' => $request->input('address1'),
            'street2' => $request->input('address2'),
            'number' => 13, // Fixed number or adapt based on logic
            'city' => $request->input('city'),
            'state' => $request->input('state'),
            'zip_code' => $request->input('postcode'),
            'country' => $request->input('country'),
            'email' => $request->input('email'),
            'telephone_number' => str_replace('.', '', $request->input('fullphonenumber')),
            'locale' => 'en_GB'
        ];

        // Remove empty nameservers
        if (empty($data['nameserver'][0])) {
            unset($data['nameserver']);
        } else {
            $data['nameserver'] = array_filter($data['nameserver']);
        }

        // Merge the domain data and registrant data
        $datas = array_merge($data, $registrant);

        try {
            // Get Access Token
            $auth = $this->authentication($oauth2);

            // Send the request to transfer domain
            $response = $this->request($this->url . "/api/rest/v3/domain/transfer", "POST", $auth['access_token'], $datas);

            if ($response['code'] !== 200) {
                return response()->json(['error' => $response['message']], $response['code']);
            }

            return response()->json([
                'code' => $response['code'],
                'message' => $response['message']
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function GetContactDetails(array $params)
    {
        // Prepare OAuth2 credentials
        $oauth2 = [
            "grant_type" => "client_credentials",
            "client_id" => 'ecc1d21e-f873-4a86-ac37-982adc0fc239',
            "client_secret" => 'DvMjDv0EaYbBZbfKgJma4u7EN6DL51Dzjy9J46Lh',
            "scope" => "",
        ];

        // Prepare data for domain contact lookup
        $datas = [
            "domain" => $params['sld'] . "." . $params['tld'],
            "domain_name"      => $params['sld'],
            "domain_extension" => $params['tld'],
        ];

        try {
            // Authenticate and get access token
            $auth = $this->authentication($oauth2);

            // Send the request to get contact details
            $response = $this->request($this->url . "/api/rest/v3/domain/contact/getbydomain", "POST", $auth['access_token'], $datas);

            if ($response['code'] !== 200) {
                return response()->json(['error' => $response['message']], $response['code']);
            }

            // Return formatted contact details
            return response()->json([
                'Registrant' => [
                    'Contact ID' => $response['data']['reg_contact']['client_contact_id'],
                    'First Name' => $response['data']['reg_contact']['contact_first_name'],
                    'Last Name' => $response['data']['reg_contact']['contact_last_name'],
                    'Gender' => $response['data']['reg_contact']['contact_gender'],
                    'Company Name' => $response['data']['reg_contact']['contact_company_name'],
                    'Email Address' => $response['data']['reg_contact']['contact_email'],
                    'Address 1' => $response['data']['reg_contact']['contact_street'],
                    'Address 2' => '',
                    'Adreess Number' => $response['data']['reg_contact']['contact_number'],
                    'City' => $response['data']['reg_contact']['contact_city'],
                    'State' => $response['data']['reg_contact']['contact_state'],
                    'Postcode' => $response['data']['reg_contact']['contact_zip_code'],
                    'Country' => $response['data']['reg_contact']['contact_country'],
                    'Phone Number' => $response['data']['reg_contact']['contact_phone'],
                    'Fax Number' => '',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function GetDomainDetails(array $params)
    {
        // Prepare OAuth2 credentials
        $oauth2 = [
            "grant_type" => "client_credentials",
            "client_id" => 'ecc1d21e-f873-4a86-ac37-982adc0fc239',
            "client_secret" => 'DvMjDv0EaYbBZbfKgJma4u7EN6DL51Dzjy9J46Lh',
            "scope" => "",
        ];

        // Prepare data for domain contact lookup
        $datas = [
            "domain_name"      => $params['sld'],
            "domain_extension" => $params['tld'],
        ];

        try {
            // Authenticate and get access token
            $auth = $this->authentication($oauth2);

            // Send the request to get contact details
            $response = $this->request($this->url . "/api/rest/v3/domain/info", "POST", $auth['access_token'], $datas);

            if ($response['code'] !== 200) {
                return response()->json(['error' => $response['message']], $response['code']);
            }

            // Return domain detail
            return response()->json(
                [   
                    'code' => $response['code'],
                    'message' => $response['message'],
                    'data' => $response['data']
                ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function GetEPPCode(array $params)
    {
        // Prepare OAuth2 credentials
        $oauth2 = [
            "grant_type" => "client_credentials",
            "client_id" => 'ecc1d21e-f873-4a86-ac37-982adc0fc239',
            "client_secret" => 'DvMjDv0EaYbBZbfKgJma4u7EN6DL51Dzjy9J46Lh',
            "scope" => "",
        ];

        // Prepare data to request EPP code
        $datas = [
            "domain_name"      => $params['sld'],
            "domain_extension" => $params['tld'],
        ];

        try {
            // Authenticate and get access token
            $auth = $this->authentication($oauth2);

            // Send the request to get EPP code
            $response = $this->request($this->url . "/api/rest/v3/domain/eppcode", "POST", $auth['access_token'], $datas);

            if ($response['code'] !== 200) {
                return response()->json(['error' => $response['message']], $response['code']);
            }

            // Return the EPP code from the API response
            return response()->json(
                [   
                    'code' => $response['code'],
                    'message' => $response['message'],
                    'data' => $response['data']
                ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function UpdateContactDetails(array $params)
    {
        // Prepare OAuth2 credentials
        $oauth2 = [
            "grant_type" => "client_credentials",
            "client_id" => 'ecc1d21e-f873-4a86-ac37-982adc0fc239',
            "client_secret" => 'DvMjDv0EaYbBZbfKgJma4u7EN6DL51Dzjy9J46Lh',
            "scope" => "",
        ];

        // Prepare contact data
        $datas = [
            'contact_id' => $params['contact_id'],
            'telephone_number' => $params['phone_number'],
            'street' => $params['address_1'],
            'number' => $params['address_number'],
            'zip_code' => $params['postcode'],
            'city' => $params['city'],
            'state' => $params['state'],
            'country' => $params['country'],
            'email' => $params['email_address'],
            'company_name' => $params['company_name'],
            'first_name' => $params['first_name'],
            'last_name' => $params['last_name'],
            'locale' => 'ID',
        ];

        try {
            // Authenticate and get access token
            $auth = $this->authentication($oauth2);

            // Send request to update contact details
            $updateResponse = $this->request($this->url . "/api/rest/v3/domain/contact/update", "PUT", $auth['access_token'], $datas);

            if ($updateResponse['code'] !== 200) {
                return response()->json(['error' => $updateResponse['message']], $updateResponse['code']);
            }

            //Response
            return response()->json(['code' =>  $updateResponse['code'], 'message' => $updateResponse['message']]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function GetRegistrarLock($params) {
        $oauth2 = [
            "grant_type" => "client_credentials",
            "client_id" => 'ecc1d21e-f873-4a86-ac37-982adc0fc239',
            "client_secret" => 'DvMjDv0EaYbBZbfKgJma4u7EN6DL51Dzjy9J46Lh',
            "scope" => "",
        ];

        // Prepare data to request EPP code
        $datas = [
            "domain_name"      => $params['sld'],
            "domain_extension" => $params['tld'],
            "status" => 1,
        ];

        try {
            // Authenticate and get access token
            $auth = $this->authentication($oauth2);

            // Send the request to get EPP code
            $response = $this->request($this->url . "/api/rest/v3/domain/lock", "POST", $auth['access_token'], $datas);

            if ($response['code'] !== 200) {
                return response()->json(['error' => $response['message']], $response['code']);
            }

            // Return the EPP code from the API response
            return response()->json(
                [   
                    'code' => $response['code'],
                    'message' => $response['message'],
                    'data' => $response['data']
                ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function GetRegistrarUnlock($params) {
        $oauth2 = [
            "grant_type" => "client_credentials",
            "client_id" => 'ecc1d21e-f873-4a86-ac37-982adc0fc239',
            "client_secret" => 'DvMjDv0EaYbBZbfKgJma4u7EN6DL51Dzjy9J46Lh',
            "scope" => "",
        ];

        // Prepare data to request EPP code
        $datas = [
            "domain_name"      => $params['sld'],
            "domain_extension" => $params['tld'],
            "status" => 0,
        ];

        try {
            // Authenticate and get access token
            $auth = $this->authentication($oauth2);

            // Send the request to get EPP code
            $response = $this->request($this->url . "/api/rest/v3/domain/lock", "POST", $auth['access_token'], $datas);

            if ($response['code'] !== 200) {
                return response()->json(['error' => $response['message']], $response['code']);
            }

            // Return the EPP code from the API response
            return response()->json(
                [   
                    'code' => $response['code'],
                    'message' => $response['message'],
                    'data' => $response['data']
                ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function SaveNameservers(array $params) {
        $oauth2 = [
            "grant_type" => "client_credentials",
            "client_id" => 'ecc1d21e-f873-4a86-ac37-982adc0fc239',
            "client_secret" => 'DvMjDv0EaYbBZbfKgJma4u7EN6DL51Dzjy9J46Lh',
            "scope" => "",
        ];
    
        $nameserver = [$params['ns1'], $params['ns2'], $params['ns3'], $params['ns4'], $params['ns5']];
        $filtered = array_filter($nameserver);
    
        $datas = [
            "domain"     => $params['sld'].".".$params['tld'], 
            "nameserver" => $filtered,
            "domain_name" => $params['sld'],
            "domain_extension" => $params['tld'],
        ];
    
        try {
            // Authenticate and get access token
            $auth = $this->authentication($oauth2);

            // Send the request to get Update Nameserver
            $response = $this->request($this->url . "/api/rest/v3/domain/modify/ns", "PUT", $auth['access_token'], $datas);

            if ($response['code'] !== 200) {
                return response()->json(['error' => $response['message']], $response['code']);
            }

            // Return the Nameserver data from the API response
            return response()->json(
                [   
                    'code' => $response['code'],
                    'message' => $response['message'],
                    'data' => $response['data']
                ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function GetHostNameservers (array $params) {
        $oauth2 = [
            "grant_type" => "client_credentials",
            "client_id" => 'ecc1d21e-f873-4a86-ac37-982adc0fc239',
            "client_secret" => 'DvMjDv0EaYbBZbfKgJma4u7EN6DL51Dzjy9J46Lh',
            "scope" => "",
        ];

        $datas = [
            "domain" => $params['domain'],
        ];

        try {
            $auth = $this->authentication($oauth2);

            $response = $this->request($this->url . "/api/rest/v3/domain/nameserver/list", "POST", $auth['access_token'], $datas);

            if ($response['code'] !== 200) {
                return response()->json(['error' => $response['message']], $response['code']);
            }

            return response()->json(
                [   
                    'code' => $response['code'],
                    'message' => $response['message'],
                    'data' => $response['data']
                ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
            return array(
                'error' => $e->getMessage(),
            );
        }
    }

    public function SaveHostNameservers (array $params) {
        $oauth2 = [
            "grant_type" => "client_credentials",
            "client_id" => 'ecc1d21e-f873-4a86-ac37-982adc0fc239',
            "client_secret" => 'DvMjDv0EaYbBZbfKgJma4u7EN6DL51Dzjy9J46Lh',
            "scope" => "",
        ];

        $datas = [
            "host" => $params['host'].".".$params['domain'], 
            "ipv4" => $params['ip_address'],
            "ipv6" => "",
        ];
        
        try {
            $auth = $this->authentication($oauth2);

            $response = $this->request($this->url . "/api/rest/v3/domain/nameserver/create", "POST", $auth['access_token'], $datas);

            if ($response['code'] !== 200) {
                return response()->json(['error' => $response['message']], $response['code']);
            }

            return response()->json(
                [   
                    'code' => $response['code'],
                    'message' => $response['message'],
                    'data' => $response['data']
                ]);
                
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }


    }

    public function UpdateHost (array $params) {
        $oauth2 = [
            "grant_type" => "client_credentials",
            "client_id" => 'ecc1d21e-f873-4a86-ac37-982adc0fc239',
            "client_secret" => 'DvMjDv0EaYbBZbfKgJma4u7EN6DL51Dzjy9J46Lh',
            "scope" => "",
        ];

        $datas = [
            "domain"     => $params['sld'].".".$params['tld'], 
            "domain_name" => $params['sld'],
            "domain_extension" => $params['tld'],
        ];

        try {
            $auth = $this->authentication($oauth2);

            $response = $this->request($this->url . "/api/rest/v3/domain/modify/ns", "POST", $auth['access_token'], $datas);

            if ($response['code'] !== 200) {
                return response()->json(['error' => $response['message']], $response['code']);
            }

            return response()->json(
                [   
                    'data' => $response
                ]);
                
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
            return array(
                'error' => $e->getMessage(),
            );
        }


    }

    public function DeleteHostNameservers (array $params) {
        $oauth2 = [
            "grant_type" => "client_credentials",
            "client_id" => 'ecc1d21e-f873-4a86-ac37-982adc0fc239',
            "client_secret" => 'DvMjDv0EaYbBZbfKgJma4u7EN6DL51Dzjy9J46Lh',
            "scope" => "",
        ];

        $datas = [
            "nameserver_id" => $params['nsid'], 
            "host" => $params['host'].".".$params['domain'], 
        ];

        try {
            $auth = $this->authentication($oauth2);

            $response = $this->request($this->url . "/api/rest/v3/domain/nameserver/delete", "DELETE", $auth['access_token'], $datas);

            if ($response['code'] !== 200) {
                return response()->json(['error' => $response['message']], $response['code']);
            }

            return response()->json(
                [   
                    'code' => $response['code'],
                    'message' => $response['message'],
                    'data' => $response['data']
                ]);
                
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

    }
    
}

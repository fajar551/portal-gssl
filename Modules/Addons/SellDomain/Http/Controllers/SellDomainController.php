<?php

namespace Modules\Addons\SellDomain\Http\Controllers;

use App\Http\Controllers\Client\_SellDomainController;
use App\Http\Controllers\Client\_SellDomainLeaseController;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SellDomainController extends Controller
{

    public function config()
    {
        return [
            'name' => 'Sell Domain/ Rent Domain',
            'description' => 'Module ini digunakan untuk kelola jual dan sewa domain',
            'author' => 'CBMS Developer - Rafly',
            'language' => 'english',
            'version' => '1.0',
            'fields' => [],
        ];
    }

    public function activate()
    {
        try {
            return [
                'status' => 'success',
                'description' => 'Module enabled',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'description' => 'Unable to create your module: ' . $e->getMessage(),
            ];
        }
    }

    public function deactivate()
    {
        try {
            return [
                'status' => 'success',
                'description' => 'Module has been disabled and all related tables have been dropped.',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'description' => "Unable to drop your module: {$e->getMessage()}",
            ];
        }
    }

    public function output()
    {
        $sellDomain = new _SellDomainController();
        $auth = Auth::guard('admin')->user();
        $clientid = $auth->id;

        $sellDomain = new _SellDomainController();
        $rentDomain = new _SellDomainLeaseController();

        $page = request()->get('page');
        $domain = request()->get('domain');
        $isFilter = request()->get('isFilter', 'false') === 'true';
        $isRent = request()->get('isRent', 'false') === 'true';
        $criteria = [
            "domain" => request()->get("domain", ""),
            "status" => request()->get("status", ""),
            "type" => request()->get("type", ""),
            "open_lelang" => request()->get("open_lelang", []),
            "close_lelang" => request()->get("close_lelang", []),
        ];

        // Check if any filter criteria are provided
        $hasCriteria = !empty($criteria['domain']) ||
                       !empty($criteria['status']) ||
                       !empty($criteria['type']) ||
                       (!empty($criteria['open_lelang']) && !empty($criteria['open_lelang'][0])) ||
                       (!empty($criteria['open_lelang']) && !empty($criteria['open_lelang'][1])) ||
                       (!empty($criteria['close_lelang']) && !empty($criteria['close_lelang'][0])) ||
                       (!empty($criteria['close_lelang']) && !empty($criteria['close_lelang'][1]));
        
        if ($isFilter && $hasCriteria) {
            $filter = $sellDomain->filterDomain($criteria);
            return response()->json($filter);
        } else if ($isFilter && !$hasCriteria) {
            $allDomains = $sellDomain->getDomainAllAdmin();
            return response()->json($allDomains);
        } 

        if ($isRent) {
            $criteria_rent = [
                'domain' => request()->get('domain', ''),
                'status' => request()->get('status', ''),
                'price' => request()->get('price', ''),
                'epp' => request()->get('epp', ''),
            ];
            $domain_rent = $rentDomain->filterDomainRent($criteria_rent);
            return response()->json($domain_rent);
        } else {
            $domain_rent = $rentDomain->getRentDomainAll($domain);
        }

        if (!request()->ajax()) {
            $clientarea_page['vars'] = [
                'domains' => $sellDomain->getDomainAllAdmin(),
                'domain_rents' => $rentDomain->getRentDomainAll($domain),
            ];

            if (empty($page) && empty($domain)) {
                return view('selldomain::index', $clientarea_page['vars']);
            }

            switch ($page) {
                case 'park_domain':
                    return view('selldomain::index', $clientarea_page['vars']);
                case 'rent':
                    $clientarea_page['vars']['domain_rent'] = $domain_rent;
                    return view('selldomain::rent', $clientarea_page['vars']);
            }
        }

        // Default JSON response for AJAX requests
        return response()->json(['message' => 'No data found'], 404);
    }

    public function action(Request $request)
    {
        $sellDomain = new _SellDomainController();
        $auth = Auth::guard('admin')->user();
        $clientid = $auth->id;

        $sellDomain = new _SellDomainController();
        $rentDomain = new _SellDomainLeaseController();

        $action = $request->get('action');
        $domain = $request->get('domain');

        switch ($action) {
            case 'proses_transfer':
                $clientid = $request->get('clientid');
                // $clientid = Auth::guard('admin')->user()->id;
                // $clientid = 33875;
                $subject = 'Sell Domain PROCESS_TRANSFER';
                $message = "<p>Proses transfer domain selesai, dengan nama domain: $domain</p>";
                $sellDomain->notifGeneral(
                    $clientid,
                    $subject,
                    $message
                );

                // $client = DB::table('clients')->where('id', $clientid)->first();
                // $email = $client->email;
                // $name = $client->name;

                try {
                    $getdata = DB::table('sell_domain')->where('domain', $domain)->first();
                } catch (\Exception $e) {
                    return redirect()->back()->with([
                        'alert-message' => 'Gagal Proses Transfer Domain' . $e->getMessage(),
                        'alert-type' => 'danger'
                    ]);
                }

                $subject = 'Sell Domain PROCESS_TRANSFERED';
                $message = "Proses transfer domain selesai, dengan nama domain: $domain";
                $sellDomain->openTicket(
                    $getdata->clientid,
                    $subject,
                    $message,
                    ""
                );

                try {
                    DB::table('sell_domain')->where('domain', $domain)->update(['status'=>'PROCESS_TRANSFER']);
                } catch (\Exception $e) {
                    return redirect()->back()->with([
                        'alert-message' => 'Gagal Proses Transfer Domain' . $e->getMessage(),
                        'alert-type' => 'danger'
                    ]);
                }

                return redirect()->back()->with([
                    'alert-message' => 'Berhasil Proses Transfer Domain',
                    'alert-type' => 'success'
                ]);
            break;

            case 'proses_dicairkan':
                $clientid = $request->get('clientid');
                // $clientid = Auth::guard('admin')->user()->id;
                try {
                    DB::table('sell_domain')->where('domain', $domain)->update(['status'=>'SETTLED']);
                } catch (\Exception $e) {
                    return redirect()->back()->with([
                        'alert-message' => 'Gagal Proses Dicairkan' . $e->getMessage(),
                        'alert-type' => 'danger'
                    ]);
                }

                $bank = $sellDomain->getBankClient($clientid);

                if($bank == null) {
                    return redirect()->back()->with([
                        'alert-message' => 'Bank tidak ditemukan',
                        'alert-type' => 'danger'
                    ]);
                }

                $invoice = DB::table('sell_domain')->where('domain', $domain)->first()->invoiceid;

                if (boolval($invoice)){
                    $price = $sellDomain->calculateDanaDicairkan($invoice, $clientid);
                    $adminprice = $price['adminprice'];
                    $feebank = $price['feebank'];
                    $withdraw = $price['withdraw'];

                    $sellDomain->notifGeneral($clientid, 'Pencairan Dana', "<p>
                    Selamat, Penjualan untuk domain $domain sebesar $withdraw sudah kami cairkan ke rekening $bank->rekening sesuai pengaturan bank di akun jual domain milik anda.</p>");

                    $src = DB::table('sell_domain_invoices')->where('domain', $domain)->first()->bukti_settled;
                    if (boolval($src)){
                        $subject = "Sell Domain - Pencairan Dana";
                        $message = "Selamat, Penjualan untuk domain $domain sebesar $withdraw sudah kami cairkan ke rekening $bank->rekening sesuai pengaturan bank di akun jual domain milik anda.
                        Bukti transfer : $src";
                        $sellDomain->openTicket(
                            $clientid,
                            $subject,
                            $message,
                            ""
                        );
                    }
                }

                return redirect()->back()->with([
                    'alert-message' => 'Berhasil Pencairan Dana',
                    'alert-type' => 'success'
                ]);
            break;

            case 'update_invoice_paid':
                $domain = $request->input('domain');
                $invoiceid = $request->input('invoiceid');

                if($invoiceid == null || $invoiceid == '') {
                    return redirect()->back()->with([
                        'alert-message' => 'Invoice tidak ditemukan',
                        'alert-type' => 'danger'
                    ]);
                }
                // $invoiceid = DB::table('sell_domain_invoices')->where('domain', $domain)->first()->invoice;

                try {
                    $results = DB::table('sell_domain')->where('domain', $domain)->update(['status'=>'INVOICE_PAID', 'invoiceid'=>$invoiceid]);
                } catch (\Exception $e) {
                    return redirect()->back()->with([
                        'alert-message' => 'Gagal Update Invoice Paid' . $e->getMessage(),
                        'alert-type' => 'danger'
                    ]);
                }
                // header('Content-Type: text/html');
                return redirect()->back()->with([
                    'alert-message' => 'Berhasil Update Invoice Paid',
                    'alert-type' => 'success'
                ]);
            break;

            case 'notif_epp_salah':
                $domain = $request->get('domain');

                if($domain == null || $domain == '') {
                    return redirect()->back()->with([
                        'alert-message' => 'Domain tidak ditemukan',
                        'alert-type' => 'danger'
                    ]);
                }

                try {
                    $data = DB::table('sell_domain')->where('domain', $domain)->first();
                } catch (\Exception $e) {
                    return redirect()->back()->with([
                        'alert-message' => 'Data Domain Tidak Ditemukan' . $e->getMessage(),
                        'alert-type' => 'danger'
                    ]);
                }
                
                $clientid = $data->clientid;
                $results = $sellDomain->notifGeneral($clientid, 'Notif EPP Salah', "<p>Mohon maaf kode epp berikut ini: <b>". $data->epp ."</b> tidak sesuai </p>");
                if ($results['result'] == 'success') {
                    return redirect()->back()->with([
                        'alert-message' => 'Berhasil Notif EPP Salah',
                        'alert-type' => 'success'
                    ]);
                } else {
                    return redirect()->back()->with([
                        'alert-message' => $results['message'],
                        'alert-type' => 'danger'
                    ]);
                }
            break;

            case 'get_bank':
                $clientid = $request->get('clientid');
                if($clientid == null || $clientid == '') {
                    return response()->json(['error' => 'Client ID tidak ditemukan']);
                }
                $bank = $sellDomain->getBankClient($clientid);
                if($bank == null) {
                    return response()->json(['error' => 'Bank tidak ditemukan']);
                }

                return response()->json($bank);
            break;

            case 'get_dana':
                $invoiceid = $request->get('invoiceid');
                $clientid = $request->get('clientid');
                
                if($clientid == null || $clientid == '') {
                    return response()->json(['error' => 'Client ID tidak ditemukan']);
                }
                if($invoiceid == null || $invoiceid == '') {
                    return response()->json(['error' => 'Invoice ID tidak ditemukan']);
                }

                $return = [];
                if (boolval($invoiceid)){
                    $res = $sellDomain->calculateDanaDicairkan($invoiceid, $clientid);
                    $return['data'] = $res;
                } else {
                    $return['error'] = 'Invoice is null';
                }

                // echo json_encode($return);
                // die();
                return response()->json($return);
            break;

            case 'list_invoices':

                $domain = $request->get('domain');
                $results = DB::table('sell_domain_invoices')
                    ->join('tblinvoices', 'sell_domain_invoices.invoice', '=', 'tblinvoices.id')
                    ->where('domain', $domain)
                    ->whereIn('status', ['Paid','Collections'])
                    ->get();

                // header('Content-Type: application/json');
                // echo json_encode($results);
                // die();
                return response()->json($results);
            break;

            case 'upload_bukti':
                $domain = $request->get('domain');
                $file = $request->file('image');

                if ($domain && $file) {
                    $baseDirectory = public_path('storage/modules');
                    $directory = $baseDirectory . '/sell_domain/';
                    
                    // Check if the directory exists, if not, create it
                    if (!is_dir($directory)) {
                        if (!mkdir($directory, 0755, true)) {
                            return response()->json([
                                'status' => 'error',
                                'message' => 'Failed to create directory'
                            ]);
                        }
                    }

                    $path = $file->getClientOriginalName();
                    $ext = pathinfo($path, PATHINFO_EXTENSION);
                    $filename = uniqid() . '.' . $ext;
                    $fullPath = $directory . $filename;

                    // Check if the filename is valid
                    if (empty($filename) || !preg_match('/^[a-zA-Z0-9_\-\.]+$/', $filename)) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Invalid filename'
                        ]);
                    }

                    try {
                        $file->move($directory, $filename);
                        // $publicPath = str_replace(public_path(), '', $fullPath);
                        // $publicUrl = url($publicPath);

                        $relativePath = 'storage/modules/sell_domain/' . $filename; 
                        $publicUrl = url($relativePath);

                        DB::table('sell_domain_invoices')
                            ->where('domain', $domain)
                            ->update(['bukti_settled' => $publicUrl]);

                        return response()->json([
                            'status' => 'success',
                            'message' => 'Berhasil Upload Bukti',
                            'data' => $publicUrl
                        ]);
                    } catch (\Exception $e) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Gagal Upload Bukti: ' . $e->getMessage()
                        ]);
                    }
                } else {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'No domain or image provided'
                    ]);
                }
            break;

            case 'rent_process_transfer':
                $domain = $request->get('domain');
                $results = $rentDomain->startRentFromAdmin($domain);
                if($results['status'] == 'success') { 
                    return redirect()->back()->with([
                        'alert-message' => $results['message'],
                        'alert-type' => 'success'
                    ]);
                } else {
                    return redirect()->back()->with([
                        'alert-message' => $results['message'],
                        'alert-type' => 'danger'
                    ]);
                }
            break;

            case 'reject_sell_domain':
                $id = $request->get('id');
                $notes = $request->get('notes');
         
                try {
                    DB::table('sell_domain')
                        ->where('id', $id)
                        ->update([
                            'status' => 'REJECTED',
                            'notes' => $notes,
                        ]);
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Berhasil Reject Sell Domain',
                        'data' => $sellDomain->getDomainAllAdmin()
                    ]);
                } catch (\Exception $e) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Gagal Reject Sell Domain: ' . $e->getMessage()
                    ]);
                }
            break;

            case 'delete_sell_domain':
                $domain = $request->get('domain');
                DB::table('sell_domain')
                    ->where('domain', $domain)
                    ->delete();

                try {
                    DB::table('auction_domain')
                        ->where('domain', $domain)
                        ->delete();
                } catch (\Exception $e) {
                    return redirect()->back()->with([
                        'alert-message' => 'Gagal Delete Domain' . $e->getMessage(),
                        'alert-type' => 'danger'
                    ]);
                }

                // header('Content-Type: text/html');
                return redirect()->back()->with([
                    'alert-message' => 'Berhasil Delete Domain',
                    'alert-type' => 'success'
                ]);
            break;

            case 'park_domain':
                $domain = $request->input('domain');
                $url = "https://qwords.io/parked-domain/parked-domain.php?domain=" . urlencode($domain);

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disabling SSL verification (use cautiously)

                $response = curl_exec($ch);
                if(!$response){
                    return redirect()->back()->with([
                        'alert-message' => "Gagal mengambil Data Domain. $response",
                        'alert-type' => 'danger'
                    ]);
                }

                if (curl_errno($ch)) {
                    $curl_error = curl_error($ch);
                    // echo "cURL error: $curl_error";
                    return redirect()->back()->with([
                        'alert-message' => "Gagal Park Domain. $curl_error",
                        'alert-type' => 'danger'
                    ]);
                } else {
                    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                    // Check if response is valid JSON
                    if ($http_code >= 200 && $http_code < 300) {
                        $response_data = json_decode($response, true);

                        if (isset($response_data['cpanelresult']['data'][0]['status']) && $response_data['cpanelresult']['data'][0]['status'] == 1) {
                            $message = 'Sukses Park Domain';
                            // header("Location: /qwadmin/addonmodules.php?module=sell_domain&message=" . urlencode($message));
                            // exit;
                            return redirect()->back()->with([
                                'alert-message' => $message,
                                'alert-type' => 'success'
                            ]);
                        } else {
                            $error_message = isset($response_data['cpanelresult']['data'][0]['reason']) ? $response_data['cpanelresult']['data'][0]['reason'] : 'Unknown error';
                            $message_error = "Gagal Park Domain. $error_message";
                            // header("Location: /qwadmin/addonmodules.php?module=sell_domain&message_error=" . urlencode($message_error));
                            // exit;
                            return redirect()->back()->with([
                                'alert-message' => $message_error,
                                'alert-type' => 'danger'
                            ]);
                        }
                    } else {
                        // echo "HTTP Error: $http_code";
                        return redirect()->back()->with([
                            'alert-message' => "Gagal Park Domain. $http_code",
                            'alert-type' => 'danger'
                        ]);
                    }
                }

                curl_close($ch);
            break;

            default:
                break;
        }
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('selldomain::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('selldomain::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('selldomain::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('selldomain::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }
}

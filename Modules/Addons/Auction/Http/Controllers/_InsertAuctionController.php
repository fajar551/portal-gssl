<?php

namespace Modules\Addons\Auction\Http\Controllers;

use App\Helpers\SystemHelper;
use App\Http\Controllers\Client\_AuctionController;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class _InsertAuctionController extends Controller
{
    public function getData($domain, $clientid) {
        try {
            $domainExists = DB::table('auction_domain')->where('domain', $domain)->first();
    
            if (!$domainExists) {
                // Jika domain belum ada, buat tanggal buka dan tutup lelang
                $openDate = date('Y-m-d H:i:s');
                $closeDate = new \DateTime($openDate);
                $closeDate->modify('+10 days');
                $closeDateStr = $closeDate->format('Y-m-d H:i:s');
    
                // Menyimpan informasi domain ke tabel auction_domain
                try {
                    DB::table('auction_domain')->insert([
                        'domain' => $domain, 
                        'price' => 0,
                        'open_date' => $openDate,
                        'close_date' => $closeDateStr,
                    ]);
                } catch (\Exception $e) {
                    return redirect()->back()->with([
                        'alert-message' => 'Gagal menyimpan domain: ' . $e->getMessage(),
                        'alert-type' => 'danger'
                    ]);
                }
    
                // Menyimpan riwayat domain ke tabel auction_domain_history
                try {
                    DB::table('auction_domain_history')->insert([
                        'domain' => $domain, 
                        'client_id' => 9999999999,
                        'bid_price' => 0,
                        'last_price' => 0
                    ]);
                } catch (\Exception $e) {
                    return redirect()->back()->with([
                        'alert-message' => 'Gagal menyimpan riwayat domain: ' . $e->getMessage(),
                        'alert-type' => 'danger'
                    ]);
                }
                return true;
            } 
    
            $userExists = DB::table('auction_domain_history')
                ->where('domain', $domain)
                ->where('client_id', $clientid)->first();
    
            // Jika client belum ada, tambahkan ke riwayat
            if (!$userExists) {
                try {
                    DB::table('auction_domain_history')->insert([
                        'domain' => $domain, 
                        'client_id' => $clientid,
                        'bid_price' => 0,
                        'last_price' => 0
                    ]);
                } catch (\Exception $e) {
                    return redirect()->back()->with([
                        'alert-message' => 'Gagal menyimpan riwayat client: ' . $e->getMessage(),
                        'alert-type' => 'danger'
                    ]);
                }
            }
            return true;
    
        } catch (\Exception $e) {
            // Jika terjadi error umum
            return redirect()->back()->with([
                'alert-message' => 'Gagal, ' . $e->getMessage(),
                'alert-type' => 'danger'
            ]);
        }
    }

    public function insertAuction(Request $request) {
        $auth = Auth::guard('admin')->user();
        $clientid = $auth->id;
        $domain = $request->input('domain');
        $type = $request->input('type');

        if($type != 'redirect'){
            $auction = new _AuctionController();
            $auction->showRouteNotFound();
            return;
        }

        if($type == 'redirect') {
            $resultGetData = $this->getData($domain, $clientid);
            if(!$resultGetData) {
                return redirect()->back()->with([
                    'alert-message' => 'Data tidak ditemukan.',
                    'alert-type' => 'danger'
                ]);
            }
            $response = $this->handleRequest($domain);
            if ($response) {
                if ($response['code'] == 501) {
                    $message = "Jika hanya ada 1 pemesanan domain tidak bisa melakukan lelang atau domain sedang dilelang";
                    return redirect()->back()->with([
                        'alert-message' => $message,
                        'alert-type' => 'danger'
                    ]);
                } else if ($response['code'] ==  null) {
                    $url = "/admin/addonsmodule?module=auction";
                    return redirect($url)->with([
                        'alert-message' => 'No domain provided',
                        'alert-type' => 'danger'
                    ]);
                } else {
                    $message = "Sukses insert data";
                    $url = "/admin/addonsmodule?module=auction";
                    return redirect($url)->with([
                        'alert-message' => $message,
                        'alert-type' => 'success'
                    ]);
                }
            } else {
                $auction = new _AuctionController();
                $auction->showRouteNotFound();
                // echo json_encode($response);
                return;
            }
        }
    }

    public function handleRequest($domain) {
        if (!$domain) {
            $response = [];
            $response['code'] = null;
            
            return $response ;
        }
        // Mengecek apakah domain ada di tabel tblcustomfieldsvalues
        $duplicateDomain = DB::table('tblcustomfieldsvalues')
            ->join('tblhosting', 'tblcustomfieldsvalues.relid', '=', 'tblhosting.id')
            ->join('tblorders', 'tblhosting.orderid', '=', 'tblorders.id')
            ->where('value', $domain)
            ->where('domainstatus', 'Active')
            ->where('fieldid', 1215) // ID custom field untuk backorder domain
            ->groupBy('tblhosting.userid')
            ->get();

        // Mengirim email dengan informasi domain yang ditemukan
        // $command = 'SendEmail';
        $postData = [
            'id' => '45244',
            'customtype' => 'general',
            'customsubject' => 'InsertAuction',
            'custommessage' => '<p>{$duplicateDomain} , => {$domain}</p>',
            'customvars' => base64_encode(serialize([
                "duplicateDomain" => json_encode(count($duplicateDomain)), 
                "domain" => $domain
            ])),
        ];
        // $results = localAPI($command, $postData);
        $systemHelper = new SystemHelper();
        $systemHelper->SendEmail($postData);

        // Jika ada lebih dari satu domain yang terduplikasi, mulai lelang
        if (count($duplicateDomain) > 1) {
            $response['data'] = "Starting Auction";
            foreach ($duplicateDomain as $item) {
                $this->insertAuction($item->value, $item->userid);
            }
            $response['data'] = $duplicateDomain;
        } else {
            // Jika tidak ada domain duplikat
            $response['code'] = 501;
            $response['data'] = $duplicateDomain;
            $response['message'] = "No duplicate domain. Auction not inserted";
        } 
        
        return $response;
    }
}
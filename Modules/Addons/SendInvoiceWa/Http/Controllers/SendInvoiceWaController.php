<?php

namespace Modules\Addons\SendInvoiceWa\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\InvoiceClass;

use Carbon\Carbon;
use App\Helpers\Client as HelpersClient;
use App\Helpers\Database;
use App\Helpers\Format;
use App\Models\Account;
use App\Models\Client;
use App\Models\Clientgroup;
use App\Models\Hosting;
use App\Models\Invoice;
use App\Models\Invoiceitem;
use App\Traits\DatatableFilter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Helpers\LogActivity;

class SendInvoiceWaController extends Controller
{

    use DatatableFilter;
    protected $prefix;

    public function __construct()
    {
        $this->middleware(['auth:admin']);
        $this->prefix = Database::prefix();
    }

    public function getLateClient()
    {
        $hostings = Hosting::where('domainstatus', 'Active')
            ->where('nextduedate', '<=', now())
            ->get();

        $userIds = $hostings->pluck('userid');
        $relIds = $hostings->pluck('id');

        $maxDueDates = Invoiceitem::whereIn('userid', $userIds)
            ->whereIn('relid', $relIds)
            ->groupBy(['userid', 'relid'])
            ->selectRaw('userid, relid, MAX(duedate) as max_duedate')
            ->get();

        $maxDueDatesFiltered = $maxDueDates->filter(function ($item) {
            return $item->max_duedate <= now()->subMonths(1);
        });

        $maxDueDatesFilteredReal = $maxDueDatesFiltered->filter(function ($item) {
            return $item->max_duedate >= now()->subMonths(4);
        });

        $invoices = Invoiceitem::whereIn('userid', $maxDueDatesFilteredReal->pluck('userid'))
            ->whereIn('relid', $maxDueDatesFilteredReal->pluck('relid'))
            ->whereIn('duedate', $maxDueDatesFilteredReal->pluck('max_duedate'))
            ->select('invoiceid', 'relid', 'duedate', 'userid')
            ->get();

        $invoicesFormatted = $invoices->reject(function ($item) {
            return $item->userid == 18; 
        })->map(function ($item) {
            $client = Client::find($item->userid);

            $fullName = $client ? $client->firstname . ' ' . $client->lastname : 'N/A';

            $email = $client ? $client->email : 'N/A';

            $linkAdmin = $client ? 'https://portal.relabs.id/admin/clients/clientsummary?userid=' . $item->userid : 'N/A';

            $lastInvoiceLink = 'https://portal.relabs.id/admin/billing/invoices/edit/' . $item->invoiceid;

            return [
                'Nama' => $fullName,
                'Email' => $email,
                'Link Admin' => $linkAdmin,
                'Last Invoice' => $lastInvoiceLink,
                'Last Due Date' => $item->duedate,
                'hostingid' => $item->relid,
                'invid' => $item->invoiceid,
                'uid' => $item->userid
            ];
        });

        dd($invoicesFormatted);


        // Create CSV content
        $csvContent = $invoicesFormatted->map(function ($row) {
            return implode(',', $row);
        })->prepend(implode(',', array_keys($invoicesFormatted->first())))->implode("\n");

        // Set headers for CSV download
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="late_clients.csv"',
        ];

        // Return CSV as response
        return response()->make($csvContent, 200, $headers);
    }

    // MENGIRIM INVOICE WA HANYA KE 1 CLIENT SAJA
    public function resendInvoiceWA(Request $request)
    {
        if ($request->has('show_loading')) {
            return view('pages.billing.invoices.loading-wa');
        }

        $successCount = 0;
        $failedCount = 0;

        try {
            // $invoice = DB::table("tblinvoices")->where(['id' => $request->invoiceid])->first();
            $invoice = DB::table("tblinvoices")
            ->where('id', $request->invoiceid)
            ->first();

            $user = DB::table("tblclients")->where(['id' => $invoice->userid])->first();

            $allitem = '';
            // $selectinvoiceitemswa2 = "SELECT CONCAT(tblproducts.name, ' - ', tblhosting.domain) as service, tblinvoiceitems.amount, tblhosting.regdate, tblhosting.packageid FROM tblinvoiceitems, tblhosting, tblproducts WHERE tblinvoiceitems.relid = tblhosting.id AND tblhosting.packageid = tblproducts.id AND tblinvoiceitems.invoiceid = ? AND tblinvoiceitems.type IN ('Hosting') AND tblinvoiceitems.userid NOT IN (SELECT userid FROM `tblhosting` WHERE `packageid` IN (52,53,54,62))";
            $selectinvoiceitemswa2 = "SELECT CONCAT(tblproducts.name, ' - ', tblhosting.domain) as service, tblinvoiceitems.amount, tblhosting.regdate, tblhosting.packageid 
                                  FROM tblinvoiceitems 
                                  JOIN tblhosting ON tblinvoiceitems.relid = tblhosting.id 
                                  JOIN tblproducts ON tblhosting.packageid = tblproducts.id 
                                  WHERE tblinvoiceitems.invoiceid = ? 
                                  AND tblinvoiceitems.type IN ('Hosting')";
                                  
            $queryselectinvoiceitemswa2 = DB::select($selectinvoiceitemswa2, [$request->invoiceid]);
            $resultselectinvoiceitemsnumrowswa2 = count($queryselectinvoiceitemswa2);
            
            if ($resultselectinvoiceitemsnumrowswa2 > 0) {
                $customdata1 = [];
                foreach ($queryselectinvoiceitemswa2 as $row1) {
                    $customdata1[] = (array)$row1;
                }

                foreach ($customdata1 as $row) {
                    Log::info(["datawaa", $row]);
                    $hosting = $row['service'];
                    $allitem .= $hosting . ", ";
                }
            }

            $phonenumber = preg_replace("/[^0-9]/", "", trim($user->phonenumber));
            $phonenumber = (substr($phonenumber, 0, 2) != '62') ? '62' . substr($phonenumber, 1) : $phonenumber;
            $totalrupiah = "Rp" . number_format($invoice->total, 2, ',', '.');

            $invoicedata = new \App\Helpers\InvoiceClass($invoice->id);
            $invoiceLink = $invoicedata->getPaymentLink();

            $message = "";
            $messagename = "";
            $norek = "";

           switch ($invoice->paymentmethod) {
			case 'banktransfer':
				$messagename = "Bank Transfer BCA";
				$paymentmethod = "BCA Bank Transfer";
				$norek = '503-5778-770 A/N PT Qwords Company International';
                break;
            case 'bcava':
                $paymentmethod = "BCA Virtual Account";
                $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message);
                break;
            case 'bcavaxendit':
                $paymentmethod = "BCA Virtual Account";
                $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message);
                break;
            case 'mandiriva':
                $paymentmethod = "Mandiri Virtual Account";
                $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message);
                break;
            case 'mandirivaxendit':
                $paymentmethod = "Mandiri Virtual Account";
                $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message);
                break;
            case 'bniva':
                $paymentmethod = "BNI Virtual Account";
                $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message);
                break;
            case 'bnivaxendit':
                $paymentmethod = "BNI Virtual Account";
                $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message);
                break;
            case 'briva':
                $paymentmethod = "BRI Virtual Account";
                $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message);
                break;
            case 'brivaxendit':
                $paymentmethod = "BRI Virtual Account";
                $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message);
                break;
            case 'permatabankva':
                $paymentmethod = "Permata Bank Virtual Account";
                $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message);
                break;
            case 'permatabankvaxendit':
                $paymentmethod = "Permata Bank Virtual Account";
                $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message);
                break;
            case 'sampoernava':
                 $paymentmethod = "Sampoerna Bank Virtual Account";
                $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message);
                break;
            case 'sampoernavaxendit':
                 $paymentmethod = "Sampoerna Bank Virtual Account";
                $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message);
                break;
            case 'atmbersamaxendit':
                $paymentmethod = "ATM BERSAMA Virtual Account";
                $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message);
                break;
			case 'danaxendit':
			    $paymentmethod = "DANA";
				$messagename = "DANA";
				$norek = 'Login Akun Relabs -> Buka Invoice -> Ikuti sesuai Petunjuk tertera';
                break;
			case 'ovoxendit':
			    $paymentmethod = "OVO";
				$messagename = "OVO";
				$norek = 'Login Akun Relabs -> Buka Invoice -> Ikuti sesuai Petunjuk tertera';
                break;
			case 'linkajaxendit':
			    $paymentmethod = "LinkAja";
				$messagename = "LinkAja";
				$norek = 'Login Akun Relabs -> Buka Invoice -> Ikuti sesuai Petunjuk tertera';
                break;
            case 'gopaymidtrans':
                $paymentmethod = "GoPay";
				$messagename = "GoPay";
				$norek = 'Login Akun Relabs -> Buka Invoice -> Ikuti sesuai Petunjuk tertera';
                break;
            case 'ccmidtrans':
                $paymentmethod = "Credit Card (Visa, Mastercard, JBC, American Express)";
				$messagename = "Credit Card (Visa, Mastercard, JBC, American Express)";
				$norek = 'Login Akun Relabs -> Buka Invoice -> Ikuti sesuai Petunjuk tertera';
                break;
			case 'alfamartxendit':
			    $paymentmethod = "Alfamart";
				$messagename = "Alfamart";
				$norek = 'Login Akun Relabs -> Buka Invoice -> Ikuti sesuai Petunjuk tertera';
				break;
			case 'shopeepayxendit':
			    $paymentmethod = "ShopeePay";
				$messagename = "ShopeePay";
				$norek = 'Login Akun Relabs -> Buka Invoice -> Ikuti sesuai Petunjuk tertera';
                break;
            default:
                $messagename = "";
				$norek = '';
                break;
            }
		
            $tanggaperiod = $invoice->duedate;
            
            $locale = 'id'; 
    
            // Set the locale dynamically
            // Carbon::setLocale($locale);
            // Carbon::setTimezone('Asia/Jakarta');

            // Set the locale dynamically
            Carbon::setLocale($locale);
            $carbonDate = Carbon::parse($tanggaperiod);
            $carbonDate->setTimezone(new \DateTimeZone('Asia/Jakarta')); 
    
            // Get the formatted month name
            $nextMonthName = $carbonDate->translatedFormat('F');

            $url = 'https://portal.qwords.com/apis/wa/waTagihanGSSL.php';
            $data = [
                'phone' => $phonenumber,
                'firstname' => $user->firstname,
                'idclient' => $invoice->userid,
                'periode' => $nextMonthName,
                'paymentmethod' => $paymentmethod,
                'message' => $message,
                'messagename' => $messagename,
                'norek' => $norek,
                'allitem' => $allitem,
                'invoiceid' => $invoice->id,
                'duedate' => $invoice->duedate,
                'totalrupiah' => $totalrupiah,
            ];

            Log::info("SendWaInvoice data " . json_encode($data));

            // 3 Kali Percobaan
            $maxRetries = 3;
            $attempt = 1;
            $success = false;

            while ($attempt <= $maxRetries && !$success) {
                try {
                    $response = Http::timeout(15)
                        ->retry(3, 100)
                        ->post($url, $data);
                        
                    $responseBody = $response->body();
                    Log::info("SendWaInvoice response attempt #{$attempt}: " . $responseBody);
                    
                    if ($response->successful()) {
                        $success = true;
                        break;
                    }
                    
                } catch (\Exception $e) {
                    Log::error("SendWaInvoice attempt #{$attempt} failed: " . $e->getMessage());
                    if ($attempt == $maxRetries) {
                        throw $e;
                    }
                }
                
                $attempt++;
                sleep(2);
            }

            if ($success) {
                LogActivity::Save("Pengiriman WA Berhasil - Invoice #{$invoice->id}");
                $successCount++;
            } else {
                LogActivity::Save("Pengiriman WA Gagal setelah {$maxRetries} percobaan - Invoice #{$invoice->id}");
                $failedCount++;
            }

        } catch (\Exception $e) {
            Log::debug("SendWaInvoice error " . $e->getMessage());
            $failedCount++;
        }

        return "Selesai mengirim WA: Berhasil: {$successCount}, Gagal: {$failedCount}";
    }

    // INI UNTUK SEND CUSTOM WA KE SEMUA CLIENTS BERDASARKAN GROUP ID YANG SAMA
    // DI BUTTON SEND WA TO ALL CLIENTS
    public function resendInvoiceWABulk(Request $request)
    {
        if ($request->has('show_loading')) {
            $groupName = DB::table('tblclientgroups')
                ->where('id', $request->groupid)
                ->value('groupname') ?? 'Bandung';

            return view('pages.billing.invoices.loading-wa', [
                'groupName' => $groupName
            ]);
        }

        $request->validate([
            'groupid' => 'required|numeric',
            'message' => 'required|string',
            'delay' => 'required|integer|min:1'
        ]);

        // Get only active clients from specific group
        $clients = DB::table("tblclients")
            ->where('status', 'Active')
            ->where('groupid', $request->groupid)
            ->select('*')
            ->get();

        $successCount = 0;
        $failedCount = 0;

        foreach ($clients as $client) {
            try {
                $phonenumber = preg_replace("/[^0-9]/", "", trim($client->phonenumber));
                $phonenumber = (substr($phonenumber, 0, 2) != '62') ? '62' . substr($phonenumber, 1) : $phonenumber;

                // Proses pesan custom dengan variabel dinamis
                $customMessage = $request->message;

                // Cek apakah pesan mengandung variabel $name
                if (strpos($customMessage, '$name') !== false) {
                    // Ganti $name dengan nama klien
                    $customMessage = str_replace('$name', $client->firstname . ' ' . $client->lastname, $customMessage);
                }

                // Di sini bisa ditambahkan variabel lain jika diperlukan
                // Contoh: if (strpos($customMessage, '$email') !== false) { ... }

                $data = [
                    'phone' => $phonenumber,
                    'message' => $customMessage,
                    'isCustomMessage' => true
                ];

                $url = 'https://portal.qwords.com/apis/wa/waTagihanGSSL.php';
                $response = Http::post($url, $data);

                if ($response->successful()) {
                    LogActivity::Save("Pengiriman WA Berhasil - Invoice #{$client->invoice_id}");
                    $successCount++;
                } else {
                    LogActivity::Save("Pengiriman WA Gagal - Invoice #{$client->invoice_id}");
                    $failedCount++;
                }

                sleep($request->delay);
            } catch (\Exception $e) {
                Log::debug("SendWABulk error " . $e->getMessage());
                $failedCount++;
            }
        }

        return "Selesai mengirim WA: Berhasil: {$successCount}, Gagal: {$failedCount}";
    }

    public function index()
    {
        return view('sendinvoicewa::index');
    }

    public function sendCustomWA(Request $request)
    {
        if ($request->has('show_loading')) {
            return view('pages.billing.invoices.loading-wa');
        }
                
        $request->validate([
            'message' => 'required|string',
            'delay' => 'required|integer|min:1'
        ]);

        $clients = DB::table("tblclients")
            ->where('status', 'Active')
            ->select('id', 'phonenumber')
            ->get();

        $totalClients = $clients->count();
            
        if ($totalClients == 0) {
            return "Tidak ada client aktif yang ditemukan";
        }

        $successCount = 0;
        $failedCount = 0;

        foreach ($clients as $client) {
            try {
                $invoice = DB::table("tblinvoices")->where(['id' => $request->invoiceid])->first();
                $user = DB::table("tblclients")->where(['id' => $invoice->userid])->first();

                $allitem = '';
                $selectinvoiceitemswa2 = "SELECT CONCAT(tblproducts.name, ' - ', tblhosting.domain) as service, tblinvoiceitems.amount, tblhosting.regdate, tblhosting.packageid FROM tblinvoiceitems, tblhosting, tblproducts WHERE tblinvoiceitems.relid = tblhosting.id AND tblhosting.packageid = tblproducts.id AND tblinvoiceitems.invoiceid = ? AND tblinvoiceitems.type IN ('Hosting')";
                $queryselectinvoiceitemswa2 = DB::select($selectinvoiceitemswa2, [$request->invoiceid]);
                $resultselectinvoiceitemsnumrowswa2 = count($queryselectinvoiceitemswa2);

                if ($resultselectinvoiceitemsnumrowswa2 > 0) {
                    $customdata1 = [];
                    foreach ($queryselectinvoiceitemswa2 as $row1) {
                        $customdata1[] = (array)$row1;
                    }

                    foreach ($customdata1 as $row) {
                        Log::info(["datawaa", $row]);
                        $hosting = $row['service'];
                        $allitem .= $hosting . ", ";
                    }
                }

                $phonenumber = preg_replace("/[^0-9]/", "", trim($user->phonenumber));
                $phonenumber = (substr($phonenumber, 0, 2) != '62') ? '62' . substr($phonenumber, 1) : $phonenumber;
                $totalrupiah = "Rp" . number_format($invoice->total, 2, ',', '.');

                $invoicedata = new \App\Helpers\InvoiceClass($invoice->id);
                $invoiceLink = $invoicedata->getPaymentLink();

                $message = "";
                $messagename = "";
                $norek = "";

                switch ($invoice->paymentmethod) {
                    case 'banktransfer':
                        $messagename = "Bank Transfer BCA";
                        $norek = '503-5778-770 A/N PT Qwords Company International';
                        break;
                    case 'bcava':
                    case 'bcavaxendit':
                    case 'mandiriva':
                    case 'mandirivaxendit':
                    case 'bniva':
                    case 'bnivaxendit':
                    case 'briva':
                    case 'brivaxendit':
                    case 'permatabankva':
                    case 'permatabankvaxendit':
                    case 'sampoernava':
                    case 'sampoernavaxendit':
                    case 'atmbersamaxendit':
                        $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message);
                        break;
                    case 'danaxendit':
                        $messagename = "DANA";
                        $norek = 'Login Akun Relabs -> Buka Invoice -> Ikuti sesuai Petunjuk tertera';
                        break;
                    case 'ovoxendit':
                        $messagename = "OVO";
                        $norek = 'Login Akun Relabs -> Buka Invoice -> Ikuti sesuai Petunjuk tertera';
                        break;
                    case 'linkajaxendit':
                        $messagename = "LinkAja";
                        $norek = 'Login Akun Relabs -> Buka Invoice -> Ikuti sesuai Petunjuk tertera';
                        break;
                    case 'gopaymidtrans':
                        $messagename = "GoPay";
                        $norek = 'Login Akun Relabs -> Buka Invoice -> Ikuti sesuai Petunjuk tertera';
                        break;
                    case 'ccmidtrans':
                        $messagename = "Credit Card (Visa, Mastercard, JBC, American Express)";
                        $norek = 'Login Akun Relabs -> Buka Invoice -> Ikuti sesuai Petunjuk tertera';
                        break;
                    case 'alfamartxendit':
                        $messagename = "Alfamart";
                        $norek = 'Login Akun Relabs -> Buka Invoice -> Ikuti sesuai Petunjuk tertera';
                        break;
                    case 'shopeepayxendit':
                        $messagename = "ShopeePay";
                        $norek = 'Login Akun Relabs -> Buka Invoice -> Ikuti sesuai Petunjuk tertera';
                        break;
                    default:
                        $messagename = "";
                        $norek = '';
                        break;
                }

                $url = 'https://portal.qwords.com/apis/wa/waTagihanGSSL.php';
                $data = [
                    'phone' => $phonenumber,
                    'firstname' => $user->firstname,
                    'message' => $message,
                    'messagename' => $messagename,
                    'norek' => $norek,
                    'allitem' => $allitem,
                    'invoiceid' => $invoice->id,
                    'duedate' => $invoice->duedate,
                    'totalrupiah' => $totalrupiah,
                ];
                $response = Http::post($url, $data);
                    
                Log::info("Send ALL WA response ".$response->body());
                $successCount++;
                    
                sleep($request->delay);

            } catch (\Exception $e) {
                $failedCount++;
                Log::debug("Send ALL WA error ".$e->getMessage());
            }
        }

        return "Selesai mengirim WA: Berhasil: {$successCount}, Gagal: {$failedCount}";
    }

    public function getGroupName($groupId)
    {
        $groupName = DB::table('tblclientgroups')
            ->where('id', $groupId)
            ->value('groupname');
            
        return $groupName ?? 'Kelompok Client';
    }

    // PENGINGAT INVOICE DUA HARI DARI SEKARANG YANG UNPAID
    public function sendReminderInvoiceWA()
    {
        $twoDaysFromNow = Carbon::now()->addDays(2)->toDateString();

        // $invoices = DB::table('tblinvoices')
        //     ->where('duedate', $twoDaysFromNow)
        //     ->where('status', 'Unpaid')->from('tblhosting')->get();

        $invoices = DB::table('tblinvoices')
            ->where('duedate', $twoDaysFromNow)
            ->where('status', 'Unpaid')
            ->get();

            \Log::debug("Tanggal dua hari dari sekarang: {$twoDaysFromNow}");
            \Log::debug("Jumlah invoice yang ditemukan: " . $invoices->count());

            if ($invoices->isEmpty()) {
                \Log::debug("Tidak ada invoice yang cocok.");
            }
            
        $successCount = 0;
        $failedCount = 0;

        foreach ($invoices as $invoice) {
            try {
                // Mengambil data pengguna berdasarkan user ID dari invoice
                // $user = DB::table('tblclients')->where('id', $invoice->userid)->first();

                // $invoice = DB::table("tblinvoices")->where(['id' => $request->invoiceid])->first();
                $user = DB::table("tblclients")->where(['id' => $invoice->userid])->first();

                $allitem = '';
            // $selectinvoiceitemswa2 = "SELECT CONCAT(tblproducts.name, ' - ', tblhosting.domain) as service, tblinvoiceitems.amount, tblhosting.regdate, tblhosting.packageid FROM tblinvoiceitems, tblhosting, tblproducts WHERE tblinvoiceitems.relid = tblhosting.id AND tblhosting.packageid = tblproducts.id AND tblinvoiceitems.invoiceid = ? AND tblinvoiceitems.type IN ('Hosting') AND tblinvoiceitems.userid NOT IN (SELECT userid FROM `tblhosting` WHERE `packageid` IN (52,53,54,62))";
            $selectinvoiceitemswa2 = "SELECT CONCAT(tblproducts.name, ' - ', tblhosting.domain) as service, tblinvoiceitems.amount, tblhosting.regdate, tblhosting.packageid 
                                  FROM tblinvoiceitems 
                                  JOIN tblhosting ON tblinvoiceitems.relid = tblhosting.id 
                                  JOIN tblproducts ON tblhosting.packageid = tblproducts.id 
                                  WHERE tblinvoiceitems.invoiceid = ? 
                                  AND tblinvoiceitems.type IN ('Hosting')";
                                  
            $queryselectinvoiceitemswa2 = DB::select($selectinvoiceitemswa2, [$request->invoiceid]);
            $resultselectinvoiceitemsnumrowswa2 = count($queryselectinvoiceitemswa2);
            
            if ($resultselectinvoiceitemsnumrowswa2 > 0) {
                $customdata1 = [];
                foreach ($queryselectinvoiceitemswa2 as $row1) {
                    $customdata1[] = (array)$row1;
                }

                foreach ($customdata1 as $row) {
                    Log::info(["datawaa", $row]);
                    $hosting = $row['service'];
                    $allitem .= $hosting . ", ";
                }
            }

            $phonenumber = preg_replace("/[^0-9]/", "", trim($user->phonenumber));
            $phonenumber = (substr($phonenumber, 0, 2) != '62') ? '62' . substr($phonenumber, 1) : $phonenumber;
            $totalrupiah = "Rp" . number_format($invoice->total, 2, ',', '.');

            $invoicedata = new \App\Helpers\InvoiceClass($invoice->id);
            $invoiceLink = $invoicedata->getPaymentLink();

            $message = "";
            $messagename = "";
            $norek = "";

            switch ($invoice->paymentmethod) {
                case 'banktransfer':
                    $messagename = "Bank Transfer BCA";
                    $paymentmethod = "BCA Bank Transfer";
                    $norek = '503-5778-770 A/N PT Qwords Company International';
                    break;
                case 'bcava':
                    $paymentmethod = "BCA Virtual Account";
                    $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message);
                    break;
                case 'bcavaxendit':
                    $paymentmethod = "BCA Virtual Account";
                    $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message);
                    break;
                case 'mandiriva':
                    $paymentmethod = "Mandiri Virtual Account";
                    $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message);
                    break;
                case 'mandirivaxendit':
                    $paymentmethod = "Mandiri Virtual Account";
                    $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message);
                    break;
                case 'bniva':
                    $paymentmethod = "BNI Virtual Account";
                    $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message);
                    break;
                case 'bnivaxendit':
                    $paymentmethod = "BNI Virtual Account";
                    $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message);
                    break;
                case 'briva':
                    $paymentmethod = "BRI Virtual Account";
                    $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message);
                    break;
                case 'brivaxendit':
                    $paymentmethod = "BRI Virtual Account";
                    $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message);
                    break;
                case 'permatabankva':
                    $paymentmethod = "Permata Bank Virtual Account";
                    $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message);
                    break;
                case 'permatabankvaxendit':
                    $paymentmethod = "Permata Bank Virtual Account";
                    $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message);
                    break;
                case 'sampoernava':
                    $paymentmethod = "Sampoerna Bank Virtual Account";
                    $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message);
                    break;
                case 'sampoernavaxendit':
                    $paymentmethod = "Sampoerna Bank Virtual Account";
                    $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message);
                    break;
                case 'atmbersamaxendit':
                    $paymentmethod = "ATM BERSAMA Virtual Account";
                    $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message);
                    break;
                case 'danaxendit':
                    $paymentmethod = "DANA";
                    $messagename = "DANA";
                    $norek = 'Login Akun Relabs -> Buka Invoice -> Ikuti sesuai Petunjuk tertera';
                    break;
                case 'ovoxendit':
                    $paymentmethod = "OVO";
                    $messagename = "OVO";
                    $norek = 'Login Akun Relabs -> Buka Invoice -> Ikuti sesuai Petunjuk tertera';
                    break;
                case 'linkajaxendit':
                    $paymentmethod = "LinkAja";
                    $messagename = "LinkAja";
                    $norek = 'Login Akun Relabs -> Buka Invoice -> Ikuti sesuai Petunjuk tertera';
                    break;
                case 'gopaymidtrans':
                    $paymentmethod = "GoPay";
                    $messagename = "GoPay";
                    $norek = 'Login Akun Relabs -> Buka Invoice -> Ikuti sesuai Petunjuk tertera';
                    break;
                case 'ccmidtrans':
                    $paymentmethod = "Credit Card (Visa, Mastercard, JBC, American Express)";
                    $messagename = "Credit Card (Visa, Mastercard, JBC, American Express)";
                    $norek = 'Login Akun Relabs -> Buka Invoice -> Ikuti sesuai Petunjuk tertera';
                    break;
                case 'alfamartxendit':
                    $paymentmethod = "Alfamart";
                    $messagename = "Alfamart";
                    $norek = 'Login Akun Relabs -> Buka Invoice -> Ikuti sesuai Petunjuk tertera';
                    break;
                case 'shopeepayxendit':
                    $paymentmethod = "ShopeePay";
                    $messagename = "ShopeePay";
                    $norek = 'Login Akun Relabs -> Buka Invoice -> Ikuti sesuai Petunjuk tertera';
                    break;
                default:
                    $messagename = "";
                    $norek = '';
                    break;
            }
            
            // Menggunakan tanggal invoice (date) sebagai dasar periode tagihan
			$currentTime = Carbon::now()->setTimezone('Asia/Jakarta');
            $startTime = Carbon::now()->setTimezone('Asia/Jakarta')->setTime(7, 40);
            $endTime = Carbon::now()->setTimezone('Asia/Jakarta')->setTime(10, 30);

            // Set $tanggaperiod based on time condition
            if ($currentTime->between($startTime, $endTime)) {
                $tanggaperiod = $invoice->duedate;
            } else {
                $tanggaperiod = $invoice->date;
            }
		
            // $tanggaperiod = $invoice->duedate;
            
            $locale = 'id'; 
    
            // Set the locale dynamically
            // Carbon::setLocale($locale);
            // Carbon::setTimezone('Asia/Jakarta');
            
            Carbon::setLocale($locale);
            $carbonDate = Carbon::parse($tanggaperiod);
            $carbonDate->setTimezone(new \DateTimeZone('Asia/Jakarta')); 

            $newDueDate = $carbonDate->addDays(5);
            
            // Get the formatted month name
            $nextMonthName = $carbonDate->translatedFormat('F');
            
            // $tanggalduedate = $invoice->duedate.' - '.$newDueDate;
            $tanggalduedate = $invoice->duedate;

                // Menyiapkan data untuk dikirim ke API WhatsApp
                $data = [
                    'phone' => $phonenumber,
                    'firstname' => $user->firstname,
                    'idclient' => $invoice->userid,
                    'periode' => $nextMonthName,
                    'paymentmethod' => $paymentmethod,
                    'message' => $message,
                    'messagename' => $messagename,
                    'norek' => $norek,
                    'allitem' => $allitem,
                    'invoiceid' => $invoice->id,
                    'duedate' => $tanggalduedate,
                    'totalrupiah' => $totalrupiah,
                    'reminderWa' => true
                ];

                // Logging untuk debugging
                Log::debug("Sending message: " . $message);

                 // Mengirim pesan melalui API
                $url = 'https://portal.qwords.com/apis/wa/waTagihanGSSL.php';
                $response = Http::post($url, $data);

                // Memeriksa apakah pengiriman berhasil
                if ($response->successful()) {
                    LogActivity::Save("Pengiriman WA Berhasil - Invoice #{$invoice->id}");
                    $successCount++;
                } else {
                    LogActivity::Save("Pengiriman WA Gagal - Invoice #{$invoice->id}");
                    $failedCount++;
                }

                // Menambahkan jeda 30 detik setelah setiap pengiriman pesan
                sleep(30);

            } catch (\Exception $e) {
                // Menangani error dan mencatatnya
                Log::debug("SendReminderInvoiceWA error " . $e->getMessage());
                $failedCount++;
            }
        }

        return "Selesai mengirim WA: Berhasil: {$successCount}, Gagal: {$failedCount}";
    }



    // UNTUK MENGIRIMKAN PENGINGAT WA KE SEMUA CLIENT YANG UNPAID
    public function resendInvoiceWAAllClients(Request $request)
    {
        if ($request->has('show_loading')) {
            return view('pages.billing.invoices.loading-wa');
        }
    
        $clients = DB::table("tblclients as c")
            ->join('tblinvoices as i', 'c.id', '=', 'i.userid')
            ->where('c.status', 'Active')
            ->where('i.status', 'Unpaid')
            ->select('c.*', 'i.id as invoice_id', 'i.total', 'i.paymentmethod', 'i.duedate')
            ->distinct()
            ->get();
    
        $successCount = 0;
        $failedCount = 0;
    
        foreach ($clients as $client) {
            try {
                $phonenumber = preg_replace("/[^0-9]/", "", trim($client->phonenumber));
                $phonenumber = (substr($phonenumber, 0, 2) != '62') ? '62' . substr($phonenumber, 1) : $phonenumber;
                $totalrupiah = "Rp" . number_format($client->total, 2, ',', '.');
    
                $invoicedata = new \App\Helpers\InvoiceClass($client->invoice_id);
                $invoiceLink = $invoicedata->getPaymentLink();
    
                $allitem = ''; // Initialize allitem
                $selectinvoiceitemswa2 = "SELECT CONCAT(tblproducts.name, ' - ', tblhosting.domain) as service, tblinvoiceitems.amount, tblhosting.regdate, tblhosting.packageid FROM tblinvoiceitems, tblhosting, tblproducts WHERE tblinvoiceitems.relid = tblhosting.id AND tblhosting.packageid = tblproducts.id AND tblinvoiceitems.invoiceid = ? AND tblinvoiceitems.type IN ('Hosting')";
                $queryselectinvoiceitemswa2 = DB::select($selectinvoiceitemswa2, [$client->invoice_id]);
                $resultselectinvoiceitemsnumrowswa2 = count($queryselectinvoiceitemswa2);
    
                if ($resultselectinvoiceitemsnumrowswa2 > 0) {
                    $customdata1 = [];
                    foreach ($queryselectinvoiceitemswa2 as $row1) {
                        $customdata1[] = (array)$row1;
                    }
    
                    foreach ($customdata1 as $row) {
                        Log::info(["datawaa", $row]);
                        $hosting = $row['service'];
                        $allitem .= $hosting . ", ";
                    }
                }
    
                $message = "";
                $messagename = "";
                $norek = "";
    
                switch ($client->paymentmethod) {
                    case 'banktransfer':
                        $messagename = "Bank Transfer BCA";
                        $norek = '503-5778-770 A/N PT Qwords Company International';
                        break;
                    case 'bcava':
                    case 'bcavaxendit':
                    case 'mandiriva':
                    case 'mandirivaxendit':
                    case 'bniva':
                    case 'bnivaxendit':
                    case 'briva':
                    case 'brivaxendit':
                    case 'permatabankva':
                    case 'permatabankvaxendit':
                    case 'sampoernava':
                    case 'sampoernavaxendit':
                    case 'atmbersamaxendit':
                        $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message);
                        break;
                    case 'danaxendit':
                        $messagename = "DANA";
                        $norek = 'Login Akun Relabs -> Buka Invoice -> Ikuti sesuai Petunjuk tertera';
                        break;
                    case 'ovoxendit':
                        $messagename = "OVO";
                        $norek = 'Login Akun Relabs -> Buka Invoice -> Ikuti sesuai Petunjuk tertera';
                        break;
                    case 'linkajaxendit':
                        $messagename = "LinkAja";
                        $norek = 'Login Akun Relabs -> Buka Invoice -> Ikuti sesuai Petunjuk tertera';
                        break;
                    case 'gopaymidtrans':
                        $messagename = "GoPay";
                        $norek = 'Login Akun Relabs -> Buka Invoice -> Ikuti sesuai Petunjuk tertera';
                        break;
                    case 'ccmidtrans':
                        $messagename = "Credit Card (Visa, Mastercard, JBC, American Express)";
                        $norek = 'Login Akun Relabs -> Buka Invoice -> Ikuti sesuai Petunjuk tertera';
                        break;
                    case 'alfamartxendit':
                        $messagename = "Alfamart";
                        $norek = 'Login Akun Relabs -> Buka Invoice -> Ikuti sesuai Petunjuk tertera';
                        break;
                    case 'shopeepayxendit':
                        $messagename = "ShopeePay";
                        $norek = 'Login Akun Relabs -> Buka Invoice -> Ikuti sesuai Petunjuk tertera';
                        break;
                    default:
                        $messagename = "";
                        $norek = '';
                        break;
                }
    
                $data = [
                    'phone' => $phonenumber,
                    'firstname' => $client->firstname,
                    'message' => $message,
                    'messagename' => $messagename,
                    'norek' => $norek,
                    'allitem' => $allitem,
                    'invoiceid' => $client->invoice_id,
                    'duedate' => $client->duedate,
                    'totalrupiah' => $totalrupiah,
                ];
    
                $url = 'https://portal.qwords.com/apis/wa/waTagihanGSSL.php';
                $response = Http::post($url, $data);
    
                if ($response->successful()) {
                    LogActivity::Save("Pengiriman WA Berhasil - Invoice #{$client->invoice_id}");
                    $successCount++;
                } else {
                    LogActivity::Save("Pengiriman WA Gagal - Invoice #{$client->invoice_id}");
                    $failedCount++;
                }
    
            } catch (\Exception $e) {
                Log::debug("SendWAAllClients error " . $e->getMessage());
                $failedCount++;
            }
        }
    
        return "Selesai mengirim WA: Berhasil: {$successCount}, Gagal: {$failedCount}";
    }
}
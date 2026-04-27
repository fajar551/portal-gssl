<?php

namespace App\Hooks;

use Carbon\Carbon;
use App\Events\InvoiceCreated;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class SendWaInvoice
{

    public function handle(InvoiceCreated $event)
    {
        // $invoice = DB::table("tblinvoices")
        //     ->where('id', $event->invoiceid)
        //     ->whereNotIn('userid', function($query) {
        //         $query->select('userid')
        //               ->from('tblhosting');
        //     })
        //     ->first();

        $invoice = DB::table("tblinvoices")
            ->where('id', $event->invoiceid)
            ->first();

        if (!$invoice) {
            Log::warning("SendWaInvoice: Invoice dengan ID {$event->invoiceid} tidak ditemukan.");
            return;
        }

        $user = DB::table("tblclients")->where(['id' => $invoice->userid])->first();

        $allitem = '';

        $selectinvoiceitemswa2 = "SELECT CONCAT(tblproducts.name, ' - ', tblhosting.domain) as service, tblinvoiceitems.amount, tblhosting.regdate FROM tblinvoiceitems, tblhosting, tblproducts WHERE tblinvoiceitems.relid = tblhosting.id AND tblhosting.packageid = tblproducts.id AND tblinvoiceitems.invoiceid = ? AND tblinvoiceitems.type IN ('Hosting')";

        $queryselectinvoiceitemswa2 = DB::select($selectinvoiceitemswa2, [$event->invoiceid]);
        $resultselectinvoiceitemsnumrowswa2 = count($queryselectinvoiceitemswa2);

        if ($resultselectinvoiceitemsnumrowswa2 > 0) {
            $allamountregister = 0;
            $allamountrenewal = 0;
            $jenisproduk = 'Hosting';

            foreach ($queryselectinvoiceitemswa2 as $row1) {
                $customdata1[] = (array)$row1;
            }

            foreach ($customdata1 as $row) {
                $hosting = $row['service'];
                $regdate = $row['regdate'];

                $allitem = $allitem . $hosting . ", ";
            }
        };

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
                $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message ?? '');
                break;
            case 'bcavaxendit':
                $paymentmethod = "BCA Virtual Account";
                $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message ?? '');
                break;
            case 'mandiriva':
                $paymentmethod = "Mandiri Virtual Account";
                $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message ?? '');
                break;
            case 'mandirivaxendit':
                $paymentmethod = "Mandiri Virtual Account";
                $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message ?? '');
                break;
            case 'bniva':
                $paymentmethod = "BNI Virtual Account";
                $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message ?? '');
                break;
            case 'bnivaxendit':
                $paymentmethod = "BNI Virtual Account";
                $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message ?? '');
                break;
            case 'briva':
                $paymentmethod = "BRI Virtual Account";
                $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message ?? '');
                break;
            case 'brivaxendit':
                $paymentmethod = "BRI Virtual Account";
                $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message ?? '');
                break;
            case 'permatabankva':
                $paymentmethod = "Permata Bank Virtual Account";
                $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message ?? '');
                break;
            case 'permatabankvaxendit':
                $paymentmethod = "Permata Bank Virtual Account";
                $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message ?? '');
                break;
            case 'sampoernava':
                $paymentmethod = "Sampoerna Bank Virtual Account";
                $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message ?? '');
                break;
            case 'sampoernavaxendit':
                $paymentmethod = "Sampoerna Bank Virtual Account";
                $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message ?? '');
                break;
            case 'atmbersamaxendit':
                $paymentmethod = "ATM BERSAMA Virtual Account";
                $message = str_replace(['<strong>', '</strong>'], '*', $invoiceLink->message ?? '');
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


        try {
            // Menggunakan tanggal invoice (date) sebagai dasar periode tagihan
            $currentTime = Carbon::now()->setTimezone('Asia/Jakarta');
            $startTime = Carbon::now()->setTimezone('Asia/Jakarta')->setTime(8, 00);
            $endTime = Carbon::now()->setTimezone('Asia/Jakarta')->setTime(9, 30);



            // Set $tanggaperiod based on time condition
            if ($currentTime->between($startTime, $endTime)) {
                $tanggaperiod = $invoice->duedate;
            } else {
                $tanggaperiod = $invoice->date;
            }

            $locale = 'id';

            // Set locale ke bahasa Indonesia
            Carbon::setLocale($locale);

            // Parse tanggal invoice
            $carbonDate = Carbon::parse($tanggaperiod);
            $carbonDate->setTimezone(new \DateTimeZone('Asia/Jakarta'));

            // Hitung tanggal jatuh tempo (5 hari setelah tanggal invoice)
            $newDueDate = $carbonDate->addDays(5);

            // Dapatkan nama bulan dalam bahasa Indonesia
            $nextMonthName = $carbonDate->translatedFormat('F');

            // Format tanggal jatuh tempo
            // $tanggalduedate = $invoice->duedate.' - '.$newDueDate;
            $tanggalduedate = $invoice->duedate;

            // Perbaikan: Handle jika $invoiceLink->message adalah array
            $message = $invoiceLink->message ?? '';
            if (is_array($message)) {
                $message = implode("\n", $message);
            }
            $message = str_replace(['<strong>', '</strong>'], '*', $message);

            // Perbaikan: Handle jika $allitem adalah array
            if (is_array($allitem)) {
                $allitem = implode(", ", $allitem);
            }

            // Kirim notifikasi WhatsApp
            $url = 'https://portal.qwords.com/apis/wa/waTagihanGSSL.php';
            $data = [
                'phone' => $phonenumber,
                'firstname' => $user->firstname,
                'idclient' => $invoice->userid,
                'periode' => $nextMonthName,
                'paymentmethod' => $paymentmethod ?? '',
                'message' => $message,
                'messagename' => $messagename,
                'norek' => $norek,
                'allitem' => $allitem,
                'invoiceid' => $invoice->id,
                'duedate' => $tanggalduedate,
                'totalrupiah' => $totalrupiah,
            ];

            // Perbaikan: Log data dengan format yang benar
            Log::info("SendWaInvoice data", $data);

            $response = Http::post($url, $data);

            // Perbaikan: Handle response body yang mungkin berupa array
            $responseBody = $response->body();
            if (is_array($responseBody)) {
                $responseBody = json_encode($responseBody);
            }
            Log::info("SendWaInvoice response: " . $responseBody);
        } catch (\Exception $e) {
            Log::debug("SendWaInvoice error: " . $e->getMessage());
        }
    }
}
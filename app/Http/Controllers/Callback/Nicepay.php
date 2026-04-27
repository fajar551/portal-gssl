<?php

namespace App\Http\Controllers\Callback;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Invoice;
use App\Helpers\Gateway;
use App\Helpers\Invoice as InvoiceHelper;
use App\Helpers\HelperApi;
use Illuminate\Support\Facades\Mail;

class Nicepay extends Controller
{
    // Fungsi untuk memanggil API Nicepay
    private function call($method = 'post', $url = '', $token = '', array $postfield = [])
    {
        $postfield = json_encode($postfield);

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_POSTFIELDS => $postfield,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode("$token:"),
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response);
    }

    // public function va(Request $request)
    // {
    //     try {
    //         $token = $request->header('x-callback-token');
    //         $callback = json_decode(file_get_contents('php://input'), true);
    //         Log::debug("===callback VA dibayar");
    //         Log::debug($callback);

    //         $tXid = $callback['tXid'];
    //         $amount = $callback['amt'];
    //         $referenceNo = $callback['referenceNo'];
    //         // $bank_code = $callback['bank_code'] ?? "";

    //         Log::info("Received Nicepay notification", $request->all());


    //         // Tentukan metode pembayaran berdasarkan kode bank
    //         // switch ($bank_code) {
    //         //     case 'BCA':
    //         //         $paymentmethod = "bcava";
    //         //         break;
    //         //     case 'BRI':
    //         //         $paymentmethod = "briva";
    //         //         break;
    //         //     case 'BNI':
    //         //         $paymentmethod = "bniva";
    //         //         break;
    //         //     case 'CIMB':
    //         //         $paymentmethod = "cimbva";
    //         //         break;
    //         //     case 'DANAMON':
    //         //         $paymentmethod = "danamonva";
    //         //         break;
    //         //     case 'HANABANK':
    //         //         $paymentmethod = "hanabankva";
    //         //         break;
    //         //     case 'MANDIRI':
    //         //         $paymentmethod = "mandiriva";
    //         //         break;
    //         //     case 'PERMATA':
    //         //         $paymentmethod = "permatabankva";
    //         //         break;
    //         //     case 'ATM BERSAMA':
    //         //         $paymentmethod = "atmbersamava";
    //         //         break;
    //         //     default:
    //         //         throw new \Exception("Gateway module not found using bank code $bank_code");
    //         //         break;
    //         // }

    //         // $params = Gateway::getGatewayVariables($paymentmethod);
    //         // $testMode = $params["testMode"];
    //         $clientIdNotification = 641;
    //         // $secretKey = $testMode ? $params["secretKeyTest"] : $params["secretKeyLive"];
    //         // $tokenModule = $testMode ? $params["tokenCallbackTest"] : $params["tokenCallbackLive"];

    //         $userid = ltrim($referenceNo, '0');

    //         $invoice = Invoice::whereRaw("userid = '$userid' AND (total = '$amount' OR total LIKE '" . $amount . ".%')")
    //             ->where("status", "Unpaid");
    //         $payment_obj_invoice = $invoice->first();
    //         $payment_num_rows_invoice = $invoice->count();

    //         $transaction = \App\Models\Account::where("transid", $tXid);
    //         $payment_num_rows_transaction_count = $transaction->count();

    //         if ($payment_num_rows_invoice == 1 && $payment_num_rows_transaction_count == 0) {
    //             $invoiceId = $payment_obj_invoice->id;

    //             $command = "SendEmail";
    //             $adminuser = "billing";
    //             $values["customtype"] = "general";
    //             $values["customsubject"] = "Ada Pembayaran Masuk ke Virtual Account Nicepay, Untuk No Invoice: " . $invoiceId . ", Txid:" . $tXid;
    //             $values["custommessage"] = "Silakan di cek di dashboard Nicepay.";
    //             $values["id"] = 641;
    //             $results = (new HelperApi)->localAPI($command, $values, $adminuser);

    //             InvoiceHelper::addInvoicePayment(
    //                 $invoiceId,
    //                 $tXid,
    //                 $amount,
    //                 '', //Payment Fee
    //                 // $paymentmethod
    //             );
    //         }

    //         if ($payment_num_rows_invoice != 1) {
    //             $invoicecount = Invoice::where(array("userid" => $userid, "status" => "Unpaid"));
    //             $payment_obj_invoice_count = $invoicecount->first();
    //             $payment_num_rows_invoice_count = $invoicecount->count();

    //             if ($payment_num_rows_invoice_count == 1 && $payment_num_rows_transaction_count == 0) {
    //                 $invoiceId = $payment_obj_invoice_count->id;
    //                 $command1 = "SendEmail";
    //                 $adminuser1 = "billing";
    //                 $values1["customtype"] = "general";
    //                 $values1["customsubject"] = "Ada Pembayaran Masuk ke Virtual Account Nicepay, TxID: " . $tXid . ". Tapi tidak ada Total yang sesuai, MOHON DI CEK SECARA TELITI ATAU KONFIRM KE CLIENT Apakah Masuk ke Invoice ini?: " . $invoiceId;
    //                 $values1["custommessage"] = "Silakan di cek di dashboard Nicepay.";
    //                 $values1["id"] = $clientIdNotification;
    //                 $results1 = (new HelperApi)->localAPI($command1, $values1, $adminuser1);
    //             }

    //             if ($payment_num_rows_invoice_count != 1 && $payment_num_rows_transaction_count == 0) {
    //                 $command2 = "SendEmail";
    //                 $adminuser2 = "billing";
    //                 $values2["customtype"] = "general";
    //                 $values2["customsubject"] = "Ada Pembayaran Masuk ke Virtual Account Nicepay, Tapi tidak dikenali Invoicenya, TxID: " . $tXid . ", UserID: " . $userid . ", Amount: " . $amount;
    //                 $values2["custommessage"] = "Silakan di cek di dashboard Nicepay.";
    //                 $values2["id"] = $clientIdNotification;
    //                 $results2 = (new HelperApi)->localAPI($command2, $values2, $adminuser2);
    //             }
    //         }

    //         return response()->json([
    //             'result' => 'success',
    //             'message' => 'Ok',
    //         ], 200);
    //     } catch (\Exception $e) {
    //         $message = "Callback Nicepay error: " . $e->getMessage();
    //         Log::error($message);
    //         return response()->json([
    //             'result' => 'error',
    //             'message' => $message,
    //         ], 400);
    //     }
    // }

//     public function va(Request $request)
// {
//     try {
//         $token = $request->header('x-callback-token');
//         $callback = json_decode(file_get_contents('php://input'), true);
//         Log::debug("===callback VA dibayar");
//         Log::debug($callback);

//         $tXid = $callback['tXid'];
//         $amount = $callback['amt'];
//         $referenceNo = $callback['referenceNo'];

//         Log::info("Received Nicepay notification", $request->all());

//         $clientIdNotification = 641;

//         $userid = ltrim($referenceNo, '0');

//         $invoice = Invoice::whereRaw("userid = '$userid' AND (total = '$amount' OR total LIKE '" . $amount . ".%')")
//             ->where("status", "Unpaid");
//         $payment_obj_invoice = $invoice->first();
//         $payment_num_rows_invoice = $invoice->count();

//         $transaction = \App\Models\Account::where("transid", $tXid);
//         $payment_num_rows_transaction_count = $transaction->count();

//         if ($payment_num_rows_invoice == 1 && $payment_num_rows_transaction_count == 0) {
//             $invoiceId = $payment_obj_invoice->id;

//             $command = "SendEmail";
//             $adminuser = "billing";
//             $values["customtype"] = "general";
//             $values["customsubject"] = "Ada Pembayaran Masuk ke Virtual Account Nicepay, Untuk No Invoice: " . $invoiceId . ", Txid:" . $tXid;
//             $values["custommessage"] = "Silakan di cek di dashboard Nicepay.";
//             $values["id"] = 641;
//             $results = (new HelperApi)->localAPI($command, $values, $adminuser);

//             // Pastikan untuk menambahkan argumen kelima yang diperlukan
//             $paymentmethod = 'nicepay'; // Ganti dengan metode pembayaran yang sesuai
//             InvoiceHelper::addInvoicePayment(
//                 $invoiceId,
//                 $tXid,
//                 $amount,
//                 '', // Payment Fee
//                 $paymentmethod
//             );
//         }

//         if ($payment_num_rows_invoice != 1) {
//             $invoicecount = Invoice::where(array("userid" => $userid, "status" => "Unpaid"));
//             $payment_obj_invoice_count = $invoicecount->first();
//             $payment_num_rows_invoice_count = $invoicecount->count();

//             if ($payment_num_rows_invoice_count == 1 && $payment_num_rows_transaction_count == 0) {
//                 $invoiceId = $payment_obj_invoice_count->id;
//                 $command1 = "SendEmail";
//                 $adminuser1 = "billing";
//                 $values1["customtype"] = "general";
//                 $values1["customsubject"] = "Ada Pembayaran Masuk ke Virtual Account Nicepay, TxID: " . $tXid . ". Tapi tidak ada Total yang sesuai, MOHON DI CEK SECARA TELITI ATAU KONFIRM KE CLIENT Apakah Masuk ke Invoice ini?: " . $invoiceId;
//                 $values1["custommessage"] = "Silakan di cek di dashboard Nicepay.";
//                 $values1["id"] = $clientIdNotification;
//                 $results1 = (new HelperApi)->localAPI($command1, $values1, $adminuser1);
//             }

//             if ($payment_num_rows_invoice_count != 1 && $payment_num_rows_transaction_count == 0) {
//                 $command2 = "SendEmail";
//                 $adminuser2 = "billing";
//                 $values2["customtype"] = "general";
//                 $values2["customsubject"] = "Ada Pembayaran Masuk ke Virtual Account Nicepay, Tapi tidak dikenali Invoicenya, TxID: " . $tXid . ", UserID: " . $userid . ", Amount: " . $amount;
//                 $values2["custommessage"] = "Silakan di cek di dashboard Nicepay.";
//                 $values2["id"] = $clientIdNotification;
//                 $results2 = (new HelperApi)->localAPI($command2, $values2, $adminuser2);
//             }
//         }

//         return response()->json([
//             'result' => 'success',
//             'message' => 'Ok',
//         ], 200);
//     } catch (\Exception $e) {
//         $message = "Callback Nicepay error: " . $e->getMessage();
//         Log::error($message);
//         return response()->json([
//             'result' => 'error',
//             'message' => $message,
//         ], 400);
//     }
// }

// public function va(Request $request)
// {
//     try {
//         $token = $request->header('x-callback-token');
//         $callback = json_decode(file_get_contents('php://input'), true);
//         Log::debug("===callback VA dibayar");
//         Log::debug($callback);

//         $tXid = $callback['tXid'];
//         $amount = $callback['amt'];
//         $referenceNo = $callback['referenceNo'];

//         Log::info("Received Nicepay notification", $request->all());

//         $userid = ltrim($referenceNo, '0');

//        $billingEmail = \DB::table('tblconfiguration')
//             ->where('setting', 'BillingNotificationReceiver')
//             ->value('value');

//         $template = \App\Models\Emailtemplate::where("type", "=", "invoice")
//             ->where("language", "=", "")
//             ->first();

//         $invoice = Invoice::whereRaw("userid = '$userid' AND (total = '$amount' OR total LIKE '" . $amount . ".%')")
//             ->where("status", "Unpaid")
//             ->first();

//         if ($invoice) {
//             $invoiceId = $invoice->id;
//             $clientIdNotification = $invoice->userid;
//             $paymentmethod = $invoice->paymentmethod;

//             $transactionExists = \App\Models\Account::where("transid", $tXid)->exists();

//             if (!$transactionExists) {
//                 $command = "SendEmail";
//                 $adminuser = "billing";
//                 $values["customtype"] = "general";
//                 $values["customsubject"] = "Ada Pembayaran Masuk ke Virtual Account Nicepay, Untuk No Invoice: " . $invoiceId . ", Txid:" . $tXid;
//                 $values["custommessage"] = "Silakan di cek di dashboard Nicepay.";
//                 $values["id"] = $clientIdNotification;
//                 $results = (new HelperApi)->localAPI($command, $values, $adminuser);

//                 // Kirim email menggunakan template
//                 Mail::send([], [], function ($message) use ($billingEmail, $template, $invoiceId, $tXid) {
//                     $message->to($billingEmail)
//                         ->subject("Ada Pembayaran Masuk ke Virtual Account Nicepay, Untuk No Invoice: " . $invoiceId)
//                         ->setBody($template->content, 'text/html'); // Asumsikan 'content' adalah kolom yang menyimpan isi email
//                 });

//                 InvoiceHelper::addInvoicePayment(
//                     $invoiceId,
//                     $tXid,
//                     $amount,
//                     '', // Payment Fee
//                     $paymentmethod
//                 );
//             }
//         } else {
//             $command2 = "SendEmail";
//             $adminuser2 = "billing";
//             $values2["customtype"] = "general";
//             $values2["customsubject"] = "Ada Pembayaran Masuk ke Virtual Account Nicepay, Tapi tidak dikenali Invoicenya, TxID: " . $tXid . ", UserID: " . $userid . ", Amount: " . $amount;
//             $values2["custommessage"] = "Silakan di cek di dashboard Nicepay.";
//             $values2["id"] = $userid;
//             $results2 = (new HelperApi)->localAPI($command2, $values2, $adminuser2);

//             // Kirim email menggunakan template
//             Mail::send([], [], function ($message) use ($billingEmail, $template, $tXid, $userid, $amount) {
//                 $message->to($billingEmail)
//                     ->subject("Ada Pembayaran Masuk ke Virtual Account Nicepay, Tapi tidak dikenali Invoicenya, TxID: " . $tXid)
//                     ->setBody($template->content, 'text/html'); // Asumsikan 'content' adalah kolom yang menyimpan isi email
//             });
//         }

//         return response()->json([
//             'result' => 'success',
//             'message' => 'Ok',
//         ], 200);
//     } catch (\Exception $e) {
//         $message = "Callback Nicepay error: " . $e->getMessage();
//         Log::error($message);
//         return response()->json([
//             'result' => 'error',
//             'message' => $message,
//         ], 400);
//     }
// }

    public function va(Request $request)
    {
        try {
            $token = $request->header('x-callback-token');
            $callback = json_decode(file_get_contents('php://input'), true);
            Log::debug("===callback VA dibayar");
            Log::debug($callback);

            $tXid = $callback['tXid'];
            $amount = $callback['amt'];
            $referenceNo = $callback['referenceNo'];

            Log::info("Received Nicepay notification", $request->all());

            // $userid = ltrim($referenceNo, '0');
            // $userid = ltrim(str_replace(['2', '0'], '', $referenceNo), '0');

            // preg_match('/^[0-9]0*(\d+)$/', $referenceNo, $matches);
            // $userid = $matches[1] ?? ltrim($referenceNo, '0');

            // Pastikan user id selalu terisi; fallback ke trim nol jika pola tidak cocok
            if (preg_match('/2000(\d+)/', $referenceNo, $matches) && !empty($matches[1])) {
                $userid = ltrim($matches[1], '0');
            } elseif (strpos($referenceNo, '2') === 0) {
                $userid = ltrim(substr($referenceNo, 1), '0');
            } else {
                $userid = ltrim($referenceNo, '0');
            }
            if (empty($userid)) {
                Log::error("Nicepay VA: gagal parse userid dari referenceNo", ['referenceNo' => $referenceNo]);
                return response()->json([
                    'result' => 'error',
                    'message' => 'UserID tidak dapat diparsing dari referenceNo',
                ], 400);
            }

            Log::info("Extracted UserID: ", ['userid' => $userid]);

            // Ambil string email dari database
            $billingEmail = \DB::table('tblconfiguration')
                ->where('setting', 'BillingNotificationReceiver')
                ->value('value');

            // Pisahkan string menjadi array dan validasi setiap email
            $billingEmails = array_filter(array_map('trim', explode(',', $billingEmail)), function ($email) {
                return filter_var($email, FILTER_VALIDATE_EMAIL);
            });

            // Log untuk memastikan array email tidak kosong
            Log::debug("Billing emails: ", $billingEmails);

            if (empty($billingEmails)) {
                Log::error("No valid email addresses found for billing notifications.");
                return response()->json([
                    'result' => 'error',
                    'message' => 'No valid email addresses found.',
                ], 400);
            }

            $template = \App\Models\Emailtemplate::where("type", "=", "invoice")
                ->where("language", "=", "")
                ->first();

            $invoice = Invoice::whereRaw("userid = '$userid' AND (total = '$amount' OR total LIKE '" . $amount . ".%')")
                ->where("status", "Unpaid")
                ->first();

            if ($invoice) {
                $invoiceId = $invoice->id;
                $clientIdNotification = $invoice->userid;
                $paymentmethod = $invoice->paymentmethod;

                $transactionExists = \App\Models\Account::where("transid", $tXid)->exists();

                if (!$transactionExists) {
                    $command = "SendEmail";
                    $adminuser = "billing";
                    $values["customtype"] = "general";
                    $values["customsubject"] = "Ada Pembayaran Masuk ke Virtual Account Nicepay, Untuk No Invoice: " . $invoiceId . ", Txid:" . $tXid;
                    $values["custommessage"] = "Silakan di cek di dashboard Nicepay.";
                    $values["id"] = $clientIdNotification;
                    $results = (new HelperApi)->localAPI($command, $values, $adminuser);

                    // Log before sending email
                    Log::info("Attempting to send email to: ", $billingEmails);

                    // Kirim email menggunakan template
                    Mail::send([], [], function ($message) use ($billingEmails, $template, $invoiceId, $tXid) {
                        if (!empty($billingEmails)) {
                            $message->to($billingEmails)
                                ->subject("Ada Pembayaran Masuk ke Virtual Account Nicepay, Untuk No Invoice: " . $invoiceId)
                                ->setBody($template->content, 'text/html'); // Asumsikan 'content' adalah kolom yang menyimpan isi email
                        } else {
                            Log::error("No valid email addresses to send.");
                        }
                    });

                    // Kirim email menggunakan template
                    // Mail::send([], [], function ($message) use ($billingEmails, $template, $invoiceId, $tXid) {
                    //     $message->to($billingEmails)
                    //         ->subject("Ada Pembayaran Masuk ke Virtual Account Nicepay, Untuk No Invoice: " . $invoiceId)
                    //         ->setBody($template->content, 'text/html'); // Asumsikan 'content' adalah kolom yang menyimpan isi email
                    // });

                    Log::info("Email sent successfully to: ", $billingEmails);

                    InvoiceHelper::addInvoicePayment(
                        $invoiceId,
                        $tXid,
                        $amount,
                        '', // Payment Fee
                        $paymentmethod
                    );
                }
            } else {
                $command2 = "SendEmail";
                $adminuser2 = "billing";
                $values2["customtype"] = "general";
                $values2["customsubject"] = "Ada Pembayaran Masuk ke Virtual Account Nicepay, Tapi tidak dikenali Invoicenya, TxID: " . $tXid . ", UserID: " . $userid . ", Amount: " . $amount;
                $values2["custommessage"] = "Silakan di cek di dashboard Nicepay.";
                $values2["id"] = $userid;
                $results2 = (new HelperApi)->localAPI($command2, $values2, $adminuser2);

                // Log before sending email
                Log::info("Attempting to send email to: ", $billingEmails);

                // Kirim email menggunakan template
                Mail::send([], [], function ($message) use ($billingEmails, $template, $tXid, $userid, $amount) {
                    if (!empty($billingEmails)) {
                        $message->to($billingEmails)
                            ->subject("Ada Pembayaran Masuk ke Virtual Account Nicepay, Tapi tidak dikenali Invoicenya, TxID: " . $tXid)
                            ->setBody($template->content, 'text/html'); // Asumsikan 'content' adalah kolom yang menyimpan isi email
                    } else {
                        Log::error("No valid email addresses to send.");
                    }
                });

                Log::info("Email sent successfully to: ", $billingEmails);
            }

            return response()->json([
                'result' => 'success',
                'message' => 'Ok',
            ], 200);
        } catch (\Exception $e) {
            $message = "Callback Nicepay error: " . $e->getMessage();
            Log::error($message);
            return response()->json([
                'result' => 'error',
                'message' => $message,
            ], 400);
        }
    }

    public function vacreated(Request $request)
    {
        $token = $request->header('x-callback-token');
        $callback = json_decode(file_get_contents('php://input'), true);
        Log::debug("===callback VA dibuat dan diperbarui");
        Log::debug($callback);

        return response()->json(['result' => 'success'], 200);
    }

    public function ewallet(Request $request)
    {
        try {
            $token = $request->header('x-callback-token');
            $callback = json_decode(file_get_contents('php://input'), true);
            Log::debug("===callback ewallet");
            Log::debug($callback);

            $invoiceid = $callback['data']['metadata']['invoiceid'];
            $userid = $callback['data']['metadata']['userid'];
            $gateway = $callback['data']['metadata']['gateway'];
            $customsubject = $callback['data']['metadata']['customsubject'];
            $status = $callback['data']['status'];
            $amount = $callback['data']['charge_amount'];
            $tXid = $callback['data']['reference_id'];

            $params = Gateway::getGatewayVariables($gateway);
            $testMode = $params["testMode"];
            $clientIdNotification = $params["clientId"];
            $secretKey = $testMode ? $params["secretKeyTest"] : $params["secretKeyLive"];
            $tokenModule = $testMode ? $params["tokenCallbackTest"] : $params["tokenCallbackLive"];

            if ($token != $tokenModule) {
                throw new \Exception("Callback token is not valid");
            }

            if ($status != "SUCCEEDED") {
                throw new \Exception("Status not SUCCEEDED");
            }

            Gateway::logTransaction($gateway, $callback, $status);
            $transaction = \App\Models\Account::where("transid", $tXid);
            $payment_num_rows_transaction_count = $transaction->count();
            if ($payment_num_rows_transaction_count <= 0) {
                $values["customtype"] = "general";
                $values["customsubject"] = "$customsubject, Untuk No Invoice: " . $invoiceid . ", Txid:" . $tXid;
                $values["custommessage"] = "Silakan di cek di dashboard Nicepay.";
                $values["id"] = $clientIdNotification;
                (new HelperApi)->localAPI('SendEmail', $values);

                InvoiceHelper::addInvoicePayment(
                    $invoiceid,
                    $tXid,
                    $amount,
                    '', //Payment Fee
                    $gateway
                );

                return response()->json([
                    'result' => 'success',
                    'message' => "No.Invoice #$invoiceid",
                ], 200);
            }
        } catch (\Exception $e) {
            $message = "Callback Nicepay ewallet error: " . $e->getMessage();
            Log::error($message);
            return response()->json([
                'result' => 'error',
                'message' => $message,
            ], 400);
        }
    }

    public function retail(Request $request)
    {
        try {
            $token = $request->header('x-callback-token');
            $callback = json_decode(file_get_contents('php://input'), true);
            Log::debug("===callback retail");
            Log::debug($callback);

            $id = $callback['id'];
            $external_id = $callback['external_id'];
            $retail_outlet_name = $callback['retail_outlet_name'];
            $amount = $callback['amount'];
            $status = $callback['status'];
            $tXid = $id;

            $gatewaysInterface = new \App\Helpers\Gateways();
            $gateway_name = "";

            switch ($retail_outlet_name) {
                case 'ALFAMART':
                    $gateway = "alfamartnicepay";
                    $gateway_name = $gatewaysInterface->getDisplayName($gateway);
                    break;
                case 'INDOMARET':
                    $gateway = "indomaretnicepay";
                    $gateway_name = $gatewaysInterface->getDisplayName($gateway);
                    break;

                default:
                    throw new \Exception("Gateway module not found using retail_outlet_name $retail_outlet_name");
                    break;
            }

            $params = Gateway::getGatewayVariables($gateway);
            $testMode = $params["testMode"];
            $clientIdNotification = $params["clientId"];
            $secretKey = $testMode ? $params["secretKeyTest"] : $params["secretKeyLive"];
            $tokenModule = $testMode ? $params["tokenCallbackTest"] : $params["tokenCallbackLive"];

            if ($token != $tokenModule) {
                throw new \Exception("Callback token is not valid");
            }

            Gateway::logTransaction($gateway, $callback, $status);
            $transaction = \App\Models\Account::where("transid", $tXid);
            $payment_num_rows_transaction_count = $transaction->count();
            if ($payment_num_rows_transaction_count <= 0) {
                $values["customtype"] = "general";
                $values["customsubject"] = "Ada Pembayaran Masuk ke $gateway_name Nicepay, Untuk No Invoice: " . $external_id . ", Txid:" . $tXid;
                $values["custommessage"] = "Silakan di cek di dashboard Nicepay.";
                $values["id"] = $clientIdNotification;
                (new HelperApi)->localAPI('SendEmail', $values);

                InvoiceHelper::addInvoicePayment(
                    $external_id,
                    $tXid,
                    $amount,
                    '', //Payment Fee
                    $gateway
                );

                return response()->json([
                    'result' => 'success',
                    'message' => "No.Invoice #$external_id",
                ], 200);
            }
        } catch (\Exception $e) {
            $message = "Callback Nicepay retail error: " . $e->getMessage();
            Log::error($message);
            return response()->json([
                'result' => 'error',
                'message' => $message,
            ], 400);
        }
    }
}

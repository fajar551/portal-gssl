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

class NicepayController extends Controller
{
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
                'Authorization: Basic '.base64_encode("$token:"),
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return json_decode($response);
    }

    public function va(Request $request)
    {
        try {
            $token = $request->header('x-callback-token');
            $callback = json_decode(file_get_contents('php://input'), true);
            // \Log::debug("===callback VA dibayar");
            // \Log::debug($callback);
            $payment_id = $callback['payment_id'];
            $tXid = $payment_id;
            $amount = $callback['amount'];
            $external_id = $callback['external_id'];
            // $referenceNo = $callback['referenceNo'];

            // Log::info("Received Nicepay notification", $request->all());

            // $userid = ltrim($referenceNo, '0');

            $bank_code = $callback['bank_code'];

            // check gateways variable based on bank code
            switch ($bank_code) {
                case 'CIMB':
                    $paymentmethod = "cimbvaxendit";
                break;
                case 'BCA':
                    $paymentmethod = "bcavaxendit";
                break;
                case 'BRI':
                    $paymentmethod = "brivaxendit";
                break;
                case 'MANDIRI':
                    $paymentmethod = "mandirivaxendit";
                break;
                case 'BNI':
                    $paymentmethod = "bnivaxendit";
                break;
                case 'PERMATA':
                    $paymentmethod = "permatabankvaxendit";
                break;
                case 'SAHABAT_SAMPOERNA':
                    $paymentmethod = "sampoernavaxendit";
                break;
                    
                default:
                    throw new \Exception("Gateway module not found using bank code $bank_code");
                break;
            }
            
            $paymentmethoddynamicarray = array('cimbvaxendit','bcavaxendit','brivaxendit','mandirivaxendit','bnivaxendit','permatabankvaxendit','sampoernavaxendit');
                
            // get params
            $params = \App\Helpers\Gateway::getGatewayVariables($paymentmethod);
            $testMode = $params["testMode"];
            $isFixed = $params["isFixed"];
            $clientIdNotification = $params["clientId"];
            $secretKey = $testMode ? $params["secretKeyTest"] : $params["secretKeyLive"];
            $tokenModule = $testMode ? $params["tokenCallbackTest"] : $params["tokenCallbackLive"];

            // check module fixed or not
            if ($isFixed) {
                // cek payment id
                $response = $this->call("get", "https://api.xendit.co/callback_virtual_account_payments/payment_id=$payment_id", $secretKey, []);
                // \Log::debug("===callback VA cek payment id");
                // \Log::debug(json_decode(json_encode($response), true));
                if (isset($response->error_code) && !$testMode) {
                    throw new \Exception($response->message);
                }

                // jika testMode payment id nya sama
                $payment_idcompare = $testMode ? $payment_id : ($response->payment_id ?? $payment_id);
                \App\Helpers\Gateway::logTransaction('Xendit Virtual Account', $callback, $payment_idcompare);
                if ($payment_idcompare == $payment_id) {
                    $external_idtrimexplode = explode("-",$external_id);
                    $kodebank = $external_idtrimexplode[0];
                    $external_idtrim = $external_idtrimexplode[1];
                    $userid = ltrim($external_idtrim, '0');

                    // cek invoice
                    $invoice = \App\Models\Invoice::whereRaw("userid = '$userid' AND (total = '$amount' OR total LIKE '".$amount.".%')")
                    ->where("status", "Unpaid");
                    $payment_obj_invoice = $invoice->first();
                    $payment_num_rows_invoice = $invoice->count();

                    // cek transaction
                    $transaction = \App\Models\Account::where("transid", $tXid);
                    $payment_num_rows_transaction_count = $transaction->count();

                    // true invoice nya 1
                    if ($payment_num_rows_invoice == 1 && $payment_num_rows_transaction_count == 0) {
                        $invoiceId = $payment_obj_invoice->id;

                        $command = "SendEmail";
                        $adminuser = "billing";
                        $values["customtype"] = "general";
                        $values["customsubject"] = "Ada Pembayaran Masuk ke Virtual Account Xendit Relabs, Untuk No Invoice: ".$invoiceId.", Txid:".$tXid;
                        $values["custommessage"] = "Silakan di cek di https://dashboard.xendit.co/</nowiki>";
                        $values["id"]            = $clientIdNotification;
                        $results = (new \App\Helpers\HelperApi)->localAPI($command, $values, $adminuser);

                        // $systemHelper = new \App\Helpers\SystemHelper();    
                        // $systemHelper->sendEmail($values);
                        
                        \App\Helpers\Invoice::addInvoicePayment(
                            $invoiceId,

                            $tXid,
                            $amount,
                            '',//Payment Fee
                            $paymentmethod
                        );
                    }

                    // masuk tp invoicenya ga dikenali
                    if ($payment_num_rows_invoice != 1) {
                        $invoicecount = \App\Models\Invoice::where(array("userid" => $userid, "status" => "Unpaid"));
                        $payment_obj_invoice_count = $invoicecount->first();
                        $payment_num_rows_invoice_count = $invoicecount->count();

                        if ($payment_num_rows_invoice_count == 1 && $payment_num_rows_transaction_count == 0) {
                            $invoiceId = $payment_obj_invoice_count->id;
                            $command1 = "SendEmail";
                            $adminuser1 = "billing";
                            $values1["customtype"] = "general";
                            $values1["customsubject"] = "Ada Pembayaran Masuk ke Virtual Account Xendit Relabs, TxID: ".$tXid.". Tapi tidak ada Total yang sesuai, MOHON DI CEK SECARA TELITI ATAU KONFIRM KE CLIENT Apakah Masuk ke Invoice ini?: ".$invoiceId;
                            $values1["custommessage"] = "Silakan di cek di https://dashboard.xendit.co/</nowiki>";
                            $values1["id"]            = $clientIdNotification;
                            $results1 = (new \App\Helpers\HelperApi)->localAPI($command1, $values1, $adminuser1);
                        }

                        if ($payment_num_rows_invoice_count != 1 && $payment_num_rows_transaction_count == 0) {
                            $command2 = "SendEmail";
                            $adminuser2 = "billing";
                            $values2["customtype"] = "general";
                            $values2["customsubject"] = "Ada Pembayaran Masuk ke Virtual Account Xendit Relabs, Tapi tidak dikenali Invoicenya, TxID: ".$tXid.", UserID: ".$userid.", Amount: ".$amount;
                            $values2["custommessage"] = "Silakan di cek di https://dashboard.xendit.co/</nowiki>";
                            $values2["id"]            = $clientIdNotification;
                            $results2 = (new \App\Helpers\HelperApi)->localAPI($command2, $values2, $adminuser2);
                        }
                    }
                }
            } else {
                // if not fixed module
                $external_id_x = explode('-', $external_id);
                $invoiceid = array_key_exists(0, $external_id_x) ? $external_id_x[0] : 0;
                $activeinvoice = false;
                try {
                    $invoice = new \App\Helpers\InvoiceClass($invoiceid);
                    $activeinvoice = true;
                } catch (\Exception $e) {
                    $activeinvoice = false;
                }

                if ($activeinvoice) {
                    $invoiceparams = $invoice->getGatewayInvoiceParams();
                    $clientdetails = $invoiceparams['clientdetails'];
                    $userid = $clientdetails['userid'];
                    $gateway = $invoiceparams['paymentmethod'];

                    // gateways helper
                    $gatewaysInterface = new \App\Helpers\Gateways();
                    $gateway_name = $gatewaysInterface->getDisplayName($gateway);;

                    $payment_num_rows_transaction_count = \App\Models\Account::where(['userid' => $userid, 'transid' => $tXid])->count();
                    if ($payment_num_rows_transaction_count <= 0) {
                        $values["customtype"] = "general";
                        $values["customsubject"] = "Ada Pembayaran Masuk ke $gateway_name Xendit Relabs, Untuk No Invoice: ".$invoiceid.", Txid:".$tXid;
                        $values["custommessage"] = "Silakan di cek di https://dashboard.xendit.co/</nowiki>";
                        $values["id"] = $clientIdNotification;
                        $result = (new \App\Helpers\HelperApi)->localAPI('SendEmail', $values);
        
                        \App\Helpers\Invoice::addInvoicePayment(
                            $invoiceid,
                            $tXid,
                            $amount,
                            '',//Payment Fee
                            $gateway
                        );
                    } else {
                        return response()->json([
                            'result' => 'success',
                            'message' => "Transaksi dengan id $tXid sudah pernah dilakukan",
                            'fixed' => $isFixed ? true : false,
                        ], 200);
                    }
                } else {
                    throw new \Exception("Invoice not found using external id $external_id");
                }
            }

            return response()->json([
                'result' => 'success',
                'message' => 'Ok',
                'fixed' => $isFixed ? true : false,
            ], 200);
        } catch (\Exception $e) {
            $message = "Callback xendit va error: ". $e->getMessage();
            \App\Helpers\LogActivity::Save($message);
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
        // \Log::debug("===callback VA dibuat dan diperbarui");
        // \Log::debug($callback);

        return response()->json(['result' => 'success'], 200);
    }

    public function ewallet(Request $request)
    {
        try {
            $token = $request->header('x-callback-token');
            $callback = json_decode(file_get_contents('php://input'), true);
            // \Log::debug("===callback ewallet");
            // \Log::debug($callback);

            $invoiceid = $callback['data']['metadata']['invoiceid'];
            $userid = $callback['data']['metadata']['userid'];
            $gateway = $callback['data']['metadata']['gateway'];
            $customsubject = $callback['data']['metadata']['customsubject'];
            $status = $callback['data']['status'];
            $amount = $callback['data']['charge_amount'];
            $tXid = $callback['data']['reference_id'];

            $params = \App\Helpers\Gateway::getGatewayVariables($gateway);
            $testMode = $params["testMode"];
            $clientIdNotification = $params["clientId"];
            $secretKey = $testMode ? $params["secretKeyTest"] : $params["secretKeyLive"];
            $tokenModule = $testMode ? $params["tokenCallbackTest"] : $params["tokenCallbackLive"];

            // verify token
            if ($token != $tokenModule) {
                throw new \Exception("Callback token is not valid");
            }

            // check status
            if ($status != "SUCCEEDED") {
                throw new \Exception("Status not SUCCEEDED");
            }
            
            \App\Helpers\Gateway::logTransaction($gateway, $callback, $status);
            $transaction = \App\Models\Account::where("transid", $tXid);
            $payment_num_rows_transaction_count = $transaction->count();
            if ($payment_num_rows_transaction_count <= 0) {
                $values["customtype"] = "general";
                $values["customsubject"] = "$customsubject, Untuk No Invoice: ".$invoiceid.", Txid:".$tXid;
                $values["custommessage"] = "Silakan di cek di https://dashboard.xendit.co/</nowiki>";
                $values["id"] = $clientIdNotification;
                // (new \App\Helpers\HelperApi)->localAPI('SendEmail', $values);
                $systemHelper = new \App\Helpers\SystemHelper();    
                $systemHelper->sendEmail($values);
                
                \App\Helpers\Invoice::addInvoicePayment(
                    $invoiceid,
                    $tXid,
                    $amount,
                    '',//Payment Fee
                    $gateway
                );

                return response()->json([
                    'result' => 'success',
                    'message' => "No.Invoice #$invoiceid",
                ], 200);
            }
        } catch (\Exception $e) {
            $message = "Callback xendit ewallet error: ". $e->getMessage();
            \App\Helpers\LogActivity::Save($message);
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
            // \Log::debug("===callback retail");
            // \Log::debug($callback);

            // callback vars
            $id = $callback['id'];
            $external_id = $callback['external_id'];
            $prefix = $callback['prefix'];
            $payment_code = $callback['payment_code'];
            $retail_outlet_name = $callback['retail_outlet_name'];
            $name = $callback['name'];
            $amount = $callback['amount'];
            $status = $callback['status'];
            $transaction_timestamp = $callback['transaction_timestamp'];
            $payment_id = $callback['payment_id'];
            $fixed_payment_code_payment_id = $callback['fixed_payment_code_payment_id'];
            $fixed_payment_code_id = $callback['fixed_payment_code_id'];
            $owner_id = $callback['owner_id'];

            // vars
            $tXid = $id;
            $invoiceid = $external_id;

            // gateways helper
            $gatewaysInterface = new \App\Helpers\Gateways();
            $gateway_name = "";

            // which gateway
            switch ($retail_outlet_name) {
                case 'ALFAMART':
                    $gateway = "alfamartxendit";
                    $gateway_name = $gatewaysInterface->getDisplayName($gateway);
                break;
                case 'INDOMARET':
                    $gateway = "indomaretxendit";
                    $gateway_name = $gatewaysInterface->getDisplayName($gateway);
                break;
                
                default:
                    throw new \Exception("Gateway module not found using retail_outlet_name $retail_outlet_name");
                break;
            }

            // get params
            $params = \App\Helpers\Gateway::getGatewayVariables($gateway);
            $testMode = $params["testMode"];
            $clientIdNotification = $params["clientId"];
            $secretKey = $testMode ? $params["secretKeyTest"] : $params["secretKeyLive"];
            $tokenModule = $testMode ? $params["tokenCallbackTest"] : $params["tokenCallbackLive"];

            // verify token
            if ($token != $tokenModule) {
                throw new \Exception("Callback token is not valid");
            }

            // check status
            /* if ($status != "PAID") {
                throw new \Exception("Status not COMPLETED");
            }
            */
            \App\Helpers\Gateway::logTransaction($gateway, $callback, $status);
            $transaction = \App\Models\Account::where("transid", $tXid);
            $payment_num_rows_transaction_count = $transaction->count();
            if ($payment_num_rows_transaction_count <= 0) {
                $values["customtype"] = "general";
                $values["customsubject"] = "Ada Pembayaran Masuk ke $gateway_name Xendit Relabs, Untuk No Invoice: ".$invoiceid.", Txid:".$tXid;
                $values["custommessage"] = "Silakan di cek di https://dashboard.xendit.co/</nowiki>";
                $values["id"] = $clientIdNotification;
                // (new \App\Helpers\HelperApi)->localAPI('SendEmail', $values);
                
                $systemHelper = new \App\Helpers\SystemHelper();    
                $systemHelper->sendEmail($values);

                \App\Helpers\Invoice::addInvoicePayment(
                    $invoiceid,
                    $tXid,
                    $amount,
                    '',//Payment Fee
                    $gateway
                );

                return response()->json([
                    'result' => 'success',
                    'message' => "No.Invoice #$invoiceid",
                ], 200);
            }
        } catch (\Exception $e) {
            $message = "Callback xendit retail error: ". $e->getMessage();
            \App\Helpers\LogActivity::Save($message);
            return response()->json([
                'result' => 'error',
                'message' => $message,
            ], 400);
        }
    }
}
<?php

namespace Modules\Addons\CbmsConvenienceFee\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Modules\Addons\CbmsConvenienceFee\Models\CbmsConvenienceFees;
use Illuminate\Support\Str;
use App\Helpers\ResponseAPI;
use Validator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CbmsConvenienceFeeController extends Controller
{
    public function config()
    {
        return [
            'name' => 'CBMS Convenience Fee',
            'description' => 'Module ini digunakan untuk mengatur CF (Convenience Fee) secara dinamis',
            'author' => 'CBMS Developer',
            'language' => 'english',
            'version' => '1.0',
            'fields' => [
                // 'restricted' => [
                //     'FriendlyName' => 'Enable Restricted CF',
                //     'Type' => 'yesno',
                //     'Description' => 'Tick to enable',
                // ],
            ],
        ];
    }

    public function activate()
    {
        // https://nwidart.com/laravel-modules/v6/advanced-tools/artisan-commands
        try {
            // call artisan
            // Artisan::call("module:migrate CbmsConvenienceFee");
            Schema::create('cbms_convenience_fees', function (Blueprint $table) {
                $table->id();
                $table->string('paymentmethod')->nullable();
                $table->integer('fixed_amount')->nullable();
                $table->integer('percentage_amount')->nullable();
                $table->timestamps();
            });

            return [
                // Supported values here include: success, error or info
                'status' => 'success',
                'description' => 'Module enabled '
            ];
        } catch (\Exception $e) {
            return [
                // Supported values here include: success, error or info
                'status' => "error",
                'description' => 'Unable to create your module: ' . $e->getMessage(),
            ];
        }
    }

    public function deactivate()
    {
        // undo ny action you created in activate function
        // https://nwidart.com/laravel-modules/v6/advanced-tools/artisan-commands
        try {
            // Artisan::call("module:migrate-rollback CbmsConvenienceFee");
            Schema::dropIfExists('cbms_convenience_fees');
    
            return [
                // Supported values here include: success, error or info
                'status' => 'success',
                'description' => 'Module has been disabled.',
            ];
        } catch (\Exception $e) {
            return [
                // Supported values here include: success, error or info
                "status" => "error",
                "description" => "Unable to drop your module: {$e->getMessage()}",
            ];
        }
    }

    public function output($vars)
    {
        $gatewayInterface = new \App\Module\Gateway();
        $ActiveGateways = $gatewayInterface->getActiveGateways();
        $ActiveGatewaysData = [];
        foreach ($ActiveGateways as $gateway) {
            $ActiveGatewaysData[] = $gateway->getName();
        }
        $result3 = \App\Models\Paymentgateway::where(array("setting" => "name"))->whereIn('gateway', $ActiveGatewaysData)->orderBy('order', 'ASC')->get();
        $result3->transform(function($pg) {
            $pg->gateway = Str::studly($pg->gateway);
            return $pg;
        });
        $result3 = $result3->toArray();
        $activeGateway = $result3;

        // get all cf
        $cfs = CbmsConvenienceFees::all();
        $cfsdata = [];
        foreach ($cfs as $cf) {
            $cfsdata[$cf->paymentmethod] = [
                'fixed_amount' => $cf->fixed_amount ?? "",
                'percentage_amount' => $cf->percentage_amount ?? "",
            ];
        }

        return view('cbmsconveniencefee::index', compact('activeGateway', 'cfsdata'));
    }

    public function save(Request $request)
    {
        $rules = [
            'fixed_price.*' => ['nullable', 'integer', 'min:0'],
            'percentage_price.*' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    
        $validator = Validator::make($request->all(), $rules);
    
        if ($validator->fails()) {
            $error = $validator->errors()->first();
    
            return ResponseAPI::Error([
                'message' => $error,
            ]);
        }

        $fixed_price = $request->input('fixed_price') ?? [];
        $percentage_price = $request->input('percentage_price') ?? [];
        
        // fixed price
        foreach ($fixed_price as $module => $amount) {
            CbmsConvenienceFees::updateOrCreate(
                [
                    'paymentmethod' => $module,
                ],
                [
                    'fixed_amount' => $amount,
                ],
            );
        }

        // percentage price
        foreach ($percentage_price as $module => $amount) {
            CbmsConvenienceFees::updateOrCreate(
                [
                    'paymentmethod' => $module,
                ],
                [
                    'percentage_amount' => $amount,
                ],
            );
        }

        return ResponseAPI::Success([
            'message' => 'Convenience Fee Setting Saved',
            'data' => $request->all(),
        ]);
    }

    public function insertCF($invoiceid = 0, $paymentmethod = "")
    {
        $type = CbmsConvenienceFees::CF_TYPE;
        $description = "Convenience Fee ";
        $taxed = 1;
        $subtotal = 0;
        $total = 0;

        $invoice = \App\Models\Invoice::find($invoiceid);
        if ($invoice) {
            $invoiceClass = new \App\Helpers\InvoiceClass($invoiceid);

            // get invoice payment method
            // $paymentmethod = $invoice->paymentmethod;
            $paymentmethod = Str::studly($paymentmethod);

            // get subtotal
            foreach ($invoiceClass->getLineItems(true) as $item) {
                // jangan ambil yg sudah ada cf
                if ($item['type'] != $type) {
                    $subtotal += $item['amount']->toNumeric();
                }
            }
            // \Log::debug("subtotal items: $subtotal");

            // get cf configs
            $fixed_amount = 0;
            $percentage_amount = 0;
            $cf = CbmsConvenienceFees::where('paymentmethod', $paymentmethod)->first();
            if ($cf) {
                // \Log::debug(json_encode($cf));
                $fixed_amount = $cf->fixed_amount ?? 0;
                $percentage_amount = $cf->percentage_amount ?? 0;
            }

            // get invoice value
            $userid = $invoice->userid;

            // the logic
            $description .= $invoiceClass->getData("paymentGatewayName");
            $description .= " (";
            $subtotalPP = 0;
            if ($percentage_amount) {
                $amountPercentage = $percentage_amount/100;
                $subtotalPP = $subtotal * ($amountPercentage);
                $description .= "$percentage_amount%";
            }

            $subtotalFP = 0;
            if ($fixed_amount) {
                $description .= " + $fixed_amount";
                // $subtotal = $subtotal + $fixed_amount;
                $subtotalFP = $fixed_amount;
            }

            if ($fixed_amount || $percentage_amount) {
                $total = $subtotalPP + $subtotalFP;
            }

            // insert data
            $description .= ")";
            $amount = $total;
            $nextduedate = \Carbon\Carbon::now()->format('Y-m-d');
            $data = array("invoiceid" => $invoiceid, "userid" => $userid, "type" => $type, "relid" => 0, "description" => $description, "amount" => $amount, "taxed" => $taxed, "duedate" => $nextduedate, "paymentmethod" => $paymentmethod);
            $where = ["invoiceid" => $invoiceid, "userid" => $userid, "type" => $type];
            // \Log::debug($data);
            // \Log::debug("Sub Total: $subtotal");
            // \Log::debug("Total: $total");

            if ($fixed_amount || $percentage_amount) {
                // cek jika salah satu itemnya ada cf
                $cfExists = \App\Models\Invoiceitem::where($where)->first();
                if (!$cfExists) {
                    \App\Models\Invoiceitem::insert($data);
                    \App\Helpers\Invoice::UpdateInvoiceTotal($invoiceid);
                }
            }
        }
    }
}

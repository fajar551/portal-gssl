<?php

namespace Modules\Addons\CbmsConvenienceFee\Listeners;

use App\Events\InvoiceChangeGateway;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Addons\CbmsConvenienceFee\Models\CbmsConvenienceFees;

class UpdateCFToInvoiceItems
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param InvoiceChangeGateway $event
     * @return void
     */
    public function handle(InvoiceChangeGateway $event)
    {
        \Log::info("UpdateCFToInvoiceItems called");
        $invoiceid = $event->invoiceid ?? 0;
        $paymentmethod = $event->paymentmethod ?? "";

        $invoice = \App\Models\Invoice::find($invoiceid);
        if ($invoice) {
            // $invoiceClass = new \App\Helpers\InvoiceClass($invoiceid);

            // get invoice payment method
            // $paymentmethod = $invoice->paymentmethod;

            // delete old CF
            $fixed_amount = 0;
            $percentage_amount = 0;
            $cf = CbmsConvenienceFees::where('paymentmethod', $paymentmethod)->first();
            if ($cf) {
                // \Log::debug(json_encode($cf));
                $fixed_amount = $cf->fixed_amount ?? 0;
                $percentage_amount = $cf->percentage_amount ?? 0;
            }

            \App\Models\Invoiceitem::where([
                'invoiceid' => $invoiceid,
                'type' => CbmsConvenienceFees::CF_TYPE,
            ])->delete();
            \App\Helpers\Invoice::UpdateInvoiceTotal($invoiceid);
            // if ($fixed_amount || $percentage_amount) {
            // }

            (new \Modules\Addons\CbmsConvenienceFee\Http\Controllers\CbmsConvenienceFeeController())->insertCF($invoiceid, $paymentmethod);
        }
    }
}

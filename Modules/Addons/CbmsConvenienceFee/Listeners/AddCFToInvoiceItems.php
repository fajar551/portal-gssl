<?php

namespace Modules\Addons\CbmsConvenienceFee\Listeners;

use App\Events\InvoiceCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Addons\CbmsConvenienceFee\Models\CbmsConvenienceFees;

class AddCFToInvoiceItems
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
     * @param InvoiceCreated $event
     * @return void
     */
    public function handle(InvoiceCreated $event)
    {
        //
        // \Log::info("AddCFToInvoiceItems called");
        $invoiceid = $event->invoiceid;

        $invoice = \App\Models\Invoice::find($invoiceid);
        // \Log::debug(json_encode($event));
        if ($invoice) {
            $invoiceClass = new \App\Helpers\InvoiceClass($invoiceid);

            // get invoice payment method
            $paymentmethod = $invoice->paymentmethod;

            (new \Modules\Addons\CbmsConvenienceFee\Http\Controllers\CbmsConvenienceFeeController())->insertCF($invoiceid, $paymentmethod);
        }
    }
}

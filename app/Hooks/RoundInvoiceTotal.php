<?php

// namespace App\Hooks;

// use App\Events\InvoiceCreated;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Log;

// class RoundInvoiceTotal
// {
//     public function handle(InvoiceCreated $event)
//     {
//         $invoiceId = $event->invoiceid;
        
//         Log::info('RoundInvoiceTotal handler dipanggil', ['invoice_id' => $invoiceId]);

//         try {
//             // Check current values first
//             $current = DB::table('tblinvoices')
//                 ->where('id', $invoiceId)
//                 ->first();

//             // Only update if values are different
//             if ($current->subtotal != 531050.00 || 
//                 $current->tax != 58415.50 || 
//                 $current->total != 589500.00) {

//                 DB::beginTransaction();
                
//                 try {
//                     // Direct update with transaction
//                     DB::statement("
//                         UPDATE tblinvoices 
//                         SET subtotal = 531050.00,
//                             tax = 58415.50,
//                             total = 589500.00
//                         WHERE id = ? 
//                         AND (subtotal != 531050.00 
//                              OR tax != 58415.50 
//                              OR total != 589500.00)
//                     ", [$invoiceId]);

//                     DB::commit();

//                     Log::info('Invoice values updated', [
//                         'invoice_id' => $invoiceId,
//                         'old_values' => [
//                             'subtotal' => $current->subtotal,
//                             'tax' => $current->tax,
//                             'total' => $current->total
//                         ],
//                         'new_values' => [
//                             'subtotal' => 531050.00,
//                             'tax' => 58415.50,
//                             'total' => 589500.00
//                         ]
//                     ]);
//                 } catch (\Exception $e) {
//                     DB::rollBack();
//                     throw $e;
//                 }
//             } else {
//                 Log::info('Invoice values already correct, skipping update', [
//                     'invoice_id' => $invoiceId,
//                     'values' => [
//                         'subtotal' => $current->subtotal,
//                         'tax' => $current->tax,
//                         'total' => $current->total
//                     ]
//                 ]);
//             }

//         } catch (\Exception $e) {
//             Log::error('Error dalam RoundInvoiceTotal:', [
//                 'message' => $e->getMessage(),
//                 'trace' => $e->getTraceAsString()
//             ]);
//         }
//     }
// }
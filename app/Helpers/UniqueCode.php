<?php

namespace App\Helpers;

use App\Models\Invoice;
use App\Models\Invoiceitem;
use App\Helpers\Invoice as InvoiceHelper;
use Illuminate\Support\Facades\Log;

class UniqueCode
{
    public static function getRangeForPaymentMethod($paymentMethod)
    {
        // Define ranges based on payment method.
        // For now, using generic examples. Adjust as needed.
        switch ($paymentMethod) {
            case 'banktransfer':
            case 'bca':
            case 'mandiri':
            case 'bri':
            case 'bcaapi':
            case 'bcava':
            case 'bniva':
            case 'briva':
            case 'mandiriva':
            case 'permatabankva':
            case 'atmbersamava':
            case 'hanabankva':
            case 'danamonva':
            case 'biiva':
            case 'cimbva':
            case 'bnivaxendit':
            case 'sampoernava':
            case 'bjbva':
            case 'bsiva':
            case 'bncva':
                return [1, 999];
            default:
                return [1, 50];
        }
    }

    public static function ensureUniqueInvoiceTotal($invoiceId, $paymentMethod)
    {
        $maxRetries = 10;
        $uniqueItemDescription = "Kode Unik Untuk Identifikasi Pembayaran Otomatis";
        $invoice = Invoice::find($invoiceId);

        if (!$invoice || $invoice->status != 'Unpaid') {
            return;
        }

        // Get the range for this payment method
        list($min, $max) = self::getRangeForPaymentMethod($paymentMethod);

        // Check if item exists
        $existingItem = Invoiceitem::where('invoiceid', $invoiceId)
            ->where('description', $uniqueItemDescription)
            ->first();

        // If exists, verify uniqueness first
        if ($existingItem) {
             InvoiceHelper::UpdateInvoiceTotal($invoiceId);
             if (self::isTotalUnique($invoiceId)) {
                 return;
             }
             // Collision found with existing code, remove it and retry
             $existingItem->delete();
        }

        $retryCount = 0;
        do {
            $codeAmount = mt_rand($min, $max);

            // Log::info("Generating unique code $codeAmount for invoice $invoiceId");

            // Insert new item
            $newItem = new Invoiceitem();
            $newItem->invoiceid = $invoiceId;
            $newItem->userid = $invoice->userid;
            $newItem->type = '';
            $newItem->relid = 0;
            $newItem->description = $uniqueItemDescription;
            $newItem->amount = $codeAmount;
            $newItem->taxed = 0;
            $newItem->duedate = $invoice->duedate;
            $newItem->paymentmethod = $paymentMethod;
            $newItem->save();

            // Update Invoice Total
            InvoiceHelper::UpdateInvoiceTotal($invoiceId);

            // Check Uniqueness
            if (self::isTotalUnique($invoiceId)) {
                break;
            }

            // Retry: delete the item we just added because it caused a collision
            $newItem->delete();
            $retryCount++;

        } while ($retryCount < $maxRetries);

        if ($retryCount >= $maxRetries) {
            Log::warning("UniqueCode: Could not generate unique invoice total for Invoice ID $invoiceId after $maxRetries attempts.");
        }
    }

    private static function isTotalUnique($invoiceId)
    {
        $currentInvoice = Invoice::find($invoiceId);
        if (!$currentInvoice) return false;

        $total = $currentInvoice->total;

        // Check for other UNPAID invoices with the same total
        $collision = Invoice::where('status', 'Unpaid')
            ->where('total', $total)
            ->where('id', '!=', $invoiceId)
            ->exists();

        return !$collision;
    }
}

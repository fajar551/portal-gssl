<?php

namespace App\Hooks;

use App\Events\InvoicePaid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class InvoicePaidHook
{

    public function handle(InvoicePaid $event)
    {
        try {
            Log::info('InvoicePaidHook started', [
                'invoice_id' => $event->invoiceid,
                'time' => now()->format('Y-m-d H:i:s')
            ]);

            $invid = $event->invoiceid;
            $today = Carbon::now()->format('Y-m-d');
            $registerdomaincollection = 'No';

            $invoice = DB::table('tblinvoices')
                ->select('userid', 'datepaid')
                ->where('id', $invid)
                ->first();

            if (!$invoice) {
                Log::warning('Invoice not found', ['invoice_id' => $invid]);
                return;
            }

            $userid = $invoice->userid;
            $datepaid = Carbon::parse($invoice->datepaid)->format('Y-m-d');

            Log::info('Processing invoice data', [
                'invoice_id' => $invid,
                'user_id' => $userid,
                'date_paid' => $datepaid
            ]);

            $domainRegistrations = DB::table('tblinvoiceitems')
                ->where('invoiceid', $invid)
                ->where('type', 'DomainRegister')
                ->get();

            $domainTransfers = DB::table('tblinvoiceitems')
                ->where('invoiceid', $invid)
                ->where('type', 'DomainTransfer')
                ->count();

            $sslItems = DB::table('tblinvoiceitems')
                ->where('invoiceid', $invid)
                ->where('type', 'Hosting')
                ->count();

            $prorateItems = DB::table('tblinvoiceitems')
                ->where('invoiceid', $invid)
                ->where('type', 'Prorate')
                ->count();

            Log::info('Item counts', [
                'domain_registrations' => $domainRegistrations->count(),
                'domain_transfers' => $domainTransfers,
                'ssl_items' => $sslItems,
                'prorate_items' => $prorateItems
            ]);

            $existingCollection = DB::table('hook_collectioninvoice')
                ->where('invoiceid', $invid)
                ->exists();

            if ($existingCollection) {
                Log::info('Invoice already in collection', ['invoice_id' => $invid]);
                return;
            }

            foreach ($domainRegistrations as $registration) {
                try {
                    $domain = DB::table('tbldomains')
                        ->where('id', $registration->relid)
                        ->value('domain');

                    if ($domain) {
                        $parts = explode('.', $domain);

                        Log::info('Processing domain', [
                            'domain' => $domain,
                            'parts' => $parts
                        ]);

                        if (isset($parts[1]) && $parts[1] == 'id') {
                            $emailVerified = DB::table('tblclients')
                                ->where('id', $userid)
                                ->value('email_verified');

                            if ($emailVerified != '1') {
                                $registerdomaincollection = 'Yes';
                                Log::info('Domain marked for collection - unverified email', [
                                    'domain' => $domain
                                ]);
                            }
                        }
                        elseif (isset($parts[2]) && $parts[2] == 'id') {
                            $secondLevel = $parts[1];
                            $specialDomains = ['co', 'ac', 'or', 'sch', 'web', 'ponpes'];

                            if (in_array($secondLevel, $specialDomains)) {
                                $registerdomaincollection = 'Yes';
                                Log::info('Domain marked for collection - special domain', [
                                    'domain' => $domain,
                                    'second_level' => $secondLevel
                                ]);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Error processing domain registration', [
                        'registration_id' => $registration->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            if (($domainTransfers > 0 || $sslItems > 0 || $prorateItems > 0) && !$existingCollection) {
                DB::beginTransaction();

                try {
                    Log::info('Processing collections', [
                        'domain_transfers' => $domainTransfers,
                        'ssl_items' => $sslItems,
                        'prorate_items' => $prorateItems
                    ]);

                    $updateResult = DB::table('tblinvoices')
                        ->where('id', $invid)
                        ->update([
                            'status' => 'Collections',
                            'updated_at' => Carbon::now()
                        ]);

                    Log::info('Updated invoice status to Collections', [
                        'invoice_id' => $invid,
                        'update_success' => $updateResult
                    ]);

                    if ($domainTransfers > 0) {
                        $this->handleDomainTransfer($invid, $datepaid, $today);
                        Log::info('Processed Domain Transfer', ['invoice_id' => $invid]);
                    }

                    if ($sslItems > 0) {
                        $this->handleSSL($invid, $datepaid, $today);
                        Log::info('Processed SSL', ['invoice_id' => $invid]);
                    }

                    if ($prorateItems > 0) {
                        $this->handleProrate($invid, $datepaid, $today);
                        Log::info('Processed Prorate', ['invoice_id' => $invid]);
                    }

                    if ($registerdomaincollection == 'Yes') {
                        $this->handleDomainRegistration($invid, $datepaid);
                        Log::info('Processed Domain Registration', ['invoice_id' => $invid]);
                    }

                    DB::commit();

                    Log::info('Collections processing completed successfully', [
                        'invoice_id' => $invid,
                        'status' => 'Collections'
                    ]);

                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            }

        } catch (\Exception $e) {
            Log::error('Error in InvoicePaidHook', [
                'invoice_id' => $invid ?? null,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function handleDomainTransfer($invid, $datepaid, $today)
    {
        try {
            DB::table('hook_collectioninvoice')->insert([
                'invoiceid' => $invid,
                'type' => 'Domain Transfer',
                'tanggalpaid' => $datepaid,
                'status' => 'OnProses'
            ]);

            $recipientEmail = $this->getRecipientEmail($invid);
            if (!$recipientEmail) {
                Log::error('Invalid email address for Domain Transfer handling', ['invoice_id' => $invid]);
                return;
            }

            $htmlContent = $this->getEmailTemplate($invid, $datepaid, $today, 'Domain Transfer');

            Mail::html('<p>Dicatat dan diawasi sampai 3x24 Jam untuk syaratnya</p>', function($message) use ($invid, $recipientEmail) {
                $message->to($recipientEmail)
                    ->subject('Ada Domain Transfer di Invoice #' . $invid);
            });

            Mail::html($htmlContent, function($message) use ($invid, $recipientEmail) {
                $message->to($recipientEmail)
                    ->subject('[UBAH STATUS] PAID TO COLLECTION Invoice #' . $invid);
            });

            $this->handleCashbackCredits($invid);

            Log::info('Domain Transfer handling completed', ['invoice_id' => $invid]);

        } catch (\Exception $e) {
            Log::error('Error in handleDomainTransfer', [
                'invoice_id' => $invid,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    // private function handleSSL($invid, $datepaid, $today)
    // {
    //     try {
    //         DB::table('hook_collectioninvoice')->insert([
    //             'invoiceid' => $invid,
    //             'type' => 'SSL',
    //             'tanggalpaid' => $datepaid,
    //             'status' => 'OnProses'
    //         ]);

    //         $recipientEmail = DB::table('tblclients')->where('id', 641)->value('email');
    //         $htmlContent = $this->getEmailTemplate($invid, $datepaid, $today, 'SSL');

    //         Mail::html($htmlContent, function($message) use ($invid, $recipientEmail) {
    //             $message->to($recipientEmail)
    //                 ->subject('[UBAH STATUS] PAID TO COLLECTION Invoice #' . $invid);
    //         });

    //         Log::info('SSL handling completed', ['invoice_id' => $invid]);

    //     } catch (\Exception $e) {
    //         Log::error('Error in handleSSL', [
    //             'invoice_id' => $invid,
    //             'error' => $e->getMessage()
    //         ]);
    //         throw $e;
    //     }
    // }

    private function handleSSL($invid, $datepaid, $today)
    {
        try {
            DB::table('hook_collectioninvoice')->insert([
                'invoiceid' => $invid,
                'type' => 'SSL',
                'tanggalpaid' => $datepaid,
                'status' => 'OnProses'
            ]);

            $recipientEmail = $this->getRecipientEmail($invid);
            if (!$recipientEmail) {
                Log::error('Invalid email address for SSL handling', ['invoice_id' => $invid]);
                return;
            }

            $htmlContent = $this->getEmailTemplate($invid, $datepaid, $today, 'SSL');

            Mail::html($htmlContent, function($message) use ($invid, $recipientEmail) {
                $message->to($recipientEmail)
                    ->subject('[UBAH STATUS] PAID TO COLLECTION Invoice #' . $invid);
            });

            Log::info('SSL handling completed', ['invoice_id' => $invid]);

        } catch (\Exception $e) {
            Log::error('Error in handleSSL', [
                'invoice_id' => $invid,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function handleCashbackCredits($invid)
    {
        try {
            $credit = DB::table('tblcredit')
                ->where('description', 'Cashback Invoice ' . $invid)
                ->first();

            if ($credit) {
                Log::info('Processing cashback credit', [
                    'invoice_id' => $invid,
                    'credit_amount' => $credit->amount,
                    'client_id' => $credit->clientid
                ]);

                DB::beginTransaction();

                try {
                    DB::table('tblcredit')
                        ->where('description', 'Cashback Invoice ' . $invid)
                        ->delete();

                    DB::table('tblclients')
                        ->where('id', $credit->clientid)
                        ->decrement('credit', $credit->amount);

                    DB::commit();

                    Log::info('Cashback credit processed successfully', [
                        'invoice_id' => $invid,
                        'credit_amount' => $credit->amount
                    ]);

                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            }

        } catch (\Exception $e) {
            Log::error('Error in handleCashbackCredits', [
                'invoice_id' => $invid,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function handleProrate($invid, $datepaid, $today)
    {
        try {
            Log::info('Handling prorate', [
                'invoice_id' => $invid,
                'date_paid' => $datepaid
            ]);

            DB::table('hook_collectioninvoice')->insert([
                'invoiceid' => $invid,
                'type' => 'Prorate',
                'tanggalpaid' => $datepaid,
                'status' => 'OnProses'
            ]);

            $recipientEmail = $this->getRecipientEmail($invid);
            if (!$recipientEmail) {
                Log::error('Invalid email address for Prorate handling', ['invoice_id' => $invid]);
                return;
            }

            $htmlContent = $this->getEmailTemplate($invid, $datepaid, $today, 'Prorate');

            Mail::html($htmlContent, function($message) use ($invid, $recipientEmail) {
                $message->to($recipientEmail)
                    ->subject('[UBAH STATUS] PAID TO COLLECTION Invoice #' . $invid);
            });

            Log::info('Prorate handling completed', ['invoice_id' => $invid]);

        } catch (\Exception $e) {
            Log::error('Error in handleProrate', [
                'invoice_id' => $invid,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function handleDomainRegistration($invid, $datepaid)
    {
        try {
            Log::info('Handling domain registration', [
                'invoice_id' => $invid,
                'date_paid' => $datepaid
            ]);

            DB::table('hook_collectioninvoice')->insert([
                'invoiceid' => $invid,
                'type' => 'Domain Registration',
                'tanggalpaid' => $datepaid,
                'status' => 'OnProses'
            ]);

            $recipientEmail = $this->getRecipientEmail($invid);
            if (!$recipientEmail) {
                Log::error('Invalid email address for Domain Registration handling', ['invoice_id' => $invid]);
                return;
            }

            $today = Carbon::now()->format('Y-m-d');
            $htmlContent = $this->getEmailTemplate($invid, $datepaid, $today, 'Domain Registration');

            Mail::html($htmlContent, function($message) use ($invid, $recipientEmail) {
                $message->to($recipientEmail)
                    ->subject('[UBAH STATUS] PAID TO COLLECTION Invoice #' . $invid);
            });

            Log::info('Domain Registration handling completed', ['invoice_id' => $invid]);

        } catch (\Exception $e) {
            Log::error('Error in handleDomainRegistration', [
                'invoice_id' => $invid,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Mendapatkan email recipient dari user yang membayar invoice
     *
     * @param int $invid Invoice ID
     * @return string|null Email address atau null jika tidak valid
     */
    private function getRecipientEmail($invid)
    {
        try {
            // Ambil userid dari invoice
            $invoice = DB::table('tblinvoices')
                ->select('userid')
                ->where('id', $invid)
                ->first();

            if (!$invoice || !$invoice->userid) {
                Log::warning('Invoice or userid not found', ['invoice_id' => $invid]);
                return null;
            }

            // Ambil email dari user yang membayar invoice
            $recipientEmail = DB::table('tblclients')
                ->where('id', $invoice->userid)
                ->value('email');

            // Validasi email
            if (!$recipientEmail || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
                Log::warning('Invalid or missing email for invoice user', [
                    'invoice_id' => $invid,
                    'user_id' => $invoice->userid,
                    'email' => $recipientEmail
                ]);
                return null;
            }

            Log::info('Recipient email retrieved', [
                'invoice_id' => $invid,
                'user_id' => $invoice->userid,
                'email' => $recipientEmail
            ]);

            return $recipientEmail;

        } catch (\Exception $e) {
            Log::error('Error getting recipient email', [
                'invoice_id' => $invid,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function getEmailTemplate($invid, $datepaid, $today, $reason)
    {
        return '<table>
            <center>
                <tr>
                    <th colspan="3">COLLECTION INVOICE</th>
                </tr>
                <tr>
                    <td>Nomor Invoice</td>
                    <td>:</td>
                    <td>' . $invid . '</td>
                </tr>
                <tr>
                    <td>Tanggal Paid</td>
                    <td>:</td>
                    <td>' . $datepaid . '</td>
                </tr>
                <tr>
                    <td>Tgl Collection</td>
                    <td>:</td>
                    <td>' . $today . '</td>
                </tr>
                <tr>
                    <td>Alasan Collection</td>
                    <td>:</td>
                    <td>' . $reason . ' (Automated By System)</td>
                </tr>
            </center>
        </table>';
    }
}
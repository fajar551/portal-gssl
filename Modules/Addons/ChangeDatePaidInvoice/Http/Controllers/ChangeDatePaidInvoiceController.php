<?php

namespace Modules\Addons\ChangeDatePaidInvoice\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChangeDatePaidInvoiceController extends Controller
{
    public function config()
    {
        return [
            'name' => 'Change Date Paid Invoice',
            'description' => 'Module ini digunakan untuk mengubah tanggal pembayaran invoice',
            'author' => 'CBMS Developer - Rafly',
            'language' => 'english',
            'version' => '1.0',
            'fields' => [],
        ];
    }

    public function activate()
    {
        try {
            return [
                'status' => 'success',
                'description' => 'Module enabled',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'description' => 'Unable to create your module: ' . $e->getMessage(),
            ];
        }
    }

    public function deactivate()
    {
        try {
            return [
                'status' => 'success',
                'description' => 'Module has been disabled and all related tables have been dropped.',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'description' => "Unable to drop your module: {$e->getMessage()}",
            ];
        }
    }

    public function output() 
    {
        $auth = Auth::guard('admin')->user();
        $clientid = $auth->id;

        $page = request()->get('page');
        $domain = request()->get('domain');
        $id = request()->get('id'); 

        if (empty($page) && empty($domain)) {
            return view('changedatepaidinvoice::index');
        }

        switch ($page) {
            case '':
                return view('changedatepaidinvoice::index');
                break;
            case 'index':
                return view('changedatepaidinvoice::index');
                break;
            default:
                echo 'page not found';
        }

        // Default JSON response for AJAX requests
        return response()->json(['message' => 'No data found'], 404);
    }

    public function processChangePaid(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'invoiceid' => 'required|integer',
                'datechange' => 'required|date',
            ]);

            $process = $request->input('invoiceid');
            $tanggal = $request->input('datechange');

            // Update the invoice datepaid
            DB::table('tblinvoices')
                ->where('id', $process)
                ->where('status', 'Paid')
                ->update(['datepaid' => $tanggal]);

            // Log the activity
            $userid = DB::table('tblinvoices')
                ->where('id', $process)
                ->value('userid');

            $date = now();
            $adminid = Auth::guard('admin')->id();

            $description = 'Invoice ID: ' . $process . ' - Invoice Changed DatePaid to ' . $tanggal;

            $username = DB::table('tbladmins')
                ->where('id', $adminid)
                ->value('username');

            DB::table('tblactivitylog')->insert([
                'date' => $date,
                'description' => $description,
                'user' => $username,
                'userid' => $userid,
                'ipaddr' => $request->ip(),
            ]);

            return response()->json(['status' => 'success', 'message' => 'Date paid successfully changed.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to change date paid: ' . $e->getMessage()]);
        }
    }

    public function getSelectInvoiceId(Request $request)
    {
        $search = $request->get('search');
        $page = $request->get('page', 1);
        $perPage = 5;

        $query = DB::table('tblinvoices')
            ->select('id', 'datepaid', 'userid')
            ->when($search, function ($query, $search) {
                return $query->where('id', 'like', "%{$search}%")
                    ->where('status', 'Paid')
                    ->orWhere('userid', 'like', "%{$search}%");
            });


        $total = $query->count();

        $clients = $query->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        $response = [
            'code' => 'success',
            'data' => $clients,
            'total' => $total
        ];
        return $response;
    }

    public function getDataItemInvoiceById(Request $request)
    {
        $invoiceId = $request->get('invoiceid');

        // Get the total number of records
        $totalRecords = DB::table('tblinvoiceitems')
            ->where('invoiceid', $invoiceId)
            ->count();

        // Get the filtered records and format the amount
        $invoiceItems = DB::table('tblinvoiceitems')
            ->where('invoiceid', $invoiceId)
            ->select('invoiceid', 'userid', 'type', 'description', 
                DB::raw('CAST(amount AS DECIMAL(16,2)) as amount'), // Cast amount to decimal
                'taxed', 'duedate')
            ->get();

        // Calculate the total amount
        $totalAmount = DB::table('tblinvoiceitems')
            ->where('invoiceid', $invoiceId)
            ->sum(DB::raw('CAST(amount AS DECIMAL(16,2))'));

        // Prepare the response
        return response()->json([
            'draw' => intval($request->get('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $invoiceItems,
            'totalAmount' => $totalAmount // Include total amount in the response
        ]);
    }


}

<?php

namespace Modules\Addons\Auction\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Helpers\ResponseAPI;
use App\Http\Controllers\Client\_AuctionController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuctionController extends Controller 
{
    public function config()
    {
        return [
            'name' => 'Auction Backoder',
            'description' => 'Module ini digunakan untuk kelola lelang domain',
            'author' => 'CBMS Developer - Rafly',
            'language' => 'english',
            'version' => '1.0',
            'fields' => [],
        ];
    }

    public function output()
    {
        $auction = new _AuctionController();
        $auth = Auth::guard('admin')->user();
        $clientid = $auth->id;
        
        $isFilter = request()->get('isFilter');
        $criteria = [];
        if($isFilter) {
            $criteria = [
                'domain' => request()->get('domain'),
                'price' => request()->get('price'),
                'open_date' => request()->get('open_date'),
                'close_date' => request()->get('close_date'),
                'status' => request()->get('status'),
                'owner' => request()->get('owner'),
            ];
        
            $query = DB::table('auction_domain');

            if ($criteria['domain']) {
                $query->where('domain', 'like', "%".$criteria['domain']."%");
            }
            if ($criteria['price']) {
                $query->where('price', $criteria['price']);
            }
            if ($criteria['open_date']) {
                $query->whereDate('open_date', $criteria['open_date']);
            }
            if ($criteria['close_date']) {
                $query->whereDate('close_date', $criteria['close_date']);
            }
            if ($criteria['status']) {
                $query->where('status', $criteria['status']);
            }
            if ($criteria['owner']) {
                $query->where('owner', 'like', "%".$criteria['owner']."%");
            }

            $results = $query->get();

            if (request()->ajax()) {
                return response()->json($results);
            }
        }

        $datas = [
            'public_domain' => [],
            'list'          => [],
            'detail'        => [],
            'history'       => [],
            'total_order'   => 0,
            'bid_token'     => '',
        ];
        $clientarea_page['vars'] = $datas;

        $page = request()->get('page');
        $domain = request()->get('domain');

        $table = $auction->getData($domain);
        $detail = $auction->getDetail($clientid);
        $total_order = $auction->getTotalOrder($domain, $clientid);
        $history = $auction->getHistory($domain);
        $history_obfuscate = $auction->obfuscate_email($history);

        if (empty($page) && empty($domain)) {
            $waktu = request()->get('waktu');
            $tipe = request()->get('tipe');
            if (!$tipe) {
                $waktu = 'waktu-terdekat';
                $tipe = 'normal';
            }
            if ($waktu && $tipe) {
                // Tentukan arah pengurutan berdasarkan waktu
                $orderDirection = 'ASC';
                if ($waktu == 'waktu-terlama') {
                    $orderDirection = 'DESC';
                }
                // Tentukan filter tipe untuk `public_domain`
                if ($tipe == 'backorder') {
                    $tipe = 'backorder';
                } elseif ($tipe == 'client') {
                    $tipe = 'client';
                } else {
                    $tipe = 'normal';
                }

                $public_domain = $auction->getAuctionData($clientid, $waktu, $tipe);
                $all_auction = $auction->getAllAuction();
                // dd($public_domain);
                // input umum untuk `sell_domain`
                $sell_domain_input = DB::table('auction_domain')
                    ->select(['auction_domain.*'])
                    ->join('sell_domain', 'sell_domain.domain', '=', 'auction_domain.domain')
                    ->where('enabled', 1)
                    ->where('sell_domain.price', '>', 249999)
                    ->where('auction_domain.status', 'SELL_DOMAIN')
                    ->groupBy('auction_domain.domain')
                    ->orderByRaw("auction_domain.close_date IS NULL, auction_domain.close_date $orderDirection");

                // Tambahkan filter berdasarkan tipe pada `sell_domain_input`
                if ($tipe == 'backorder') {
                    $sell_domain_input->whereNull('owner');
                } elseif ($tipe == 'client') {
                    $sell_domain_input->whereNotNull('owner');
                }

                // Eksekusi input `sell_domain` menjadi array
                $sell_domain = $sell_domain_input->get()->toArray();

                // Update last_price dan status dari sell_domain
                foreach ($sell_domain as $sdata) {
                    $sdata->last_price = $sdata->price;
                    $sdata->status = $sdata->status;
                }
            }

            if ($sell_domain) {
                $public_domain = array_merge($public_domain, $sell_domain);
            }
            
            if (!boolval($domain) && !in_array($page, ['my_auction', 'setting'])) {
                $public_domain = array_map(function ($val) {
                    $auction = new _AuctionController();

                    if ($val->owner == NULL) {
                        $total_order = $auction->getPriceDomain($val->domain);
                        $val->price_domain = $total_order;
                    }
                    return $val;
                }, $public_domain);

                // $clientarea_page['vars']['payment_methods'] = $payment_methods;
                $clientarea_page['vars']['public_domain'] = $public_domain;

                $datas = [
                    'public_domain' => $public_domain,
                    'all_auction'   => $all_auction,
                    'list'          => $table,
                    'detail'        => $detail,
                    'history'       => $history_obfuscate,
                    'total_order'   => $total_order,
                    'bid_token'     => '',
                    'waktu'         => $waktu,
                    'tipe'          => $tipe,
                ];
                $clientarea_page['vars'] = $datas;
            }
            // dd($clientarea_page);
            return view('auction::index', $clientarea_page['vars']);
        }

        switch ($page) {
            case 'history':
                if ($page == 'history' && !$domain) {
                    $auction->showRouteNotFound();
                    return;
                }
            
                // Mengambil semua riwayat dari database
                $all_history = DB::table('auction_domain_history')
                    ->join('auction_domain', 'auction_domain.domain', '=', 'auction_domain_history.domain')
                    ->select('auction_domain_history.*', 'auction_domain.owner')
                    ->where('auction_domain_history.domain', $domain)
                    ->get();
            
                // Memproses setiap entri dalam riwayat
                $all_history = array_map(function($val) use ($auction) {
                    if (isset($val->owner) && boolval($val->owner)) {
                        return $val;
                    }
                    
                    $total_order = $auction->getTotalOrder($val->domain, null);
                    $val->last_price = $val->last_price + $total_order;
                    return $val;
                }, $all_history->toArray()); 
            
                if ($detail) {
                    $detail->maxtry = 1;
                }
            
                $clientarea_page['vars']['all_history'] = $all_history;
                $clientarea_page['vars']['detail'] = $detail;
            
                return view('auction::history', $clientarea_page['vars']);
            break;   

            case 'backorder':
                $irsfa = new _IrsfaAuctionController(); 
                $backorder = $irsfa->getDomainBackorder($datas);
                
                if(!$backorder) {
                    return redirect()->back()->with([
                        'alert-message' => 'Gagal mendapatkan domain backorder: ' . $backorder->message,
                        'alert-type' => 'danger'
                    ]);
                }
                $backorder = $backorder->data; 

                $clientarea_page['vars']['backorder'] = $backorder;

                return view('auction::backorder', $clientarea_page['vars']);
            break;

            default:
                $auction->showRouteNotFound();
                return;
            break;
        }

    }

    public function action(Request $request)
    {
        $auction = new _AuctionController();
        $action = $request->input('action');
    
        switch ($action) {
            case 'delete':
                $idDomain = $request->input('id_domain');
                if (!$idDomain) {
                    return response()->json([
                        'status' => 'error',
                        'description' => 'ID domain tidak valid.'
                    ]);
                }
                
                try {
                    DB::table('auction_domain')
                        ->where('id', $idDomain)
                        ->update(['status' => 'ARCHIEVED']);
                } catch (\Exception $e) {
                    return response()->json([
                        'status' => 'error',
                        'description' => 'Gagal delete domain: ' . $e->getMessage()
                    ]);
                }

                return response()->json([
                    'status' => 'success',
                    'description' => 'Domain berhasil dihapus.'
                ]);
            break;

            case 'reopen':
                $idDomain = $request->input('id_domain');
                $datetime = $request->input('datetime');
                if (!$datetime) {
                    return response()->json([
                        'status' => 'error',
                        'description' => 'DateTime kosong'
                    ]);
                }
                if (!$idDomain) {
                    return response()->json([
                        'status' => 'error',
                        'description' => 'ID domain kosong'
                    ]);
                }

                $domain = DB::table('auction_domain')->where('id', $idDomain)->first();
                if (!$domain) {
                    return response()->json([
                        'status' => 'error',
                        'description' => 'Domain tidak ditemukan.'
                    ]);
                }

                try {
                    DB::table('auction_domain')->where('id', $idDomain)->update([
                        'status' => 'OPEN_LELANG',
                        'close_date' => $datetime,
                    ]);

                    DB::table('auction_domain_history')
                        ->where('domain', $domain->domain)
                        ->update(['invoiceid' => null]);
                } catch (\Exception $e) {
                    return response()->json([
                        'status' => 'error',
                        'description' => 'Gagal membuka kembali domain: ' . $e->getMessage()
                    ]);
                }

                return response()->json([
                    'status' => 'success',
                    'description' => 'Domain berhasil dibuka kembali untuk lelang.'
                ]);
            break;

            case 'restart':
                $datetime = $request->input('datetime');
                $hargaawal = $request->input('hargaawal');
                $idDomain = $request->input('id_domain');
            
                if (!$idDomain) {
                    return response()->json([
                        'status' => 'error',
                        'description' => 'ID domain tidak valid.'
                    ]);
                }

                $domain = DB::table('auction_domain')->where('id', $idDomain)->first();
                if (!$domain) {
                    return response()->json([
                        'status' => 'error',
                        'description' => 'Domain tidak ditemukan.'
                    ]);
                }

                try {
                    DB::table('auction_domain')->where('id', $idDomain)->update([
                        'status' => 'OPEN_LELANG',
                        'close_date' => $datetime,
                    ]);

                    DB::statement('INSERT INTO auction_domain_history_old SELECT * FROM auction_domain_history WHERE domain = ?', [$domain->domain]);
                    DB::statement('DELETE FROM auction_domain_history WHERE domain = ?', [$domain->domain]);
                
                    DB::table('auction_domain_history')->insert([
                        'domain' => $domain->domain,
                        'client_id' => $domain->owner ? $domain->owner : 9999999999,
                        'last_price' => $hargaawal,
                        'bid_price' => 0,
                        'removed_credit' => 0,
                        'status_deposit' => 'HOLD',
                        'status' => 'ARCHIEVE'
                    ]);
                } catch (\Exception $e) {
                    return response()->json([
                        'status' => 'error',
                        'description' => 'Gagal restart domain: ' . $e->getMessage()
                    ]);
                }

                return response()->json([
                    'status' => 'success',
                    'description' => 'Domain berhasil di-restart untuk lelang.'
                ]); 
            break;
           
            case 'save_bid':
                $domain = $request->input('domain');
                $idBid = $request->input('id_bid');
                $valueBid = $request->input('value_bid');
                $isCancelInvoice = $request->input('is_cancel_invoice');
            
                if (!$idBid && !$valueBid) {
                    return redirect()->back()->with([
                        'alert-message' => 'Bid atau nilai tidak ditemukan.',
                        'alert-type' => 'error'
                    ]);
                }
            
                if ($isCancelInvoice == '1') {
                    $bid_obj = DB::table('auction_domain_history')->where('id', $idBid)->first();
                    if ($bid_obj) {
                        $updated_invoice = DB::table('auction_domain_history')
                            ->where('domain', $domain)
                            ->where('client_id', $bid_obj->client_id)
                            ->update(['invoiceid' => null]);
            
                        if ($updated_invoice === 0) {
                            return redirect()->back()->with([
                                'alert-message' => 'Tidak ada invoice yang diperbarui.',
                                'alert-type' => 'warning'
                            ]);
                        }
                    } else {
                        return redirect()->back()->with([
                            'alert-message' => 'Bid tidak ditemukan.',
                            'alert-type' => 'error'
                        ]);
                    }
                }
            
                $updated_bid = DB::table('auction_domain_history')->where('id', $idBid)->update(['bid_price' => $valueBid]);
                if ($updated_bid === 0) {
                    return redirect()->back()->with([
                        'alert-message' => 'Tidak ada perubahan pada bid.',
                        'alert-type' => 'warning'
                    ]);
                }
            
                $list_history = DB::table('auction_domain_history')->where('domain', $domain)->orderBy('last_price', 'asc')->get();
                if ($list_history->isEmpty()) {
                    return redirect()->back()->with([
                        'alert-message' => 'Tidak ada history untuk domain ini.',
                        'alert-type' => 'warning'
                    ]);
                }
            
                $update_row = false;
            
                foreach ($list_history as $idx => $item) {
                    if ($idx !== 0) {
                        if ($item->id == $idBid) {
                            $item->bid_price = $valueBid;
                            $update_row = true;
                        }
            
                        if ($update_row) {
                            $prev_id = $list_history[$idx - 1]->id;
                            $prev_price = DB::table('auction_domain_history')->where('id', $prev_id)->first();
                            if ($prev_price) {
                                $last_price = $prev_price->last_price + $item->bid_price;
                                $update_item = DB::table('auction_domain_history')->where('id', $item->id)->update(['last_price' => $last_price]);
            
                                if ($update_item === 0) {
                                    return redirect()->back()->with([
                                        'alert-message' => 'Tidak ada perubahan pada last price.',
                                        'alert-type' => 'warning'
                                    ]);
                                }
                            } else {
                                return redirect()->back()->with([
                                    'alert-message' => 'Harga sebelumnya tidak ditemukan.',
                                    'alert-type' => 'error'
                                ]);
                            }
                        }
                    }
                }
            
                return redirect()->back()->with([
                    'alert-message' => 'Harga domain berhasil diubah untuk lelang.',
                    'alert-type' => 'success'
                ]);
            break;

            default:
                $auction->showRouteNotFound();
                return;
            break;
        }
    }

    public function activate()
    {
        try {
            // Cek apakah tabel auction_domain sudah ada
            if (!Schema::hasTable('auction_domain')) {
                Schema::create('auction_domain', function (Blueprint $table) {
                    $table->increments('id');
                    $table->string('domain', 100);
                    $table->integer('price');
                    $table->dateTime('open_date')->nullable();
                    $table->dateTime('close_date')->nullable();
                    $table->string('status', 100)->default('OPEN_LELANG');
                    $table->integer('maxtry')->default(1);
                    $table->integer('owner')->nullable();
                    $table->integer('max_auto_bid')->default(0);
                    $table->timestamps();
                });
            }

            // Cek apakah tabel auction_domain_history sudah ada
            if (!Schema::hasTable('auction_domain_history')) {
                Schema::create('auction_domain_history', function (Blueprint $table) {
                    $table->increments('id');
                    $table->string('domain', 100);
                    $table->integer('client_id');
                    $table->integer('bid_price');
                    $table->integer('last_price');
                    $table->integer('invoiceid')->nullable();
                    $table->string('status_deposit', 100)->default('HOLD')->comment('HOLD, PAID, REFUND, CANCEL');
                    $table->integer('removed_credit')->default(0);
                    $table->string('note', 255)->nullable();
                    $table->integer('anon_email')->nullable();
                    $table->timestamps();
                });
            }

            // Cek apakah tabel auction_domain_history_old sudah ada
            if (!Schema::hasTable('auction_domain_history_old')) {
                Schema::create('auction_domain_history_old', function (Blueprint $table) {
                    $table->increments('id');
                    $table->string('domain', 100);
                    $table->integer('client_id');
                    $table->integer('bid_price');
                    $table->integer('last_price');
                    $table->integer('invoiceid')->nullable();
                    $table->string('status_deposit', 100)->default('HOLD')->comment('HOLD, PAID, REFUND, CANCEL');
                    $table->integer('removed_credit')->default(0);
                    $table->string('note', 255)->nullable();
                    $table->integer('anon_email')->nullable();
                    $table->timestamps();
                });
            }

            // Cek apakah tabel auction_domain_old sudah ada
            if (!Schema::hasTable('auction_domain_old')) {
                Schema::create('auction_domain_old', function (Blueprint $table) {
                    $table->increments('id');
                    $table->string('domain', 255);
                    $table->integer('price');
                    $table->dateTime('open_date')->nullable();
                    $table->dateTime('close_date')->nullable();
                    $table->string('status', 100)->default('OPEN_LELANG');
                    $table->integer('maxtry')->default(1);
                    $table->integer('owner')->nullable();
                    $table->integer('max_auto_bid')->default(0);
                    $table->timestamps();
                });
            }

            // Cek apakah tabel auction_domain_setting sudah ada
            if (!Schema::hasTable('auction_domain_setting')) {
                Schema::create('auction_domain_setting', function (Blueprint $table) {
                    $table->increments('id');
                    $table->integer('client_id');
                    $table->integer('is_hidden')->nullable();
                    $table->integer('notif_domain')->nullable();
                });
            }

            // Cek apakah tabel account_winner_auction sudah ada
            if (!Schema::hasTable('account_winner_auction')) {
                Schema::create('account_winner_auction', function (Blueprint $table) {
                    $table->increments('id');
                    $table->string('domain', 100);
                    $table->enum('status', ['Unfreeze', 'Freeze'])->default('Unfreeze');
                    $table->integer('id_client');
                    $table->timestamps();
                });
            }

            // if (!Schema::hasTable('sell_domain')) {
            //     Schema::create('sell_domain', function (Blueprint $table) {
            //         $table->increments('id');
            //         $table->integer('clientid');
            //         $table->string('domain', 255);
            //         $table->string('uniqid', 255)->nullable();
            //         $table->string('status', 50)->default('NEED_VERIFY');
            //         $table->tinyInteger('enabled')->default(0);
            //         $table->integer('price')->default(0);
            //         $table->string('type', 50)->default('FIX_PRICE');
            //         $table->string('epp', 150)->nullable();
            //         $table->integer('invoiceid')->nullable();
            //         $table->longText('seller_note')->nullable();
            //         $table->timestamp('created_at')->useCurrent();
            //         $table->longText('notes')->nullable();
            //     });
            // }

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
            // // Menghapus semua tabel terkait
            // Schema::dropIfExists('auction_domain');
            // Schema::dropIfExists('auction_domain_history');
            // Schema::dropIfExists('auction_domain_history_old');
            // Schema::dropIfExists('auction_domain_old');
            // Schema::dropIfExists('auction_domain_setting');

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
}

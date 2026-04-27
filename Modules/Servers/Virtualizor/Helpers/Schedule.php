<?php

namespace Modules\Servers\Virtualizor\Helpers;

use Illuminate\Http\Request;
use Modules\Servers\Virtualizor\Helpers\Resourcefunctions;
use Modules\Servers\Virtualizor\Sdk\Virtualizor_Admin_API;
use Carbon\Carbon;
use DB;

class Schedule
{
    public static function run()
    {
        \Log::debug("virtualizor runs");

        DB::beginTransaction();
        try {
            //keypass lama
            // $key =  '4ronqkc6jbkbe92i1slreqcxjuw4sfjf';
            // $pass = 'oqagrlf89d1zsaih5rybr5sss9sujkq1';
            
            //keypass baru
            $key =  'vyfm0ABIerOF8OHIOL8G8q0mY3v6qFfb';
            $pass = '0KbFX7SvlpFWQLVtW85cWHHI2JpwZjiy';
            $ip = '103.28.12.120';
            @date_default_timezone_set('Asia/Jakarta');
            $now=date("Y-m-d H:i:s");
            $admin = new Virtualizor_Admin_API($ip, $key, $pass);
            $format=[1=>1,2=>2,3=>4,4=>8,5=>16];

            $now1=Carbon::now();
            $now2=Carbon::now();
            $range_1=$now1->subMinutes(60)->format('Y-m-d H:i');
            $range_2=$now2->subMinutes(63)->format('Y-m-d H:i');

            $vps=DB::table('tblhosting')
                ->join('tblcustomfieldsvalues', 'tblcustomfieldsvalues.relid', '=', 'tblhosting.id')
                ->join('tblcustomfields', 'tblcustomfields.id', '=', 'tblcustomfieldsvalues.fieldid')
                ->join('tblproducts', 'tblproducts.id', '=', 'tblhosting.packageid')
                ->where('tblcustomfields.fieldname','vpsid')
                ->where('tblcustomfields.type','product')
                ->where('tblcustomfieldsvalues.value','!=','')
                ->where('tblhosting.domainstatus','Active')
                ->select('tblhosting.id','tblhosting.userid','tblcustomfieldsvalues.value as vpsID','tblhosting.domain as hostname','tblproducts.name as paket')
                ->get();
            
            // \Log::debug($vps->toArray());
            foreach($vps as $vpsData) {
                if($vpsData->vpsID !='' ) {
                    $HOUR = date('H:i',strtotime('-60 minutes',strtotime($now)));
                    $Date = date('Y-m-d',strtotime('-60 minutes',strtotime($now)));
                    //menit ke 2
                    $HOUR2 = date('H:i',strtotime('-61 minutes',strtotime($now)));
                    $Date2 = date('Y-m-d',strtotime('-61 minutes',strtotime($now)));
                    //menit ke 3
                    $HOUR3 = date('H:i',strtotime('-62 minutes',strtotime($now)));
                    $Date3 = date('Y-m-d',strtotime('-63 minutes',strtotime($now)));
                    
                    //cek aktivitas VPS
                    $cekInterval=DB::table('jamaktivasi')
                        ->where('product_id',$vpsData->id)
                        ->where('type','vps')
                        ->select('id');
                    //$admin->r($cekInterval);
                    //exit(); 
                    //$cekInterval->id=1;
                    
                    //invoice vps generate
                    // \Log::debug("invoice vps generate");
                    // \Log::debug($cekInterval->value('id') ?? 0);
                    if($cekInterval->value('id')) {
                                            
                        $time_aktivite = DB::table('jamaktivasi')
                            ->select('id','jamaktivasi')
                            ->whereBetween('jamaktivasi', [$range_2.':00', $range_1.':59'])
                            ->where('product_id',$vpsData->id)
                            ->where('type','vps');
                                
                        //$admin->r($time_aktivite);
                        // \Log::debug("===== time_aktivite");
                        // \Log::debug($time_aktivite->value('id'));
                        if($time_aktivite->value('id')){
                            $post=['vpsid'=> $vpsData->vpsID,'vsstatus' => 'u'];
                            //$admin->r($post);
                            $getVitualizor = $admin->listvs($page=0 ,$reslen=0 ,$post);
                            //$admin->r($getVitualizor);
                            if(!empty($getVitualizor[$vpsData->vpsID])){
                                $usageVPS=(object)$getVitualizor[$vpsData->vpsID];
                                //$admin->r($usageVPS);
                                //$bandwidthUSED=$usageVPS->used_bandwidth;
                                
                                $param						= array();
                                $param['userid']			= $vpsData->userid;
                                //$param['status']			= 'Unpaid';
                                
                                $getVPS_paket=(new Resourcefunctions)->paketVPS(trim($vpsData->paket));
                        
                                $param['itemdescription1']	= $vpsData->paket .' ('.$vpsData->hostname.')' ;
                                $param['itemamount1']		= $getVPS_paket['perhour'];
                                $param['itemtaxed1']		= false;
                                $param['paymentmethod']		= "BankTransfer";
                                $param['sendinvoice']		= false;
                            
                                
                                $sent=(new Resourcefunctions)->createInvoice($param);
                                // \Log::debug("===== createInvoice");
                                // \Log::debug($sent);
                                //$admin->r($sent);
                                
                                //exit();
                                //$admin->r($sent);
                                if($sent['result'] == 'success'){
                                    $appy=(new Resourcefunctions)->ApplyCreditInvoice($sent['invoiceid']);
                                    // \Log::debug("==== appy");
                                    // \Log::debug($appy);
                                    //$admin->r($appy);
                                    // klo PAID
                                    if ($appy['result'] == 'success' && $appy['invoicepaid'] == 'true') {
                                        $nextdate = Carbon::parse($time_aktivite->value('jamaktivasi'))->addMinute(60);
                                        DB::table('jamaktivasi')
                                            ->where('id',$time_aktivite->value('id'))
                                            ->update(['jamaktivasi' => $nextdate]);
                                    } else {
                                        $suspend=$admin->suspend($vpsData->vpsID);
                                        DB::table('vps_kavm_invoice')->insert(
                                        ['product_id' => $vpsData->id, 'user_id' => $vpsData->userid,'vps_id' => $vpsData->vpsID, 'invoice' => $sent['invoiceid'], 'total' => (new Resourcefunctions)->getInvoiceTotalVPS($sent['invoiceid']), 'date_created' => date('Y-m-d') ]);
                                        
                                        
                                        DB::table('tblhosting')
                                            ->where('id', $vpsData->id)
                                            ->update(['domainstatus' => 'Suspended']);
                                                            
                                        //Suspend Notif via email kalau server di suspend akibat deposit kurang
                                        $msgEmail='
                                                    <p>
                                                        Layanan '.$vpsData->paket.' telah kami Suspended dikarenakan deposit anda tidak mencukupi
                                                        </p>
                                        
                                                    ';
                                        
                                        
                                        $postData=[
                                                    'customtype'		=> 'general',
                                                    'messagename' 		=> $msgEmail,
                                                    'id'				=> $vpsData->userid,
                                                    'custommessage'		=> $msgEmail,
                                                    'customsubject'		=> 'Suspended Layanan '.$vpsData->paket,
                                                    ];
                                            
                                            
                                        $results = (new \App\Helpers\HelperApi)->localAPI('SendEmail', $postData);
                                    }
                                        
                                } 
                                else {
                                
                                                            
                                        // Notif via email ke admin kalau server tidak terkoneksi
                                        $msgEmail2='
                                                    <p>
                                                        Layanan '.$vpsData->paket.' tidak terkoneksi dengan server
                                                        </p>
                                        
                                                    ';
                                        
                                        
                                        $postData2=[
                                                    'customtype'		=> 'general',
                                                    'messagename' 		=> $msgEmail2,
                                                    'id'				=> 1,
                                                    'custommessage'		=> $msgEmail2,
                                                    'customsubject'		=> 'Koneksi Terputus '.$vpsData->paket,
                                                    ];
                                            
                                            
                                        $results = (new \App\Helpers\HelperApi)->localAPI('SendEmail', $postData2);
                                        
                                        $appy=(new Resourcefunctions)->ApplyCreditInvoice($sent['invoiceid']);
                                    // \Log::debug("==== appy");
                                    // \Log::debug($appy);
                                    //$admin->r($appy);
                                    // klo PAID
                                    if ($appy['result'] == 'success' && $appy['invoicepaid'] == 'true') {
                                        $nextdate = Carbon::parse($time_aktivite->value('jamaktivasi'))->addMinute(60);
                                        DB::table('jamaktivasi')
                                            ->where('id',$time_aktivite->value('id'))
                                            ->update(['jamaktivasi' => $nextdate]);
                                    } else {
                                        $suspend=$admin->suspend($vpsData->vpsID);
                                        DB::table('vps_kavm_invoice')->insert(
                                        ['product_id' => $vpsData->id, 'user_id' => $vpsData->userid,'vps_id' => $vpsData->vpsID, 'invoice' => $sent['invoiceid'], 'total' => (new Resourcefunctions)->getInvoiceTotalVPS($sent['invoiceid']), 'date_created' => date('Y-m-d') ]);
                                        
                                        
                                        DB::table('tblhosting')
                                            ->where('id', $vpsData->id)
                                            ->update(['domainstatus' => 'Suspended']);
                                                            
                                        //Suspend Notif via email kalau server di suspend akibat deposit kurang
                                        $msgEmail='
                                                    <p>
                                                        Layanan '.$vpsData->paket.' telah kami Suspended dikarenakan deposit anda tidak mencukupi
                                                        </p>
                                        
                                                    ';
                                        
                                        
                                        $postData=[
                                                    'customtype'		=> 'general',
                                                    'messagename' 		=> $msgEmail,
                                                    'id'				=> $vpsData->userid,
                                                    'custommessage'		=> $msgEmail,
                                                    'customsubject'		=> 'Suspended Layanan '.$vpsData->paket,
                                                    ];
                                            
                                            
                                        $results = (new \App\Helpers\HelperApi)->localAPI('SendEmail', $postData);
                                    }
                                }
                            }
                        } else {
                            /* sync jam jamaktivasi */
                            $star_flex=Carbon::now()->subMinutes(60)->format('Y-m-d H:i:s');
                            $end_flex=Carbon::now()->addMinutes(60)->format('Y-m-d H:i');
                            $time_aktivite = DB::table('jamaktivasi')
                                                    ->select('id')
                                                    ->where('product_id',$vpsData->id)
                                                    ->whereBetween('jamaktivasi', [$star_flex.':00', $end_flex.':59'])
                                                    ->where('type','vps')
                                                    ->first();
                            // \Log::debug("=== else time_aktivite");
                            // \Log::debug(json_encode($time_aktivite));
                            if(!$time_aktivite){
                                $cekativitas= DB::table('jamaktivasi')
                                                        ->select('id','jamaktivasi')
                                                        ->where('product_id',$vpsData->id)
                                                        ->where('type','vps');
                                if($cekativitas->value('id')){
                                    $gettimeFormexisting=Carbon::now()->format('Y-m-d H:i:s');
                                    //dd($gettimeFormexisting);die();
                                    DB::table('jamaktivasi')
                                                ->where('id',$cekativitas->value('id'))
                                                ->update(['jamaktivasi' => $gettimeFormexisting]);
                                }	
                            }	
                                
                            
                        }
                    
                    }else{
                        DB::table('jamaktivasi')->insert(['product_id' => $vpsData->id, 'jamaktivasi' => date("Y-m-d H:i:s"),'type' => 'vps']);
                    }
                    //end vps invoice
                    
                    //invoice addons RAM
                    $AdditionalRAM=DB::table('tblhostingconfigoptions')
                                        ->select('tblproductconfigoptionssub.optionname as qty')
                                        ->join('tblproductconfigoptionssub','tblhostingconfigoptions.optionid','=','tblproductconfigoptionssub.id')
                                        ->where('tblhostingconfigoptions.relid',$vpsData->id)
                                        ->where('tblhostingconfigoptions.configid',3)
                                        ->first();
                    // \Log::debug("=== additional ram");
                    // \Log::debug(isset($AdditionalRAM->qty) && intval($AdditionalRAM->qty) > 0);
                    if(isset($AdditionalRAM->qty) && intval($AdditionalRAM->qty) > 0 ){
                        $cekIntervalRam=DB::table('jamaktivasi')
                                ->where('product_id',$vpsData->id)
                                ->where('type','ram')
                                ->select('id');
                        //$admin->r($cekIntervalRam);
                        // \Log::debug("=== additional ram cekIntervalRam");
                        // \Log::debug($cekIntervalRam->value('id'));
                        if($cekIntervalRam->value('id')){
                            $time_aktiviteRAM = DB::table('jamaktivasi')
                                ->select('id','jamaktivasi')
                                ->whereBetween('jamaktivasi', [$range_2.':00', $range_1.':59'])
                                ->where('product_id',$vpsData->id)
                                ->where('type','ram')
                                ->first();
                            if($time_aktiviteRAM){
                            
                                $ramnya=intval($AdditionalRAM->qty) / 1024;
                                $getPrice=(new Resourcefunctions)->additional_addons('ram');
                                $hargaRam=$getPrice * $ramnya;
                                
                                $param=array();
                                $param['userid']			= $vpsData->userid;
                                $param['itemdescription1']	= $vpsData->paket.' ('.$vpsData->hostname.') Additional RAM up to '.$ramnya.' GB';
                                $param['itemamount1']	    = $hargaRam;
                                $param['itemtaxed1']		= false;
                                $param['sendinvoice']		= false;
                                //$i=$i+1;
                                //$admin->r($param);
                                $spek=(new Resourcefunctions)->paketVPS(trim($vpsData->paket));
                                /* echo '<pre>';
                                print_r($spek);
                                exit(); */
                                $ram=$spek['ram'];
                            
                                $sent=(new Resourcefunctions)->createInvoice($param);
                                // \Log::debug("===== createInvoice RAM");
                                // \Log::debug($sent);
                                if($sent['result'] == 'success'){
                                    $appy=(new Resourcefunctions)->ApplyCreditInvoice($sent['invoiceid']);
                                    //$admin->r($appy);
                                    if($appy['invoicepaid'] != 'true'){
                                        /* $vpsRAM=$ram * 1024;
                                        $paramUpgrade=[
                                                        'vpsid' => $vpsData->vpsid,
                                                        'ram'		=> $vpsRAM
                                                        ]; */
                                                        
                                        //$setDefaultRam = $admin->managevps($paramUpgrade);

                                                        
                                                        
                                        //$admin->r($paramUpgrade);exit();			
                                        //die(); 
                                        
                                        /*suspand*/
                                            $suspend=$admin->suspend($vpsData->vpsID);
                                            
                                            DB::table('tblhosting')
                                            ->where('id', $vpsData->id)
                                            ->update(['domainstatus' => 'Suspended']);
                                        
                                        
                                        
                                        
                                        //if(empty($setDefaultRam['error'])){
                                        
                                            DB::table('vps_kavm_invoice')->insert([
                                                                                'product_id' => $vpsData->id,
                                                                                'user_id' => $vpsData->userid,
                                                                                'vps_id' => $vpsData->vpsID,
                                                                                'invoice' => $sent['invoiceid'],
                                                                                'total' => (new Resourcefunctions)->getInvoiceTotalVPS($sent['invoiceid']),
                                                                                'date_created' => date('Y-m-d'),
                                                                                'type' => 'ram'
                                                                                ]); 
                                                                                
                                            /* $config=DB::table('tblproductconfigoptions')->where('id',3)->select('id')->first();
                                            $option=DB::table('tblproductconfigoptionssub')
                                                    ->where('configid',$config->id)
                                                    ->where('optionname',0)
                                                    ->select('id')->first();
                                            
                                            DB::table('tblhostingconfigoptions')
                                                        ->where('relid', $vpsData->id)
                                                        ->where('configid', $config->id)
                                                        ->update(['optionid' => $option->id]); */
                                                                                
                                        //}
                                    }else{
                                        
                                        $nextdate = Carbon::parse($time_aktiviteRAM->jamaktivasi)->addMinute(60);
                                        DB::table('jamaktivasi')
                                        ->where('id',$time_aktiviteRAM->id)
                                        ->update(['jamaktivasi' => $nextdate->toDateTimeString()]);
                                    }
                                }
                                
                            }	
                                
                        }else{
                            DB::table('jamaktivasi')->insert(['product_id' => $vpsData->id, 'jamaktivasi' => date("Y-m-d H:i:s"),'type' => 'ram']);
                        }
                            
                    }
                    // set inv core
                    // generate invoice
                        
                    $AdditionalCores=DB::table('tblhostingconfigoptions')
                            ->select('tblproductconfigoptionssub.optionname as qty')
                            ->join('tblproductconfigoptionssub','tblhostingconfigoptions.optionid','=','tblproductconfigoptionssub.id')
                            ->where('tblhostingconfigoptions.relid',$vpsData->id)
                            ->where('tblhostingconfigoptions.configid',1)
                            ->first();
                                
                                
                                
                    //$admin->r($AdditionalCores);
                    // \Log::debug("=== AdditionalCores");
                    // \Log::debug(isset($AdditionalCores->qty) && intval($AdditionalCores->qty) > 0);
                    if(isset($AdditionalCores->qty) && intval($AdditionalCores->qty) > 0){
                        $cekIntervalCPU=DB::table('jamaktivasi')
                                ->where('product_id',$vpsData->id)
                                ->where('type','cpu')
                                ->select('id');
                        //$admin->r($cekIntervalRam);
                        if($cekIntervalCPU->value('id')){
                            $time_aktiviteCPU = DB::table('jamaktivasi')
                                ->select('id','jamaktivasi')
                                ->whereBetween('jamaktivasi', [$range_2.':00', $range_1.':59'])
                                ->where('product_id',$vpsData->id)
                                ->where('type','cpu')
                                ->first();
                                
                            if($time_aktiviteCPU){
                        
                        
                                //$getPrice=addonsInvoice('core',$getVPS_paket['core'],intval($AdditionalCores->qty));
                                $getPrice=(new Resourcefunctions)->additional_addons('core');
                                $hargaCore=$getPrice * intval($AdditionalCores->qty);
                                
                                $param=array();
                                $param['userid']			= $vpsData->userid;
                                $param['itemdescription1']	= $vpsData->paket.' ('.$vpsData->hostname.') Additional Cores Up to '. intval($AdditionalCores->qty).' Core';
                                $param['itemamount1']	 	= $hargaCore;
                                $param['itemtaxed1']		= false;
                                $param['sendinvoice']		= false;
                                
                                $spek=(new Resourcefunctions)->paketVPS(trim($vpsData->paket));
                                /* echo '<pre>';
                                print_r($spek);
                                exit(); */
                                $core=$spek['core'];
                            
                                $sent=(new Resourcefunctions)->createInvoice($param);
                                // \Log::debug("===== createInvoice CORE");
                                // \Log::debug($sent);
                                if($sent['result'] == 'success'){
                                    $appy=(new Resourcefunctions)->ApplyCreditInvoice($sent['invoiceid']);
                                    //$admin->r($appy);
                                    if($appy['invoicepaid'] != 'true'){
                                        //$vpsRAM=$ram * 1024;
                                        /* $paramUpgrade=[
                                                        'vpsid' => $vpsData->vpsid,
                                                        'cores'		=> $core
                                                        ]; */
                                        //$admin->r($paramUpgrade);exit();			
                                        //die(); 
                                        //$setDefaultRam = $admin->managevps($paramUpgrade);
                                        //if(empty($setDefaultRam['error'])){
                                        
                                            $suspend=$admin->suspend($vpsData->vpsID);

                                            DB::table('tblhosting')
                                            ->where('id', $vpsData->id)
                                            ->update(['domainstatus' => 'Suspended']);
                                        
                                        
                                            DB::table('vps_kavm_invoice')->insert([
                                                                                'product_id' => $vpsData->id,
                                                                                'user_id' => $vpsData->userid,
                                                                                'vps_id' => $vpsData->vpsID,
                                                                                'invoice' => $sent['invoiceid'],
                                                                                'total' => (new Resourcefunctions)->getInvoiceTotalVPS($sent['invoiceid']),
                                                                                'date_created' => date('Y-m-d'),
                                                                                'type' => 'cpu'
                                                                                ]); 
                                                                                
                                            /* $config=DB::table('tblproductconfigoptions')->where('id',1)->select('id')->first();
                                            $option=DB::table('tblproductconfigoptionssub')
                                                    ->where('configid',$config->id)
                                                    ->where('optionname',0)
                                                    ->select('id')->first();
                                            
                                            DB::table('tblhostingconfigoptions')
                                                        ->where('relid', $vpsData->id)
                                                        ->where('configid', $config->id)
                                                        ->update(['optionid' => $option->id]); */
                                                                                
                                        //}
                                    }else{
                                        
                                        $nextdate = Carbon::parse($time_aktiviteCPU->jamaktivasi)->addMinute(60);
                                        DB::table('jamaktivasi')
                                        ->where('id',$time_aktiviteCPU->id)
                                        ->update(['jamaktivasi' => $nextdate->toDateTimeString()]);
                                    }
                                }
                                
            
                            }
                            
                        }else{
                            
                            DB::table('jamaktivasi')->insert(['product_id' => $vpsData->id, 'jamaktivasi' => date("Y-m-d H:i:s"),'type' => 'cpu']);
                            
                        }
                        
                    } 
                    //end---
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            // \Log::debug($e);
        }
    }
}

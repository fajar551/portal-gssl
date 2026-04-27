<?php

namespace Modules\Servers\Virtualizor\Helpers;

class Resourcefunctions
{
   function total_price_CPU($cpu, $price)
   {
      $cpu   = (int) $cpu;
      $price    = (int) $price;
      $total = $cpu * $price;
      return $total;
   }


   function total_price_RAM($ram, $price)
   {
      $ram   = (int) $ram;
      $price   = (int) $price;
      $total = ($ram / 1024) * $price;
      return $total;
   }

   function total_price_HDD($hdd, $price)
   {
      $hdd   = (int) $hdd;
      $price   = (int) $price;
      $total = $hdd * $price;
      return $total;
   }

   function getClient($email)
   {
      $command = 'GetClientsDetails';
      $postData = array(
         'email' => $email,
         /* 'stats' => true, */
      );
      $adminUsername = ''; // Optional for WHMCS 7.2 and later
      $localApi =  new \App\Helpers\HelperApi();
      $results = $localApi->localAPI($command, $postData, $adminUsername);
      //print_r($results);
      if ($results['result'] == 'success') {
         return $results['userid'];
      } else {
         return false;
      }
   }

   function getInvoiceTotal($invoiceid)
   {
      $command = 'GetInvoice';
      $postData = array(
         'invoiceid' => $invoiceid,
      );
      $adminUsername = '';
      $localApi =  new \App\Helpers\HelperApi();
      $results = $localApi->localAPI($command, $postData, $adminUsername);
      //print_r($results);
      if ($results['result'] == 'success') {
         return $results['total'];
      } else {
         return false;
      }
   }

   function ApplyCreditInvoice($invoiceid)
   {
      /*get invoice*/
      $amount = $this->getInvoiceTotal($invoiceid);
      // dd($invoiceid);
      $command = 'ApplyCredit';
      $postData = array(
         'invoiceid' => $invoiceid,
         'amount'   => $amount,
         'noemail' => true,
      );
      $adminUsername = ''; // Optional for WHMCS 7.2 and later
      $localApi =  new \App\Helpers\HelperApi();
      $results = $localApi->localAPI($command, $postData, $adminUsername);
      // \Log::debug($result);
      return $results;
   }



   function cekDeposit($user)
   {
      $command = 'GetClientsDetails';
      $postData = array(
         'clientid' => $user,
         'stats' => true,
      );
      $adminUsername = '';
      $localApi =  new \App\Helpers\HelperApi();
      $results = $localApi->localAPI($command, $postData, $adminUsername);
      return  @$results['credit'];
   }



   function paketVPS($param)
   {

      $paket['Flex 1'] = array(
         'core'       => 1,
         'ram'       => 1,
         'space'      => 25,
         'bandwidth'   => 1000,
         'perday'   => 4800,
         'perhour'   => 200
      );
      $paket['Flex 2'] = array(
         'core'       => 1,
         'ram'       => 2,
         'space'      => 50,
         'bandwidth'   => 2000,
         'perday'   => 8400,
         'perhour'   => 350
      );
      /* $paket['Flex 3']=array(
                            'core' 		=> 2,
                            'ram' 		=> 4,
                            'space'		=> 80,
                            'bandwidth'	=> 4000,
                            'perday'	=> 12960,
                            'perhour'	=> 540
                        ); */
      $paket['Flex 4'] = array(
         'core'       => 2,
         'ram'       => 4,
         'space'      => 80,
         'bandwidth'   => 4000,
         'perday'   => 13200,
         'perhour'   => 550
      );
      $paket['Flex 8'] = array(
         'core'       => 4,
         'ram'       => 8,
         'space'      => 160,
         'bandwidth'   => 5000,
         'perday'   => 19680,
         'perhour'   => 820
      );
      $paket['Flex 16'] = array(
         'core'       => 6,
         'ram'       => 16,
         'space'      => 320,
         'bandwidth'   => 8000,
         'perday'   => 43200,
         'perhour'   => 1800
      );
      $paket['Flex 32'] = array(
         'core'       => 8,
         'ram'       => 32,
         'space'      => 640,
         'bandwidth'   => 16000,
         'perday'   => 86400,
         'perhour'   => 3600
      );

      return $paket[$param];
   }



   function addons($type, $default, $ukuran)
   {

      switch ($type) {
         case 'core':
            $format = [1 => 1, 2 => 2, 4 => 3, 6 => 4, 8 => 5, 16 => 6];

            $perhour = 150;
            $perday = 3600;

            $formatDefault = $format[$default];
            $FormatUpgrade = $format[$ukuran];

            $return['price'] = ($FormatUpgrade - $formatDefault) * $perhour;
            $return['up'] = ($FormatUpgrade - $formatDefault);
            return $return;
            break;
         case 'ram':
            $format = [1 => 1, 2 => 2, 4 => 3, 6 => 4, 8 => 5, 16 => 6];
            $perhour = 150;
            $perday = 3600;

            $formatDefault = $default;
            $FormatUpgrade = $format[$ukuran];
            $return['price'] = ($FormatUpgrade - $formatDefault) * $perhour;
            $return['up'] = ($FormatUpgrade - $formatDefault);
            return $return;
            break;

         case 'bandwidth':
            $perhour = 150;
            $perday = 3600;
            /* return $ukuran * $perhour; */
            $return['price'] = $ukuran * $perhour;
            $return['up'] = $ukuran;
            return $return;
            break;
         default:
            return array();
      }
   }

   function additional_addons($param = 'ram')
   {

      $obj = [
         'ram'    => 150,
         'core'   => 150

      ];


      return $obj[$param];
   }



   /* 
    function addonsInvoice($type,$default,$ukuran){
        
        switch($type){
            case 'core':
                    $format=[1=>1,2=>2,4=>3,6=>4,8=>5,16=>6];
                    $perhour=125;
                    $perday=3000;
                    
                    $formatDefault=$format[$default];
                    $FormatUpgrade=$format[$ukuran];
                    
                    $tot=$FormatUpgrade - $formatDefault;
                    $return['price']=$tot * $perhour;
                    $return['up']=$tot;
                    return $return;
            break;
            case 'ram':
                    $format=[1=>1,2=>2,4=>3,6=>4,8=>5,16=>6];
                    $perhour=125;
                    $perday=3000;
                    //echo $default;
                    $formatDefault=$format[$default];
                    $FormatUpgrade=$format[$ukuran];
                    $tot=$FormatUpgrade - $formatDefault;
                    $return['price']=$tot * $perhour;
                    $return['up']=$tot;
                    return $return;
            break;
            
            case 'bandwidth':
                    $perhour=150;
                    $perday=3600;
                    
                    $return['price']=$ukuran * $perhour;
                    $return['up']=$ukuran;
                    return $return;
            break;
            default:
                    return array();
            
        }
    }
     */
   function addonsInvoice($type, $default, $ukuran)
   {

      switch ($type) {
         case 'core':
            $format = [1 => 1, 2 => 2, 4 => 3, 6 => 4, 8 => 5, 16 => 6];
            $perhour = 150;
            $perday = 3600;

            $formatDefault = $format[$default];
            $FormatUpgrade = $format[$ukuran];

            $tot = $ukuran;
            $return['price'] = $tot * $perhour;
            $return['up'] = $ukuran;;
            return $return;
            break;
         case 'ram':
            $format = [1 => 1, 2 => 2, 4 => 3, 6 => 4, 8 => 5, 16 => 6];
            $perhour = 150;
            $perday = 150;
            //echo $default;
            $formatDefault = $format[$default];
            $FormatUpgrade = $format[$ukuran];
            $tot = $ukuran;
            $return['price'] = $tot * $perhour;
            $return['up'] = $ukuran;
            return $return;
            break;

         case 'bandwidth':
            $perhour = 150;
            $perday = 3600;
            /* return $ukuran * $perhour; */
            $return['price'] = $ukuran * $perhour;
            $return['up'] = $ukuran;
            return $return;
            break;
         default:
            return array();
      }
   }

   function createInvoice($postData)
   {
      $command = 'CreateInvoice';
      $adminUsername = '';
      $localApi =  new \App\Helpers\HelperApi();
      $results = $localApi->localAPI($command, $postData, $adminUsername);
      return $results;
      //print_r($results);
      /* if($result['result'] == 'success '){
            Capsule::table('vps_kavm_invoice')->insert(
                ['user_id' => $postData['userid'],'vps_id' => $vpsID, 'invoice' => $result['invoiceid'], 'total' => getInvoiceTotalVPS($result['invoiceid'])   ]
            );
            
        } */
   }


   function getInvoiceTotalVPS($invoiceid)
   {
      $command = 'GetInvoice';
      $postData = array(
         'invoiceid' => $invoiceid,
      );
      $adminUsername = '';
      $localApi =  new \App\Helpers\HelperApi();
      $results = $localApi->localAPI($command, $postData, $adminUsername);
      //print_r($results);
      return $results['total'];
   }


   function cekDepositUser($id)
   {

      $command = 'GetClientsDetails';
      $postData = array(
         'clientid' => $id
      );
      $adminUsername = '';

      $localApi =  new \App\Helpers\HelperApi();
      $results = $localApi->localAPI($command, $postData, $adminUsername);
      //print_r($results);
      if ($results['result'] == 'success') {
         return $results['client']['credit'];
      } else {
         return 0;
      }
   }
}

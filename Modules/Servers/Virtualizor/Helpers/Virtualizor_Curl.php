<?php

namespace Modules\Servers\Virtualizor\Helpers;
use DB;
use Modules\Servers\Virtualizor\Helpers\Functions;

class Virtualizor_Curl {

	public static function fix_uuid_field(){
		// vps_uuid of virtualizor
		$query = DB::table('tblcustomfields')
		        ->select(DB::raw('relid, id'))
				->where('fieldname', 'vps_uuid')
				->get();
				
		$products = array();
		$check_products = 0;
		foreach($query as $q){
		    $products[$q->relid][$q->id] = (array) $q;
		    if(count($products[$q->relid]) > 1){
		    	$check_products = 1;
		    }
		}
		
		if(!empty($products) && !empty($check_products)){
		
		    foreach($products as $relid => $rows){
		        if(count($rows) == 1){
		            unset($products[$relid]);
		            continue;
		        }
		        $delete = 0;
		        foreach($rows as $id => $row){
		            //skip first value
		            if(!empty($delete)){
            			DB::table('tblcustomfieldsvalues')->where('fieldid', $id)->delete();
                		DB::table('tblcustomfields')->where('id', $id)->delete();
		            }
		            $delete = 1;
		        }
		    }
		    
		}
		
	}

	public static function create_uuid_field($pid, $serviceid, $uuid){
		
		Virtualizor_Curl::fix_uuid_field();
		
		// vps_uuid of virtualizor
		$query = DB::table('tblcustomfields')
				->where('relid', $pid)
				->where('fieldname', 'vps_uuid')
				->get();
		$result = $query->count() > 0 ? (array) $query[0] : ['id' => '', 'relid' => ''];
		$fieldid = $result['id'];
        
		//logActivity('$result:'.var_export($result,1));
		
		// We will check if there is an entry if not we will insert it.
		$query1 = DB::table('tblcustomfieldsvalues')
				->where('relid', $serviceid)
				->where('fieldid', $result['id'])
				->get();
		$sel_res = $query1->count() > 0 ? (array) $query1[0] : ['value' => ''];

		//logActivity('$sel_res:'.var_export($sel_res,1));

		if($query1->count() > 0 && empty($sel_res['value'])){

			DB::table('tblcustomfieldsvalues')
				->where('relid', $serviceid)
				->where('fieldid', $result['id'])
				->update(
					array('value' => $uuid)
				);
		}

		if(empty($result['relid'])){

			$fieldid = DB::table('tblcustomfields')
				->insertGetId(array(
					'type' => 'product',
					'relid' => $pid,
					'fieldname' => 'vps_uuid',
					'fieldtype' => 'text',
					'adminonly' => 'on'
				));

		}
		
		if($query1->count() <= 0 && empty($sel_res['value'])){
		
		    $insertvalues = DB::table('tblcustomfieldsvalues')
			->insert(array(
				'value' => $uuid,
				'relid' => $serviceid,
				'fieldid' => $fieldid
			));
			
		}
	}
	
	public static function error($ip = ''){
		
		$err = '';
		
		if(!empty($GLOBALS['virt_curl_err'])){
			$err .= ' Curl Error: '.$GLOBALS['virt_curl_err'];
		}
		
		if(!empty($ip)){
			$err .= ' (Server IP : '.$ip.')';
		}
		
		return $err;
	}
	
	public static function make_api_call($ip, $pass, $path, $data = array(), $post = array(), $cookies = array()){
		
		global $virtualizor_conf, $whmcsmysql;
		
		$key = (new Functions)->generateRandStr(8);
		$apikey = (new Functions)->make_apikey($key, $pass);
		
		$url = 'https://'.$ip.':4085/'.$path;	
		$url .= (strstr($url, '?') ? '' : '?');	
		$url .= '&api=serialize&apikey='.rawurlencode($apikey).'&skip_callback=1';
		
		// Pass some data if there
		if(!empty($data)){
			$url .= '&apidata='.rawurlencode(base64_encode(serialize($data)));
		}
	
		if($virtualizor_conf['loglevel'] > 0){
			\App\Helpers\LogActivity::Save('URL : '. $url);
		}
		
		// Set the curl parameters.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
			
		// Time OUT
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
		
		// Turn off the server and peer verification (TrustManager Concept).
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
			
		// UserAgent
		curl_setopt($ch, CURLOPT_USERAGENT, 'Softaculous');
		
		// Cookies
		if(!empty($cookies)){
			curl_setopt($ch, CURLOPT_COOKIESESSION, true);
			curl_setopt($ch, CURLOPT_COOKIE, http_build_query($cookies, '', '; '));
		}
		
		if(!empty($post)){
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
		}
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		// Get response from the server.
		$resp = curl_exec($ch);
		
		if(empty($resp)){
            $GLOBALS['virt_curl_err'] = curl_error($ch);
		}
        
		curl_close($ch);
		
		// The following line is a method to test
		//if(preg_match('/sync/is', $url)) echo $resp;
		
		if(empty($resp)){
			return false;
		}
		
		// As a security prevention measure - Though this cannot happen
		$resp = str_replace($pass, '12345678901234567890123456789012', $resp);
		
		$r = (new Functions)->_unserialize($resp);
		
		if(empty($r)){
			return false;
		}
		
		return $r;
	}	

	public static function e_make_api_call($ip, $pass, $vid, $path, $post = array()){
		$key = (new Functions)->generateRandStr(8);
		$apikey = (new Functions)->make_apikey($key, $pass);
		
		$url = 'https://'.$ip.':4083/'.$path;	
		$url .= (strstr($url, '?') ? '' : '?');	
		$url .= '&svs='.$vid.'&api=serialize&apikey='.rawurlencode($apikey).'&skip_callback=whmcs';
		
		// Set the curl parameters.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		
		// Time OUT
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
		
		// Turn off the server and peer verification (TrustManager Concept).
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
			
		// UserAgent and Cookies
		curl_setopt($ch, CURLOPT_USERAGENT, 'Softaculous');
		
		if(!empty($post)){
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
		}
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		// Get response from the server.
		$resp = curl_exec($ch);
		curl_close($ch);
		
		// The following line is a method to test
		//if(preg_match('/os/is', $url)) echo $resp;
		
		if(empty($resp)){
			return false;
		}
		
		// As a security prevention measure - Though this cannot happen
		$resp = str_replace($pass, '12345678901234567890123456789012', $resp);
		
		$r = (new Functions)->_unserialize($resp);
		
		if(empty($r)){
			return false;
		}
		
		return $r;
	}	
	
	public static function action($params, $action, $post = array()){
		
		global $virt_verify, $virt_errors;

		$id = $params['customfields']['vpsid'];
		
		if(!empty($params['customfields']['vps_uuid'])){
			$post['uuid'] = $params['customfields']['vps_uuid'];
		}
		
		// Make the call
		$response = Virtualizor_Curl::e_make_api_call($params["serverip"], $params["serverpassword"], $id, 'index.php?'.$action, $post);

		if(empty($response)){
			$virt_errors[] = 'The action could not be completed as no response was received.';
			return false;
		}
		
		return $response;
	
	} // function virt_curl_action ends	
	
	public static function curl_call($url, $header = 1, $time = 1, $post = array(), $cookie = ''){
	
		global $globals;
		
		// Set the curl parameters.
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $time);

		// Turn off the server and peer verification (TrustManager Concept).
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		
		// Follow redirects
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
				
		if(!empty($post)){
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
		}
		
		// Is there a Cookie
		if(!empty($cookie)){
			curl_setopt($ch, CURLOPT_COOKIESESSION, true);
			curl_setopt($ch, CURLOPT_COOKIE, $cookie);
		}
		
		if($header){
		
			curl_setopt($ch, CURLOPT_HEADER, 1);
			curl_setopt($ch, CURLOPT_NOBODY, true);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:2.0.1) Gecko/20100101 Firefox/4.0.1');
			
		}

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		// Get response from the server.
		$resp = curl_exec($ch);

		//echo curl_error($ch);
		
		return $resp;
		
	}


}

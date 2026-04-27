<?php

namespace Modules\Servers\Virtualizor\Helpers;

class Functions
{
    function generateRandStr($length){	
        $randstr = "";	
        for($i = 0; $i < $length; $i++){	
            $randnum = mt_rand(0,61);		
            if($randnum < 10){		
                $randstr .= chr($randnum+48);			
            }elseif($randnum < 36){		
                $randstr .= chr($randnum+55);			
            }else{		
                $randstr .= chr($randnum+61);			
            }		
        }	
        return strtolower($randstr);	
    }

    function make_apikey($key, $pass){
        return $key.md5($pass.$key);
    }

    function _unserialize($str){

		$var = @unserialize($str);
		
		if(empty($var)){
			
			preg_match_all('!s:(\d+):"(.*?)";!s', $str, $matches);
			foreach($matches[2] as $mk => $mv){
				$tmp_str = 's:'.strlen($mv).':"'.$mv.'";';
				$str = str_replace($matches[0][$mk], $tmp_str, $str);
			}
			$var = @unserialize($str);
		
		}
		
		//If it is still empty false
		if(empty($var)){
		
			return false;
		
		}else{
		
			return $var;
		
		}
	
	}

    function v_fn($f){
        global $virtualizor_conf;
        
        if(empty($virtualizor_conf['fields'][$f])){
            $r = $f;
        }else{
            $r = $virtualizor_conf['fields'][$f];
        }
        
        return $r;	
    }

    function virt_add_cpanel_license($params) {
	
        include_once(dirname(__FILE__).'/sdk/cpl.inc.php');
        
        global $lisc, $cpl, $virtualizor_conf;
        
        $id = $params['serviceid'];
        $query = "SELECT * FROM `tblhosting` WHERE id='$id'";
        $result = mysql_query($query);
        $row = mysql_fetch_array($result);
        
        $ip = $row['dedicatedip'];
        
        if(!$ip){
            v_logActivity("No dedicated IP found.");
        }
        
        v_logActivity("Let's print ID for verification purpose:" . $id);
    
        $cpl = new cPanelLicensing($virtualizor_conf['cp']['cpanel_manage2_username'], $virtualizor_conf['cp']['cpanel_manage2_password']);
        $groupid = $cpl->findKey($virtualizor_conf['cp']['cpanel_manage2_group'], $cpl->fetchGroups());
        $packageid = $cpl->findKey($virtualizor_conf['cp']['cpanel_manage2_pkg'], $cpl->fetchPackages());
    
        $query = 'SELECT `id` FROM `tblproducts` WHERE `name`="'.$virtualizor_conf['cp']['cpanel_manage2_pkg'].'"';
        $result = full_query($query);
        $DBproductID = mysql_fetch_assoc($result);
    
        $query = 'SELECT `id` FROM `tblproductgroups` WHERE `name`="'.$virtualizor_conf['cp']['cpanel_manage2_group'].'"';
        $result = full_query($query);
        $DBgroupID = mysql_fetch_assoc($result);
        
        $lisc = $cpl->activateLicense(array(
            "ip" => $ip,
            "groupid" => $groupid,
            "packageid" => $packageid,
            "force" => 1
            )
        );
        
        if ($lisc['@attributes']['status'] > 0) {
            v_logActivity($lisc['@attributes']['reason']);
        } else {
            v_logActivity("License add failed: " . $lisc['@attributes']['reason']);
        }
    }
}

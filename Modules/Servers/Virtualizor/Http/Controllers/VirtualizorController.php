<?php

namespace Modules\Servers\Virtualizor\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request as Req;
use Illuminate\Support\Facades\Request;
use Illuminate\Routing\Controller;
use Modules\Servers\Virtualizor\Helpers\Virtualizor_Curl;
use Modules\Servers\Virtualizor\Helpers\Functions;
use Modules\Servers\Virtualizor\Helpers\Resourcefunctions;
use Modules\Servers\Virtualizor\Sdk\Virtualizor_Admin_API;
use DB;

include_once('virtualizor_conf.php');
include_once('FunctionsVirtualizor.php');
class VirtualizorController extends Controller
{

   public function index()
   {
      $module = \Module::find('virtualizor');
      $html = file_get_contents($module->getPath() . "/Resources/assets/index.html");
      Request::get('give') . "#act=vpsmanage";
      return $html;
   }
   public function ConfigOptions(array $params)
   {

      global $virtualizor_conf;

      // Get the Servers
      $res = DB::table('tblservers')->where('type', 'Virtualizor')->get();

      if ($res->count() <= 0) {
         throw new \Exception("The virtualizor servers could not be found. Please add the Virtualizor Server and Server group to proceed");

         return;
      }
      $server_list = array();

      foreach ($res as $re) {
         $server_list[$re->id] = $re->id . ' - ' . trim($re->name);
         $server_data[$re->id] = (array) $re;
      }

      # Should return an array of the module options for each product - Minimum of 24
      $config_array = array(
         "Type" => array("Type" => "dropdown", "Options" => "OpenVZ,Xen PV,Xen HVM,KVM,XCP HVM,XCP PV,LXC,Virtuozzo OpenVZ,Virtuozzo KVM,Proxmox KVM,Proxmox OpenVZ,Proxmox LXC"),
         "DiskSpace" => array("Type" => "text", "Size" => "25", "Description" => "GB"),
         "Inodes" => array("Type" => "text", "Size" => "25", "Description" => " (OpenVZ)"),
         "Guaranteed RAM" => array("Type" => "text", "Size" => "25", "Description" => "MB"),
         "Burstable RAM" => array("Type" => "text", "Size" => "25", "Description" => "MB (OpenVZ, Proxmox OpenVZ, Virtuozzo OpenVZ)"),
         "SWAP RAM" => array("Type" => "text", "Size" => "25", "Description" => "MB (Xen, XCP, KVM, LXC, Virtuozzo KVM, Proxmox KVM, Proxmox LXC)"),
         "Bandwidth" => array("Type" => "text", "Size" => "25", "Description" => "GB (Zero or empty for unlimited)"),
         "CPU Units" => array("Type" => "text", "Size" => "25", "Description" => "Units"),
         "CPU Cores" => array("Type" => "text", "Size" => "25", "Description" => ""),
         "CPU%" => array("Type" => "text", "Size" => "25", "Description" => ""),
         "I/O Priority" => array("Type" => "dropdown", "Options" => "0,1,2,3,4,5,6,7", "Description" => "(OpenVZ)"),
         "VNC" => array("Type" => "yesno", "Description" => "Enable VNC (Xen, XCP, KVM, Virtuozzo)"),
         "IPs" => array("Type" => "text", "Size" => "25", "Description" => "Number of IPs"),
         "Network Speed" => array("Type" => "text", "Size" => "25", "Description" => "KB/s (Zero or empty for unlimited)"),
         "Server" => array("Type" => "text", "Size" => "25", "Description" => "Slave Servers name if any"),
         "Server Group" => array("Type" => "text", "Size" => "25", "Description" => "To choose a server"),
         "IPv6" => array("Type" => "text", "Size" => "25", "Description" => "Number of IPv6 Address"),
         "IPv6 Subnets" => array("Type" => "text", "Size" => "25", "Description" => "Number of IPv6 Subnets"),
         "Internal IP Address" => array("Type" => "text", "Size" => "25", "Description" => "Number of Internal IP Address"),
      );

      // Get the product ID
      $pid = (int) $_REQUEST['id'];

      // First get the configoption1 to check if the user is on OLD method or New method.
      $res = DB::table('tblproducts')->where('id', $pid)->get();

      $row = (array) $res[0];
      //rprint($row);

      $configarray = array(
         'Virtualizor Servers' => array("Type" => "dropdown", "Options" => implode(',', array_values($server_list))),
         'Type' => array("Type" => "dropdown", "Options" => 'OpenVZ,Xen PV,Xen HVM,KVM,XCP HVM,XCP PV,LXC,Virtuozzo OpenVZ,Virtuozzo KVM,Proxmox KVM,Proxmox OpenVZ,Proxmox LXC'),
         'Select Plan' => array("Type" => "dropdown", "Options" => ''),
      );

      // If this is filled up then user is using the OLD method
      if ((!empty($row['configoption1']) && in_array($row['configoption1'], array('OpenVZ', 'Xen PV', 'Xen HVM', 'KVM', 'XCP HVM', 'XCP PV', 'LXC', 'Virtuozzo OpenVZ', 'Virtuozzo KVM', 'Proxmox KVM', 'Proxmox OpenVZ', 'Proxmox LXC'))) || !empty($virtualizor_conf['no_virt_plans'])) {
         //array_values($server_list)
         $tmp_type = array('OpenVZ', 'Xen PV', 'Xen HVM', 'KVM', 'XCP HVM', 'XCP PV', 'LXC', 'Virtuozzo OpenVZ', 'Virtuozzo KVM', 'Proxmox KVM', 'Proxmox OpenVZ', 'Proxmox LXC');
         array_push($tmp_type, implode(',', array_values($server_list)));

         $config_array['Type']['Options'] = implode(',', $tmp_type);
         $configarray = $config_array;

         // If we get the Virtualizor server in configoption1, we will make an API call and load other fields
      } elseif (!empty($row['configoption1']) && in_array($row['configoption1'], array_values($server_list))) {
         // Get the server ID
         $ser_id = array_search($row['configoption1'], $server_list);
         $ser_data = $server_data[$ser_id];

         //$configarray['Virtualizor Servers'] = array("Type" => "dropdown", "Options" => implode(',', array_values($server_list)));
         $tmp_hostname = $ser_data['hostname'];
         if (empty($tmp_hostname)) {
            $tmp_hostname = $ser_data['ipaddress'];
         }
         // Get the data from virtualizor
         $data = Virtualizor_Curl::make_api_call($tmp_hostname, $this->get_server_pass_from_cbms($ser_data["password"]), 'index.php?act=addvs');

         //rprint($data);
         //rprint($row);

         if (empty($data)) {
            //echo '<font color="red">Could not load the server data.'.Virtualizor_Curl::error($ser_data["ipaddress"]).'</font>';
            return $configarray;
         }

         $virttype = (preg_match('/xen/is', $data['resources']['virt']) ? 'xen' : (preg_match('/xcp/is', $data['resources']['virt']) ? 'xcp' : strtolower($data['resources']['virt'])));

         $hvm = (preg_match('/hvm/is', $row['configoption2']) ? 1 : 0);

         // Build the options list to show Plans
         foreach ($data['plans'] as $k => $v) {
            $tmp_plans[$v['plid']] = $v['plid'] . ' - ' . $v['plan_name'];
         }

         //rprint($data['oses']);
         if (!empty($row['configoption2']) && in_array($row['configoption2'], array('OpenVZ', 'Xen PV', 'Xen HVM', 'KVM', 'XCP HVM', 'XCP PV', 'LXC'))) {

            // Build the options list to show OS
            foreach ($data['oses'] as $ok => $ov) {

               // If we do not get the virttype Which
               if (!preg_match('/' . $virttype . '/is', $ov['type'])) {
                  continue;
               }

               // Xen/XCP Stuff!
               if ($virttype == 'xen' || $virttype == 'xcp') {

                  // Xen/XCP HVM templates
                  if (!empty($hvm) && empty($ov['hvm'])) {
                     continue;

                     // Xen/XCP PV templates
                  } elseif (empty($hvm) && !empty($ov['hvm'])) {
                     continue;
                  }
               }

               $tmp_oses[$ok] = $ok . ' - ' . $ov['name'];
            }
         }
         //rprint($tmp_oses);

         // Build the default node / group field
         $tmp_default_node_grp['Auto Select Server'] = 'Auto Select Server';

         foreach ($data['servergroups'] as $k => $v) {

            $tmp_default_node_grp[$k] = $k . ' - [G] ' . $v['sg_name'];

            foreach ($data['servers'] as $m => $n) {
               if ($n['sgid'] == $k) {
                  $tmp_default_node_grp[$n['server_name']] = $m . " - " . $n['server_name'];
               }
            }
         }

         $configarray['Select Plan'] = array("Type" => "dropdown", "Options" => implode(',', $tmp_plans));
         $configarray['Default Node/ Group'] = array("Type" => "dropdown", "Options" => implode(',', array_values($tmp_default_node_grp)), "Description" => '[G] = Group Name');
         //$configarray['Operating System'] = array("Type" => "dropdown", "Options" => ' -- ,'.implode(',', $tmp_oses));

      }

      return $configarray;
   }

   public function CreateAccount($params)
   {

      global $virtualizor_conf;

      # ** The variables listed below are passed into all module functions **

      $loglevel = (int) @$_REQUEST['loglevel'];

      if (!empty($virtualizor_conf['loglevel'])) {
         $loglevel = $virtualizor_conf['loglevel'];
      }

      $serviceid = $params["serviceid"]; # Unique ID of the product/service in the WHMCS Database
      $pid = $params["pid"]; # Product/Service ID
      $producttype = $params["producttype"]; # Product Type: hostingaccount, reselleraccount, server or other
      $domain = $params["domain"];
      $username = $params["username"];
      $password = $params["password"];
      $clientsdetails = $params["clientsdetails"]; # Array of clients details - firstname, lastname, email, country, etc...
      $customfields = $params["customfields"]; # Array of custom field values for the product
      $configoptions = $params["configoptions"]; # Array of configurable option values for the product

      if (empty($customfields)) {
         $customfields = $this->virtualizor_getcustomfields($params['serviceid']);
      }

      if (!empty($customfields['vpsid'])) {
         return 'The VPS exists';
      }

      // New Module detection
      // If it is a new module then it will not have KVM or OPENVZ....
      if (!in_array($params['configoption1'], array('OpenVZ', 'Xen PV', 'Xen HVM', 'KVM', 'XCP HVM', 'XCP PV', 'LXC',  'Virtuozzo OpenVZ', 'Virtuozzo KVM', 'Proxmox KVM', 'Proxmox OpenVZ', 'Proxmox LXC'))) {

         $server_group = '';
         $slave_server = '';

         if (isset($params['configoptions'][(new Functions)->v_fn('slave_server')]) && $params['configoptions'][(new Functions)->v_fn('slave_server')] != 'none') {
            $params['configoption4'] = $params['configoptions'][(new Functions)->v_fn('slave_server')];
         }

         // Is it a Server group ?
         if (preg_match('/\[G\]/s', $params['configoption4'])) {
            //$server_group = str_replace('[G] ', '', $params['configoption4']);
            //$server_group = trim($server_group);
            $tmp_sg = array();
            $tmp_sg = explode('- [', $params['configoption4']);
            $server_group = trim($tmp_sg[0]);
         }

         // If we do not get server group we will search it for slave server
         if ($server_group == '') {
            // Is user wants auto selection from server?
            if ($params['configoption4'] == 'Auto Select Server') {

               $slave_server = 'auto';

               // Or is it a particular Slave server ?
            } else {

               $tmp_ss = array();
               $tmp_ss = explode("-", (string)$params['configoption4']);
               $slave_server = trim($tmp_ss[0]);
            }
         }

         $post['server_group'] = $server_group;
         if (strtolower($slave_server) != 'none') {
            $post['slave_server'] = $slave_server;
         }

         // Now get the plan ID to post
         $tmp_plid = explode('-', $params['configoption3']);
         $post['plid'] = trim($tmp_plid[0]);
         $virttype = (preg_match('/xen/is', $params['configoption2']) ? 'xen' : (preg_match('/xcp/is', $params['configoption2']) ? 'xcp' : strtolower($params['configoption2'])));

         //\App\Helpers\LogActivity::Save('Params : '.var_export($params, 1));

         // If its Virtuozzo
         if (preg_match('/virtuozzo/is', $virttype)) {

            $tmp_virt = explode(' ', $virttype);

            if ($tmp_virt[1] == 'openvz') {
               $virttype = 'vzo';
            } elseif ($tmp_virt[1] == 'kvm') {
               $virttype = 'vzk';
            }
         }

         if (preg_match('/proxmox/is', $virttype)) {

            $tmp_virt = explode(' ', $virttype);

            if ($tmp_virt[1] == 'openvz') {
               $virttype = 'proxo';
            } elseif ($tmp_virt[1] == 'kvm') {
               $virttype = 'proxk';
            } elseif ($tmp_virt[1] == 'lxc') {
               $virttype = 'proxl';
            }
         }

         if (empty($virtualizor_conf['vps_control']['custom_hname'])) {
            $post['hostname'] = $params['domain'];
         } else {

            // Select the Order ID
            $res = DB::table('tblhosting')->where('id', $params['serviceid'])->get();

            $hosting_details = (array) $res[0];

            $post['hostname'] = str_replace('{ID}', $hosting_details['orderid'], $virtualizor_conf['vps_control']['custom_hname']);
            if (preg_match('/(\{RAND(\d{1,3})\})/is', $post['hostname'], $matches)) {
               $post['hostname'] = str_replace($matches[1], (new Functions)->generateRandStr($matches[2]), $post['hostname']);
            }

            // Change the Hostname to the email
            DB::table('tblhosting')->where('id', $params['serviceid'])->update(
               array('domain' => $post['hostname'])
            );
         }

         $post['rootpass'] = $params['password'];

         // Pass the user details
         $post['user_email'] = $params["clientsdetails"]['email'];
         $post['user_pass'] = $params["password"];

         $post['fname'] = $params["clientsdetails"]['firstname'];
         $post['lname'] = $params["clientsdetails"]['lastname'];

         if ($loglevel > 0) \App\Helpers\LogActivity::Save('params : ' . var_export($params, 1));

         // Set the OS
         // Get the OS from the fields set
         $OS = isset($params['configoptions'][(new Functions)->v_fn('OS')]) ? strtolower(trim($params['configoptions'][(new Functions)->v_fn('OS')])) : "";
         if (empty($OS)) {
            $OS = isset($customfields['OS']) ? strtolower(trim($customfields['OS'])) : "none";
         }

         if (!empty($params['configoptions']['webuzo_os'])) {

            $post['webuzo_spasswd'] = $params['password'];
            $post['webuzo_pd'] = $domain;
            $post['webuzo_stack'] = $params['configoptions']['webuzo_stack'];
            $post['webuzo_os'] = $params['configoptions']['webuzo_os'];
         }

         if ($OS != 'none') {
            $post['os_name'] = $OS;
         }

         if (!empty($customfields['iso']) && strtolower($customfields['iso']) != 'none') {
            $post['iso'] = $customfields['iso'];
         }

         if (!empty($params['configoptions'][(new Functions)->v_fn('ips')])) {
            $post['num_ips'] = $params['configoptions'][(new Functions)->v_fn('ips')];
         }

         if (!empty($params['configoptions'][(new Functions)->v_fn('ips_int')])) {
            $post['num_ips_int'] = $params['configoptions'][(new Functions)->v_fn('ips_int')];
         }

         if (!empty($params['configoptions'][(new Functions)->v_fn('ips6')])) {
            $post['num_ips6'] = $params['configoptions'][(new Functions)->v_fn('ips6')];
         }

         if (!empty($params['configoptions'][(new Functions)->v_fn('ips6_subnet')])) {
            $post['num_ips6_subnet'] = $params['configoptions'][(new Functions)->v_fn('ips6_subnet')];
         }

         if (!empty($params['configoptions']['ippoolid'])) {
            $post['ippoolid'] = $params['configoptions']['ippoolid'];
         }

         if (!empty($params['configoptions'][(new Functions)->v_fn('space')])) {
            $post['space'] = $params['configoptions'][(new Functions)->v_fn('space')];
         }

         if (!empty($params['configoptions'][(new Functions)->v_fn('ram')])) {
            $post['ram'] = $params['configoptions'][(new Functions)->v_fn('ram')];
            if (!empty($virtualizor_conf['ram_in_gb'])) {
               $post['ram'] = $post['ram'] * 1024;
            }
         }

         if (!empty($params['configoptions'][(new Functions)->v_fn('swapram')])) {
            $post['swapram'] = $params['configoptions'][(new Functions)->v_fn('swapram')];
         }

         if (!empty($params['configoptions'][(new Functions)->v_fn('bandwidth')])) {
            $post['bandwidth'] = $params['configoptions'][(new Functions)->v_fn('bandwidth')];
         }

         if (!empty($params['configoptions'][(new Functions)->v_fn('cores')])) {
            $post['cores'] = $params['configoptions'][(new Functions)->v_fn('cores')];
         }

         if (!empty($params['configoptions'][(new Functions)->v_fn('network_speed')])) {
            $post['network_speed'] = $params['configoptions'][(new Functions)->v_fn('network_speed')];
         }

         if (!empty($params['configoptions'][(new Functions)->v_fn('OS')])) {
            $post['OS'] = $params['configoptions'][(new Functions)->v_fn('OS')];
         }

         if (!empty($params['configoptions'][(new Functions)->v_fn('ctrlpanel')])) {
            $post['control_panel'] = $params['configoptions'][(new Functions)->v_fn('ctrlpanel')];
         }

         if (isset($params['configoptions'][(new Functions)->v_fn('server_group')])) {
            $post['server_group'] = $params['configoptions'][(new Functions)->v_fn('server_group')];
            $post['slave_server'] = '';
         }

         if (!empty($params['configoptions'][(new Functions)->v_fn('recipe')])) {
            $post['recipe'] = $params['configoptions'][(new Functions)->v_fn('recipe')];
         }

         if (!empty($params['configoptions'][(new Functions)->v_fn('total_iops_sec')])) {
            $post['total_iops_sec'] = $params['configoptions'][(new Functions)->v_fn('total_iops_sec')];
         }

         if (!empty($params['configoptions'][(new Functions)->v_fn('read_bytes_sec')])) {
            $post['read_bytes_sec'] = $params['configoptions'][(new Functions)->v_fn('read_bytes_sec')];
         }

         if (!empty($params['configoptions'][(new Functions)->v_fn('write_bytes_sec')])) {
            $post['write_bytes_sec'] = $params['configoptions'][(new Functions)->v_fn('write_bytes_sec')];
         }

         if (!empty($params['configoptions'][(new Functions)->v_fn('cpu_percent')])) {
            $post['cpu_percent'] = $params['configoptions'][(new Functions)->v_fn('cpu_percent')];
         }

         // Are there any configurable options
         if (!empty($params['configoptions'])) {
            foreach ($params['configoptions'] as $k => $v) {
               if (!isset($post[$k])) {
                  $post[$k] = $v;
               }
            }
         }

         // Any custom code ?
         // if(file_exists(dirname(__FILE__).'/custom.php')){
         //     include_once(dirname(__FILE__).'/custom.php');

         //     if(!empty($custom_error)){
         //         return $custom_error;
         //     }

         // }

         // Check if there is a hostname custom field
         if (!empty($params['customfields']['hostname'])) {
            $post['hostname'] = $params['customfields']['hostname'];
         }

         // No emails
         if (!empty($customfields['noemail'])) {
            $post['noemail'] = 1;
         }

         $post['node_select'] = 1;
         $post['addvps'] = 1;

         if ($loglevel > 0) \App\Helpers\LogActivity::Save('POST : ' . var_export($post, 1));

         $ctrlpanel = (empty($params['configoptions'][(new Functions)->v_fn('ctrlpanel')]) ? -1 : strtolower(trim($params['configoptions'][(new Functions)->v_fn('ctrlpanel')])));

         $ret = Virtualizor_Curl::make_api_call($params["serverip"], $params["serverpassword"], 'index.php?act=addvs&virt=' . $virttype, array(), $post, array());

         //\App\Helpers\LogActivity::Save('data to be posted: '.var_export($post, 1));

         if (empty($ret)) {
            return 'Could not load the slave server data';
         }

         if (!empty($ret['error'])) {
            return implode('<br>*', array_values($ret['error']));
         }

         //\App\Helpers\LogActivity::Save('New module Return data after post : '.var_export($ret['newvs'], 1));

         // Fill the variables as per the OLD module as it will be inserted to WHMCS. Like ips, ips6, etc..
         if (!empty($ret['newvs']['ips'])) {
            $_ips = $ret['newvs']['ips'];
         }

         if (!empty($ret['newvs']['ipv6'])) {
            $_ips6 = $ret['newvs']['ipv6'];
         }

         if (!empty($ret['newvs']['ipv6_subnet'])) {
            $_ips6_subnet = $ret['newvs']['ipv6_subnet'];
         }



         // Setup cPanel licenses if cPanel configurable option is set
         if ($ctrlpanel != -1 && $ctrlpanel != 'none') {

            if ($ctrlpanel == 'cpanel' && !empty($virtualizor_conf['cp']['buy_cpanel_login']) && !empty($virtualizor_conf['cp']['buy_cpanel_apikey'])) {
               \App\Helpers\LogActivity::Save("CPANEL : cPanel issued for ip $_ips[0] of ordertype $cpanel");

               $url = 'https://www.buycpanel.com/api/order.php?';
               $login = 'login=' . $virtualizor_conf['cp']['buy_cpanel_login'] . '&';
               $key = 'key=' . $virtualizor_conf['cp']['buy_cpanel_apikey'] . '&';
               $domain = 'domain=' . $params['domain'] . '&';
               $serverip = 'serverip=' . $_ips[0] . '&';
               $ordertype = 'ordertype=10';

               $url .= $login . $key . $domain . $serverip . $ordertype;

               $ret_ctrlpanel = Virtualizor_Curl::curl_call($url, 0, 5);

               $ret_ctrlpanel = json_decode($ret_ctrlpanel);

               if ($ret_ctrlpanel->success == 0) {
                  return 'Errors : cPanel Licensing : ' . $ret_ctrlpanel->faultstring;
               }
            }
         }

         // Old Module compatibility
      } else {

         # Additional variables if the product/service is linked to a server
         $server = $params["server"]; # True if linked to a server
         $serverid = $params["serverid"];
         $serverip = $params["serverip"];
         $serverusername = $params["serverusername"];
         $serverpassword = $params["serverpassword"];
         $serveraccesshash = $params["serveraccesshash"];
         $serversecure = $params["serversecure"]; # If set, SSL Mode is enabled in the server config

         $virttype = (preg_match('/xen/is', $params['configoption1']) ? 'xen' : (preg_match('/xcp/is', $params['configoption1']) ? 'xcp' : strtolower($params['configoption1'])));

         // If its Virtuozzo
         if (preg_match('/virtuozzo/is', $virttype)) {

            $tmp_virt = explode(' ', $virttype);

            if ($tmp_virt[1] == 'openvz') {
               $virttype = 'vzo';
            } elseif ($tmp_virt[1] == 'kvm') {
               $virttype = 'vzk';
            }
         }

         // If its Proxmox
         if (preg_match('/proxmox/is', $virttype)) {

            $tmp_virt = explode(' ', $virttype);

            if ($tmp_virt[1] == 'openvz') {
               $virttype = 'proxo';
            } elseif ($tmp_virt[1] == 'kvm') {
               $virttype = 'proxk';
            } elseif ($tmp_virt[1] == 'lxc') {
               $virttype = 'proxl';
            }
         }

         $hvm = (preg_match('/hvm/is', $params['configoption1']) ? 1 : 0);
         $numips = (empty($params['configoptions'][(new Functions)->v_fn('ips')]) || $params['configoptions'][(new Functions)->v_fn('ips')] == 0 ? $params['configoption13'] : $params['configoptions'][(new Functions)->v_fn('ips')]);
         $numips_int = (empty($params['configoptions'][(new Functions)->v_fn('ips_int')]) || $params['configoptions'][(new Functions)->v_fn('ips_int')] == 0 ? $params['configoption19'] : $params['configoptions'][(new Functions)->v_fn('ips_int')]);
         $numips6 = (empty($params['configoptions'][(new Functions)->v_fn('ips6')]) || $params['configoptions'][(new Functions)->v_fn('ips6')] == 0 ? $params['configoption17'] : $params['configoptions'][(new Functions)->v_fn('ips6')]);
         $numips6_subnet = (empty($params['configoptions'][(new Functions)->v_fn('ips6_subnet')]) || $params['configoptions'][(new Functions)->v_fn('ips6_subnet')] == 0 ? $params['configoption18'] : $params['configoptions'][(new Functions)->v_fn('ips6_subnet')]);
         $ctrlpanel = (empty($params['configoptions'][(new Functions)->v_fn('ctrlpanel')]) ? -1 : strtolower(trim($params['configoptions'][(new Functions)->v_fn('ctrlpanel')])));

         // Fixes for SolusVM imported ConfigOptions
         if (empty($numips) && !empty($params['configoptions']['Extra IP Address'])) {
            $numips = $params['configoptions']['Extra IP Address'];
         }

         if ($loglevel > 0) \App\Helpers\LogActivity::Save('VIRT : ' . $virttype . ' - ' . $hvm);
         if ($loglevel > 0) \App\Helpers\LogActivity::Save(var_export($params, 1));

         if (!empty($params['configoptions']['ippoolid'])) {
            $post['ippoolid'] = $params['configoptions']['ippoolid'];
         }

         // Get the Data
         $data = Virtualizor_Curl::make_api_call($params["serverip"], $params["serverpassword"], 'index.php?act=addvs&virt=' . $virttype, array(), $post);

         if (empty($data)) {
            return 'Could not load the server data.' . Virtualizor_Curl::error($params["serverip"]);
         }

         $cookies = array();

         $slave_server = (empty($params['configoptions'][(new Functions)->v_fn('slave_server')]) ? $params['configoption15'] : $params['configoptions'][(new Functions)->v_fn('slave_server')]);
         $server_group = (empty($params['configoptions'][(new Functions)->v_fn('server_group')]) ? $params['configoption16'] : $params['configoptions'][(new Functions)->v_fn('server_group')]);

         // Overcommit RAM
         foreach ($data['servers'] as $k => $v) {
            $data['servers'][$k]['_ram'] = !empty($v['overcommit']) ? ($v['overcommit'] - $v['alloc_ram']) : $v['ram'];
         }

         // Post Variables
         $post = array();
         $post['space'] = (empty($params['configoptions'][(new Functions)->v_fn('space')]) || $params['configoptions'][(new Functions)->v_fn('space')] == 0 ? $params['configoption2'] : $params['configoptions'][(new Functions)->v_fn('space')]);
         $post['ram'] = (empty($params['configoptions'][(new Functions)->v_fn('ram')]) || $params['configoptions'][(new Functions)->v_fn('ram')] == 0 ? $params['configoption4'] : $params['configoptions'][(new Functions)->v_fn('ram')]);
         if (!empty($virtualizor_conf['ram_in_gb'])) {
            $post['ram'] = $post['ram'] * 1024;
         }
         if ($loglevel > 0) \App\Helpers\LogActivity::Save('GET DATA : ' . var_export($data, 1));
         // Is there a Slave server ?
         if (!empty($slave_server) && $slave_server != 'localhost') {

            // Do we have to Auto Select
            if ($slave_server == 'auto') {

               foreach ($data['servers'] as $k => $v) {

                  // Master servers cannot be here
                  if (empty($k)) continue;

                  // Only the Same type of Virtualization is supported
                  if (!in_array($virttype, $v['virts'])) {
                     continue;
                  }

                  // Xen HVM additional check
                  if (!empty($hvm) && empty($v['hvm'])) {
                     continue;
                  }

                  // Do you have enough space
                  if ($v['space'] < $post['space']) {
                     continue;
                  }

                  // Is the server locked ?
                  if (!empty($v['locked'])) {
                     continue;
                  }

                  $ser_setting = unserialize($v['settings']);

                  // Reached the limit of vps creation ?
                  if (!empty($ser_setting['vpslimit']) && $v['numvps'] >= $ser_setting['vpslimit']) {
                     continue;
                  }

                  // Do you have enough RAM
                  if ($v['_ram'] < $post['ram']) {
                     continue;
                  }

                  if (isset($customfields['node_ram_select']) || !empty($virtualizor_conf['node_ram_select'])) {
                     $tmpsort[$k] = -$v['_ram'];
                  } else {
                     $tmpsort[$k] = $v['numvps'];
                  }
               }

               // Did we get a list of Slave Servers
               if (empty($tmpsort)) {
                  return 'No server present in the Cluster which is of the Virtualization Type : ' . $params['configoption1'];
               }

               asort($tmpsort);

               $newserid = key($tmpsort);
               //return 'Tests'.$newserid.var_export($tmpsort, 1);

            } else {

               foreach ($data['servers'] as $k => $v) {
                  if (trim(strtolower($v['server_name'])) == trim(strtolower($slave_server))) {
                     $newserid = $k;
                  }
               }
            }

            // Is there a valid slave server ?
            if (empty($newserid)) {
               return 'There is no slave server - ' . $slave_server . '. Please correct the <b>Product / Service</b> with the right slave server name.';
            }

            if ($loglevel > 1) \App\Helpers\LogActivity::Save('Slave Server : ' . $newserid);

            // Is there a Server Group ?
         } elseif (!empty($server_group)) {

            foreach ($data['servergroups'] as $k => $v) {

               // Match the Server Group
               if (trim(strtolower($v['sg_name'])) == trim(strtolower($server_group))) {
                  $sgid = $k;
               }
            }

            // OH SHIT ! We didnt find anything
            if (!isset($sgid)) {
               return 'Could not find the server group - ' . $server_group . '. Please correct the <b>Product / Service</b> with the right slave server name.';
            }

            // Make an array of available servers in this group
            foreach ($data['servers'] as $k => $v) {

               // Do you belong to this group
               if ($v['sgid'] != $sgid) {
                  continue;
               }

               // Is the server locked ?
               if (!empty($v['locked'])) {
                  continue;
               }

               $ser_setting = unserialize($v['settings']);

               // Reached the limit of vps creation ?
               if (!empty($ser_setting['vpslimit']) && $v['numvps'] >= $ser_setting['vpslimit']) {
                  continue;
               }

               // Only the Same type of Virtualization is supported
               if (!in_array($virttype, $v['virts'])) {
                  continue;
               }

               // Xen HVM additional check
               if (!empty($hvm) && empty($v['hvm'])) {
                  continue;
               }

               //\App\Helpers\LogActivity::Save('Slave Server Selection Ram : '.$v['_ram'].' '.$v['overcommit'].' '.$v['alloc_ram'].' '.$post['ram'].' Space : '.$v['space'].' '.$post['space']);

               // Do you have enough space
               if ($v['space'] < $post['space']) {
                  continue;
               }

               // Do you have enough RAM
               if ($v['_ram'] < $post['ram']) {
                  continue;
               }

               if (isset($customfields['node_ram_select']) || !empty($virtualizor_conf['node_ram_select'])) {
                  $tmpsort[$k] = -$v['_ram'];
               } else {
                  $tmpsort[$k] = $v['numvps'];
               }
            }

            asort($tmpsort);

            // Is there a valid slave server ?
            if (empty($tmpsort)) {
               return 'No server present in the Server Group which is of the Virtualization Type : ' . $params['configoption1'] . '. Please correct the <b>Product / Service</b> with the right slave server name.';
            }

            $newserid = key($tmpsort);

            if ($loglevel > 1) \App\Helpers\LogActivity::Save('Slave Group Server Chosen : ' . $newserid);
            if ($loglevel > 1) \App\Helpers\LogActivity::Save('Slave Server Details : ' . var_export($data['servers'][$newserid], 1));
         }

         if (!empty($params['configoptions']['ippoolid'])) {
            $post['ippoolid'] = $params['configoptions']['ippoolid'];
         }

         // If a new server ID was found. Even if its 0 (Zero) then there is no need to reload data as the DATA is by default of 0
         if (!empty($newserid)) {

            $cookies[$data['globals']['cookie_name'] . '_server'] = $newserid;


            $data = Virtualizor_Curl::make_api_call($params["serverip"], $params["serverpassword"], 'index.php?act=addvs&virt=' . $virttype, array(), $post, $cookies);

            if (empty($data)) {
               return 'Could not load the slave server data';
            }
         }

         if ($loglevel > 2) \App\Helpers\LogActivity::Save(var_export($data, 1));

         // Search does the user exist
         foreach ($data['users'] as $k => $v) {
            if (strtolower($v['email']) == strtolower($params["clientsdetails"]['email'])) {
               $post['uid'] = $v['uid'];
            }
         }

         // Was the user there ?
         if (empty($post['uid'])) {
            $post['user_email'] = $params["clientsdetails"]['email'];
            $post['user_pass'] = $params["password"];

            // Just add teh fname and lname
            $post['fname'] = $params["clientsdetails"]['firstname'];
            $post['lname'] = $params["clientsdetails"]['lastname'];
         }

         // Get the OS from the fields set
         $OS = strtolower(trim($params['configoptions'][(new Functions)->v_fn('OS')]));
         if (empty($OS)) {
            $OS = strtolower(trim($customfields['OS']));
         }

         // Search the OS ID
         if ($OS != 'none') {

            foreach ($data['oslist'][$virttype] as $k => $v) {
               foreach ($v as $kk => $vv) {

                  // Xen/XCP Stuff!
                  if ($virttype == 'xen' || $virttype == 'xcp') {

                     // Xen/XCP HVM templates
                     if (!empty($hvm) && empty($vv['hvm'])) {
                        continue;

                        // Xen/XCP PV templates
                     } elseif (empty($hvm) && !empty($vv['hvm'])) {
                        continue;
                     }
                  }

                  // Does the String match ?
                  if (strtolower($vv['name']) == $OS) {
                     $post['osid'] = $kk;
                  }
               }
            }
         }

         // Is the OS template there
         if (empty($post['osid']) && $OS != 'none') {
            return 'Could not find the OS Template ' . $OS;
         }

         // Search the ISO
         if (!empty($customfields['iso']) && strtolower($customfields['iso']) != 'none') {

            // ISO restricted in OVZ and XEN-PV
            if (in_array($virttype, array('openvz', 'vzo', 'proxo', 'lxc')) || (($virttype == 'xen' || $virttype == 'xcp') && empty($hvm))) {
               return 'You can not select ISO for OpenVZ, LXC, Virtuozzo OpenVZ, Proxmox OpenVZ, XEN-PV and XCP-PV VPS';
            }

            foreach ($data['isos'] as $k => $v) {

               foreach ($v as $kk => $vv) {

                  //echo $vv['name'].' - '.$params["customfields"]['iso'].'<br>';

                  // Does the String match ?
                  if (strtolower($vv) == strtolower(trim($customfields['iso']))) {
                     $post['iso'] = $vv;
                  }
               }
            }

            // Is the ISO there
            if (empty($post['iso'])) {
               return 'Could not find the ISO ' . $customfields['iso'];
            }
         }

         // If ISO and OS both not selected ?
         if (empty($post['iso']) && empty($post['osid']) && strtolower($customfields['iso']) == 'none' && $OS == 'none') {
            return 'ISO or OS is not selected';
         }

         // No emails
         if (!empty($customfields['noemail'])) {
            $post['noemail'] = 1;
         }

         // Are there any IPv4 to assign ?
         if ($numips > 0) {

            // Assign the IPs
            foreach ($data['ips'] as $k => $v) {
               $i = $numips;
               $_ips[] = $v['ip'];

               if ($i == count($_ips)) {
                  break;
               }
            }

            // Were there enough IPs
            if (empty($_ips) || count($_ips) < $numips) {
               return 'There are insufficient IPs on the server';
            }
         }

         // Are there any Inernal IPs to assign ?
         if ($numips_int > 0) {

            // Assign the IPs
            foreach ($data['ips_int'] as $k => $v) {
               $i = $numips_int;
               $_ips_int[] = $v['ip'];

               if ($i == count($_ips_int)) {
                  break;
               }
            }

            // Were there enough IPs
            if (empty($_ips_int) || count($_ips_int) < $numips_int) {
               return 'There are insufficient Internal IPs on the server';
            }
         }

         // Are there any IPv6 to assign ?
         if ($numips6 > 0) {

            $_ips6 = array();

            // Assign the IPs
            foreach ($data['ips6'] as $k => $v) {

               if ($numips6 == count($_ips6)) {
                  break;
               }

               $_ips6[] = $v['ip'];
            }

            // Were there enough IPs
            if (empty($_ips6) || count($_ips6) < $numips6) {
               return 'There are insufficient IPv6 Addresses on the server';
            }
         }

         // Are there any IPv6 Subnets to assign ?
         if ($numips6_subnet > 0) {

            $_ips6_subnet = array();

            // Assign the IPs
            foreach ($data['ips6_subnet'] as $k => $v) {

               if ($numips6_subnet == count($_ips6_subnet)) {
                  break;
               }

               $_ips6_subnet[] = $v['ip'];
            }

            // Were there enough IPs
            if (empty($_ips6_subnet) || count($_ips6_subnet) < $numips6_subnet) {
               return 'There are insufficient IPv6 Subnets on the server';
            }
         }

         if (empty($virtualizor_conf['vps_control']['custom_hname'])) {
            $post['hostname'] = $params['domain'];
         } else {

            // Select the Order ID
            $res = DB::table('tblhosting')->where('id', $params['serviceid'])->get();

            $hosting_details = (array) $res[0];

            $post['hostname'] = str_replace('{ID}', $hosting_details['orderid'], $virtualizor_conf['vps_control']['custom_hname']);
            if (preg_match('/(\{RAND(\d{1,3})\})/is', $post['hostname'], $matches)) {
               $post['hostname'] = str_replace($matches[1], (new Functions)->generateRandStr($matches[2]), $post['hostname']);
            }

            // Change the Hostname to the email
            DB::table('tblhosting')
               ->where('id', $params['serviceid'])
               ->update(array('domain' => $post['hostname']));
         }

         $post['rootpass'] = $params['password'];
         $post['bandwidth'] = (empty($params['configoptions'][(new Functions)->v_fn('bandwidth')]) || $params['configoptions'][(new Functions)->v_fn('bandwidth')] == 0 ? (empty($params['configoption7']) ? '0' : $params['configoption7']) : $params['configoptions'][(new Functions)->v_fn('bandwidth')]);
         $post['cores'] = (empty($params['configoptions'][(new Functions)->v_fn('cores')]) || $params['configoptions'][(new Functions)->v_fn('cores')] == 0 ? $params['configoption9'] : $params['configoptions'][(new Functions)->v_fn('cores')]);
         $post['network_speed'] = (empty($params['configoptions'][(new Functions)->v_fn('network_speed')]) || $params['configoptions'][(new Functions)->v_fn('network_speed')] == 0 ? $params['configoption14'] : $params['configoptions'][(new Functions)->v_fn('network_speed')]);
         $post['cpu_percent'] = (empty($params['configoptions'][(new Functions)->v_fn('cpu_percent')]) || $params['configoptions'][(new Functions)->v_fn('cpu_percent')] == 0 ? $params['configoption10'] : $params['configoptions'][(new Functions)->v_fn('cpu_percent')]);
         $post['cpu'] = $params['configoption8'];
         $post['addvps'] = 1;
         $post['band_suspend'] = 1;

         // Fixes for SolusVM imported ConfigOptions
         if (empty($post['ram']) && !empty($params['configoptions']['Memory'])) {
            $post['ram'] = (int)$params['configoptions']['Memory'];
            if (!empty($virtualizor_conf['ram_in_gb'])) {
               $post['ram'] = $post['ram'] * 1024;
            }
         }
         if (empty($post['space']) && !empty($params['configoptions']['Disk Space'])) {
            $post['space'] = $params['configoptions']['Disk Space'];
         }
         if (empty($post['cores']) && !empty($params['configoptions']['CPU'])) {
            $post['cores'] = $params['configoptions']['CPU'];
         }

         if (!empty($params['customfields']['hostname'])) {
            $post['hostname'] = $params['customfields']['hostname'];
         }

         if (!empty($params['configoptions']['ippoolid'])) {
            $post['ippoolid'] = $params['configoptions']['ippoolid'];
         }

         // Control Panel
         $control_panel = trim(strtolower($params['configoptions']['control_panel']));
         $post['control_panel'] = ((empty($control_panel) || $control_panel == 'none') ? 0 : $control_panel);

         // Is is OpenVZ
         if ($virttype == 'openvz') {

            $post['inodes'] = $params['configoption3'];
            $post['burst'] = $params['configoption5'];
            $post['priority'] = $params['configoption11'];

            // Is it Xen PV?
         } elseif (($virttype == 'xen' || $virttype == 'xcp') && empty($hvm)) {

            $post['swapram'] = (empty($params['configoptions'][(new Functions)->v_fn('swapram')]) || $params['configoptions'][(new Functions)->v_fn('swapram')] == 0 ? (empty($params['configoption6']) ? '0' : $params['configoption6']) : $params['configoptions'][(new Functions)->v_fn('swapram')]);
            if ($params['configoption12'] == 'yes' || $params['configoption12'] == 'on') {
               $post['vnc'] = 1;
               $post['vncpass'] = (new Functions)->generateRandStr(8);
            }

            // Is it Xen HVM?
         } elseif (($virttype == 'xen' || $virttype == 'xcp') && !empty($hvm)) {

            $post['hvm'] = 1;
            $post['shadow'] = 8;
            $post['swapram'] = (empty($params['configoptions'][(new Functions)->v_fn('swapram')]) || $params['configoptions'][(new Functions)->v_fn('swapram')] == 0 ? (empty($params['configoption6']) ? '0' : $params['configoption6']) : $params['configoptions'][(new Functions)->v_fn('swapram')]);
            if ($params['configoption12'] == 'yes' || $params['configoption12'] == 'on') {
               $post['vnc'] = 1;
               $post['vncpass'] = (new Functions)->generateRandStr(8);
            }

            // Is it KVM ?
         } elseif ($virttype == 'kvm') {

            $post['swapram'] = (empty($params['configoptions'][(new Functions)->v_fn('swapram')]) || $params['configoptions'][(new Functions)->v_fn('swapram')] == 0 ? (empty($params['configoption6']) ? '0' : $params['configoption6']) : $params['configoptions'][(new Functions)->v_fn('swapram')]);
            if ($params['configoption12'] == 'yes' || $params['configoption12'] == 'on') {
               $post['vnc'] = 1;
               $post['vncpass'] = (new Functions)->generateRandStr(8);
            }
         } elseif ($virttype == 'lxc') {
            $post['swapram'] = (empty($params['configoptions'][(new Functions)->v_fn('swapram')]) || $params['configoptions'][(new Functions)->v_fn('swapram')] == 0 ? (empty($params['configoption6']) ? '0' : $params['configoption6']) : $params['configoptions'][(new Functions)->v_fn('swapram')]);
         }

         // Suspend on bandwidth
         //$post['band_suspend'] = 1;

         // Add the IPs
         if (!empty($_ips)) {
            $post['ips'] = $_ips;
         }

         // Add the Internal IPs
         if (!empty($_ips_int)) {
            $post['ips_int'] = $_ips_int;
         }

         // Add the IPv6
         if (!empty($_ips6)) {
            $post['ipv6'] = $_ips6;
         }

         // Add the IPv6 Subnet
         if (!empty($_ips6_subnet)) {
            $post['ipv6_subnet'] = $_ips6_subnet;
         }

         if ($loglevel > 0) \App\Helpers\LogActivity::Save('configoption : ' . var_export($params['configoptions'], 1));

         // Are there any configurable options
         if (!empty($params['configoptions'])) {
            foreach ($params['configoptions'] as $k => $v) {
               if (!isset($post[$k])) {
                  $post[$k] = $v;
               }
            }
         }

         // Any custom code ?
         // if(file_exists(dirname(__FILE__).'/custom.php')){
         //     include_once(dirname(__FILE__).'/custom.php');

         //     if(!empty($custom_error)){
         //         return $custom_error;
         //     }

         // }

         if ($loglevel > 0) \App\Helpers\LogActivity::Save('POST : ' . var_export($post, 1));

         //echo "<pre>";print_r($cookies);echo "</pre>";
         //echo "<pre>";print_r($post);echo "</pre>";
         // return 'TEST'.var_export($params, 1);

         // Setup cPanel licenses if cPanel configurable option is set
         if ($ctrlpanel != -1 && $ctrlpanel != 'none') {

            if ($ctrlpanel == 'cpanel' && !empty($virtualizor_conf['cp']['buy_cpanel_login']) && !empty($virtualizor_conf['cp']['buy_cpanel_apikey'])) {
               \App\Helpers\LogActivity::Save("CPANEL : cPanel issued for ip $_ips[0] of ordertype $cpanel");

               $url = 'https://www.buycpanel.com/api/order.php?';
               $login = 'login=' . $virtualizor_conf['cp']['buy_cpanel_login'] . '&';
               $key = 'key=' . $virtualizor_conf['cp']['buy_cpanel_apikey'] . '&';
               $domain = 'domain=' . $params['domain'] . '&';
               $serverip = 'serverip=' . $_ips[0] . '&';
               $ordertype = 'ordertype=10';

               $url .= $login . $key . $domain . $serverip . $ordertype;

               $ret = file_get_contents($url);

               $ret = json_decode($ret);

               if ($ret->success == 0) {
                  return 'Errors : cPanel Licensing : ' . $ret->faultstring;
               }
            }
         }

         $ret = Virtualizor_Curl::make_api_call($params["serverip"], $params["serverpassword"], 'index.php?act=addvs&virt=' . $virttype, array(), $post, $cookies);

         if ($loglevel > 0) \App\Helpers\LogActivity::Save('RETURN POST AFTER CREATION: ' . var_export($ret['newvs'], 1));
      } // End of old module

      // Was the VPS Inserted
      if (!empty($ret['newvs']['vpsid'])) {

         if ($loglevel > 0) \App\Helpers\LogActivity::Save('Virtualizor DONE ? : ' . var_export($ret['done'], 1));

         // vpsid of virtualizor
         $query = DB::table('tblcustomfields')
            ->where('relid', $pid)
            ->where('fieldname', 'vpsid')
            ->get();
         $res = (array) $query[0];

         // We will check if there is an entry if not we will insert it.
         $query = DB::table('tblcustomfieldsvalues')
            ->select('relid')
            ->where('relid', $serviceid)
            ->where('fieldid', $res['id'])
            ->get();
         $sel_res = $query->count() > 0 ? (array) $query[0] : ['relid' => ''];

         if ($loglevel > 0) \App\Helpers\LogActivity::Save('Did we found anything : ' . var_export($sel_res, 1));

         // We will insert it if not found anything
         if (empty($sel_res['relid'])) {
            DB::table('tblcustomfieldsvalues')
               ->insert(array(
                  'value' => $ret['newvs']['vpsid'],
                  'relid' => $serviceid,
                  'fieldid' => $res['id']
               ));
            //if($loglevel > 0) \App\Helpers\LogActivity::Save('After Updating tblcustomfieldsvalues : '.var_export(mysql_error($whmcsmysql), 1));

         } else {
            DB::table('tblcustomfieldsvalues')
               ->where('relid', $serviceid)
               ->where('fieldid', $res['id'])
               ->update(
                  array('value' => $ret['newvs']['vpsid'])
               );

            if ($loglevel > 0) \App\Helpers\LogActivity::Save("UPDATE `tblcustomfieldsvalues` SET `value` = '" . $ret['newvs']['vpsid'] . "' WHERE `relid` = '$serviceid' AND `fieldid` = '" . $res['id'] . "'");
         }


         $uuid = $ret['newvs']['uuid'];

         // add vps_uuid field as well
         Virtualizor_Curl::create_uuid_field($pid, $serviceid, $uuid);

         // Change the Username to the email
         DB::table('tblhosting')
            ->where('id', $serviceid)
            ->update(
               array('username' => $params['clientsdetails']['email'])
            );

         // The Dedicated IP
         DB::table('tblhosting')
            ->where('id', $serviceid)
            ->update(
               array('dedicatedip' => (!empty($_ips[0]) ? $_ips[0] : $_ips6[0]))
            );

         if ($ctrlpanel == 'cpanel' && !empty($virtualizor_conf['cp']['cpanel_manage2_username']) && !empty($virtualizor_conf['cp']['cpanel_manage2_password'])) {

            // TODO: virt_add_cpanel_license($params);

         }


         $tmp_ips = empty($_ips) ? array() : $_ips;

         if (!empty($_ips6_subnet)) {
            foreach ($_ips6_subnet as $k => $v) {
               $tmp_ips[] = $v;
            }
         }

         if (!empty($_ips6)) {
            foreach ($_ips6 as $k => $v) {
               $tmp_ips[] = $v;
            }
         }

         // Extra IPs
         if (count($tmp_ips) > 1) {
            unset($tmp_ips[0]);
            DB::table('tblhosting')
               ->where('id', $serviceid)
               ->update(
                  array('assignedips' => implode("\n", $tmp_ips))
               );
         }

         // Did it start ?
         if (!empty($ret['done'])) {
            return 'success';
         } else {
            return 'Errors : ' . implode('<br>', $ret['error']);
         }
      } else {
         return 'Errors : ' . implode('<br>', $ret['error']);
      }
   }

   public function ChangePassword($params)
   {

      $loglevel = (int) @$_REQUEST['loglevel'];

      # Code to perform action goes here...
      $id = $params['customfields']['vpsid'];

      if (!empty($params['customfields']['vps_uuid'])) {
         $uuid = $params['customfields']['vps_uuid'];
      }

      $data = Virtualizor_Curl::make_api_call($params["serverip"], $params["serverpassword"], 'index.php?act=editvs&vpsid=' . $id . '&vps_uuid=' . $uuid);

      if (empty($data)) {
         return 'Could not load the server data.' . Virtualizor_Curl::error($params["serverip"]);
      }

      $post_vps = $data['vps'];

      // Are there any configurable options
      if (!empty($params['configoptions'])) {

         foreach ($params['configoptions'] as $k => $v) {
            if (!isset($post_vps[$k])) {
               $post_vps[$k] = $v;
            }
         }
      }

      $post_vps['editvps'] = 1;

      $post_vps['rootpass'] = $params['password'];

      //logActivity('Post Array : '.var_export($params, 1));

      if ($loglevel > 0) \App\Helpers\LogActivity::Save('Post Array : ' . var_export($post_vps, 1));

      $ret = Virtualizor_Curl::make_api_call($params["serverip"], $params["serverpassword"], 'index.php?act=editvs&vpsid=' . $id . '&vps_uuid=' . $uuid, array(), $post_vps);

      unset($ret['scripts']);
      unset($ret['iscripts']);
      unset($ret['ostemplates']);
      unset($ret['isos']);

      if ($loglevel > 0) \App\Helpers\LogActivity::Save('Post Result : ' . var_export($ret, 1));

      if (empty($ret)) {
         return 'Could not load the server data after processing.' . Virtualizor_Curl::error($params["serverip"]);
      }

      if (!empty($ret['done'])) {

         $result = "success";
      } else {

         if (!empty($ret['error'])) {
            return 'Errors : ' . implode('<br>', $ret['error']);
         }

         $result = 'Unknown error occured. Please check logs';
      }

      return $result;
   }

   public function SuspendAccount($params)
   {

      global $virtualizor_conf;

      $id = $params['customfields']['vpsid'];

      if (!empty($params['customfields']['vps_uuid'])) {
         $uuid = $params['customfields']['vps_uuid'];
      }

      $data = Virtualizor_Curl::make_api_call($params["serverip"], $params["serverpassword"], 'index.php?act=vs&suspend=' . $id . '&suspend_uuid=' . $uuid);

      if (empty($data)) {
         return 'Could not load the server data.' . Virtualizor_Curl::error($params["serverip"]);
      }

      $ctrlpanel = (empty($params['configoptions'][(new Functions)->v_fn('ctrlpanel')]) ? -1 : strtolower(trim($params['configoptions'][(new Functions)->v_fn('ctrlpanel')])));

      if ($ctrlpanel == 'cpanel' && !empty($virtualizor_conf['cp']['cpanel_manage2_username']) && !empty($virtualizor_conf['cp']['cpanel_manage2_password'])) {

         // TODO: virt_remove_cpanel_license($params);

      }

      // echo "<pre>";print_r($params);echo "</pre>";
      // echo "<pre>";print_r($data);echo "</pre>";

      if ($data['done']) {
         $result = "success";
      } else {
         $result = "There was some error suspending the VPS";
      }
      return $result;
   }

   public function UnsuspendAccount($params)
   {

      global $virtualizor_conf;

      $id = $params['customfields']['vpsid'];

      if (!empty($params['customfields']['vps_uuid'])) {
         $uuid = $params['customfields']['vps_uuid'];
      }

      $data = Virtualizor_Curl::make_api_call($params["serverip"], $params["serverpassword"], 'index.php?act=vs&unsuspend=' . $id . '&unsuspend_uuid=' . $uuid);

      if (empty($data)) {
         return 'Could not load the server data.' . Virtualizor_Curl::error($params["serverip"]);
      }

      // echo "<pre>";print_r($params);echo "</pre>";
      // echo "<pre>";print_r($data);echo "</pre>";

      $ctrlpanel = (empty($params['configoptions'][(new Functions)->v_fn('ctrlpanel')]) ? -1 : strtolower(trim($params['configoptions'][(new Functions)->v_fn('ctrlpanel')])));

      if ($ctrlpanel == 'cpanel' && !empty($virtualizor_conf['cp']['cpanel_manage2_username']) && !empty($virtualizor_conf['cp']['cpanel_manage2_password'])) {

         // TODO: virt_add_cpanel_license($params);

      }

      if ($data['done']) {
         $result = "success";
      } else {
         $result = "There was some error unsuspending the VPS";
      }
      return $result;
   }

   public function ChangePackage($params)
   {

      global $virtualizor_conf;

      $loglevel = (int) @$_REQUEST['loglevel'];
      $serviceid = $params["serviceid"]; # Unique ID of the product/service in the WHMCS Database

      if (!empty($virtualizor_conf['loglevel'])) {
         $loglevel = $virtualizor_conf['loglevel'];
      }

      $id = $params['customfields']['vpsid'];

      if (!empty($params['customfields']['vps_uuid'])) {
         $uuid = $params['customfields']['vps_uuid'];
      }

      // Get the Data
      $data = Virtualizor_Curl::make_api_call($params["serverip"], $params["serverpassword"], 'index.php?act=editvs&vpsid=' . $id . '&vps_uuid=' . $uuid);

      if (empty($data)) {
         return 'Could not load the server data.' . Virtualizor_Curl::error($params["serverip"]);
      }

      $post_vps = $data['vps'];

      if ($loglevel > 0) \App\Helpers\LogActivity::Save('Change Package Params : ' . var_export($params, 1));
      if ($loglevel > 0) \App\Helpers\LogActivity::Save('Orig VPS : ' . var_export($post_vps, 1));

      // Are you using New module ?
      if (!in_array($params['configoption1'], array('OpenVZ', 'Xen PV', 'Xen HVM', 'KVM', 'XCP HVM', 'XCP PV', 'LXC', 'Virtuozzo OpenVZ', 'Virtuozzo KVM', 'Proxmox KVM', 'Proxmox OpenVZ', 'Proxmox LXC'))) {
         $post_vps = array();

         // Now get the plan ID to post
         $tmp_plid = explode('-', $params['configoption3']);
         $post_vps['plid'] = trim($tmp_plid[0]);
         $virttype = $data['vps']['virt'];
         $post_vps['user_email'] = $params["clientsdetails"]['email'];

         //\App\Helpers\LogActivity::Save('Params : '.var_export($params, 1));

         if ($loglevel > 0) \App\Helpers\LogActivity::Save('params : ' . var_export($params, 1));

         // Fixes for SolusVM imported ConfigOptions
         if (empty($post_vps['ram']) && !empty($params['configoptions']['Memory'])) {
            $post_vps['ram'] = $params['configoptions']['Memory'];
            if (!empty($virtualizor_conf['ram_in_gb'])) {
               $post_vps['ram'] = $post_vps['ram'] * 1024;
            }
         }
         if (empty($post_vps['space']) && !empty($params['configoptions']['Disk Space'])) {
            $post_vps['space'] = $params['configoptions']['Disk Space'];
         }
         if (empty($post_vps['cores']) && !empty($params['configoptions']['CPU'])) {
            $post_vps['cores'] = $params['configoptions']['CPU'];
         }

         if (!empty($params['configoptions'][(new Functions)->v_fn('ips')])) {
            $post_vps['num_ips'] = $params['configoptions'][(new Functions)->v_fn('ips')];
         }

         if (!empty($params['configoptions'][(new Functions)->v_fn('ips_int')])) {
            $post_vps['num_ips_int'] = $params['configoptions'][(new Functions)->v_fn('ips_int')];
         }

         if (!empty($params['configoptions'][(new Functions)->v_fn('ips6')])) {
            $post_vps['num_ips6'] = $params['configoptions'][(new Functions)->v_fn('ips6')];
         }

         if (!empty($params['configoptions'][(new Functions)->v_fn('ips6_subnet')])) {
            $post_vps['num_ips6_subnet'] = $params['configoptions'][(new Functions)->v_fn('ips6_subnet')];
         }

         if (!empty($params['configoptions'][(new Functions)->v_fn('space')])) {
            $post_vps['space'] = $params['configoptions'][(new Functions)->v_fn('space')];
         }

         if (!empty($params['configoptions'][(new Functions)->v_fn('ram')])) {
            $post_vps['ram'] = $params['configoptions'][(new Functions)->v_fn('ram')];
            if (!empty($virtualizor_conf['ram_in_gb'])) {
               $post_vps['ram'] = $post_vps['ram'] * 1024;
            }
         }

         if (!empty($params['configoptions'][(new Functions)->v_fn('swapram')])) {
            $post['swapram'] = $params['configoptions'][(new Functions)->v_fn('swapram')];
         }

         if (!empty($params['configoptions'][(new Functions)->v_fn('bandwidth')])) {
            $post_vps['bandwidth'] = $params['configoptions'][(new Functions)->v_fn('bandwidth')];
         }

         if (!empty($params['configoptions'][(new Functions)->v_fn('cores')])) {
            $post_vps['cores'] = $params['configoptions'][(new Functions)->v_fn('cores')];
         }

         if (!empty($params['configoptions'][(new Functions)->v_fn('network_speed')])) {
            $post_vps['network_speed'] = $params['configoptions'][(new Functions)->v_fn('network_speed')];
         }

         if (!empty($params['configoptions'][(new Functions)->v_fn('cpu_percent')])) {
            $post_vps['cpu_percent'] = $params['configoptions'][(new Functions)->v_fn('cpu_percent')];
         }

         if (!empty($params['configoptions'][(new Functions)->v_fn('topology_sockets')])) {
            $post_vps['topology_sockets'] = $params['configoptions'][(new Functions)->v_fn('topology_sockets')];
         }

         if (!empty($params['configoptions'][(new Functions)->v_fn('topology_cores')])) {
            $post_vps['topology_cores'] = $params['configoptions'][(new Functions)->v_fn('topology_cores')];
         }

         if (!empty($params['configoptions'][(new Functions)->v_fn('topology_threads')])) {
            $post_vps['topology_threads'] = $params['configoptions'][(new Functions)->v_fn('topology_threads')];
         }

         // Are there any configurable options
         if (!empty($params['configoptions'])) {
            foreach ($params['configoptions'] as $k => $v) {
               if (!isset($post_vps[$k])) {
                  $post_vps[$k] = $v;
               }
            }
         }

         $post_vps['hostname'] = $data['vps']['hostname'];

         $post_vps['editvps'] = 1;

         if ($loglevel > 0) \App\Helpers\LogActivity::Save('Post Array : ' . var_export($post_vps, 1));

         $ret = Virtualizor_Curl::make_api_call($params["serverip"], $params["serverpassword"], 'index.php?act=editvs&vpsid=' . $id . '&vps_uuid=' . $uuid, array(), $post_vps);

         //if($loglevel > 0) \App\Helpers\LogActivity::Save('Return after Edit: '.var_export($ret, 1));

         // Fill the variables as per the OLD module as it will be inserted to WHMCS. Like ips, ips6, etc..
         if (!empty($ret['vps']['ips'])) {
            $post_vps['ips'] = $ret['vps']['ips'];
         }

         if (!empty($ret['vps']['ips6'])) {
            $post_vps['ipv6'] = $ret['vps']['ips6'];
         }

         if (!empty($ret['vps']['ips6_subnet'])) {
            $post_vps['ipv6_subnet'] = $ret['vps']['ips6_subnet'];
         }

         if (!empty($ret['vps']['ips_int'])) {
            $post_vps['ips_int'] = $ret['vps']['ips_int'];
         }

         // This is old method
      } else {

         // POST Variables
         $post_vps['space'] = (empty($params['configoptions'][(new Functions)->v_fn('space')]) || $params['configoptions'][(new Functions)->v_fn('space')] == 0 ? $params['configoption2'] : $params['configoptions'][(new Functions)->v_fn('space')]);
         $post_vps['ram'] = (empty($params['configoptions'][(new Functions)->v_fn('ram')]) || $params['configoptions'][(new Functions)->v_fn('ram')] == 0 ? $params['configoption4'] : $params['configoptions'][(new Functions)->v_fn('ram')]);
         if (!empty($virtualizor_conf['ram_in_gb'])) {
            $post_vps['ram'] = $post_vps['ram'] * 1024;
         }
         $post_vps['bandwidth'] = (empty($params['configoptions'][(new Functions)->v_fn('bandwidth')]) || $params['configoptions'][(new Functions)->v_fn('bandwidth')] == 0 ? (empty($params['configoption7']) ? '0' : $params['configoption7']) : $params['configoptions'][(new Functions)->v_fn('bandwidth')]);
         $post_vps['cores'] = (empty($params['configoptions'][(new Functions)->v_fn('cores')]) || $params['configoptions'][(new Functions)->v_fn('cores')] == 0 ? $params['configoption9'] : $params['configoptions'][(new Functions)->v_fn('cores')]);
         $post_vps['network_speed'] = (empty($params['configoptions'][(new Functions)->v_fn('network_speed')]) || $params['configoptions'][(new Functions)->v_fn('network_speed')] == 0 ? $params['configoption14'] : $params['configoptions'][(new Functions)->v_fn('network_speed')]);
         $post_vps['cpu_percent'] = (empty($params['configoptions'][(new Functions)->v_fn('cpu_percent')]) || $params['configoptions'][(new Functions)->v_fn('cpu_percent')] == 0 ? $params['configoption10'] : $params['configoptions'][(new Functions)->v_fn('cpu_percent')]);
         $post_vps['cpu'] = $params['configoption8'];

         $post_vps['inodes'] = $params['configoption3'];
         $post_vps['burst'] = $params['configoption5'];
         $post_vps['priority'] = $params['configoption11'];
         $post_vps['swapram'] = $params['configoption6'];

         // Fixes for SolusVM imported ConfigOptions
         if (empty($post_vps['ram']) && !empty($params['configoptions']['Memory'])) {
            $post_vps['ram'] = $params['configoptions']['Memory'];
            if (!empty($virtualizor_conf['ram_in_gb'])) {
               $post_vps['ram'] = $post_vps['ram'] * 1024;
            }
         }
         if (empty($post_vps['space']) && !empty($params['configoptions']['Disk Space'])) {
            $post_vps['space'] = $params['configoptions']['Disk Space'];
         }
         if (empty($post_vps['cores']) && !empty($params['configoptions']['CPU'])) {
            $post_vps['cores'] = $params['configoptions']['CPU'];
         }

         if ($params['configoption12'] == 'yes' || $params['configoption12'] == 'on') {
            $post_vps['vnc'] = 1;
            if (empty($vps['vnc'])) {
               $post_vps['vncpass'] = (new Functions)->generateRandStr(8);
            }
         }

         $virttype = $post_vps['virt'];

         // IPs are the same always
         $post_vps['ips'] = $post_vps['ips'];

         // Add the IPv6
         if (!empty($post_vps['ips6'])) {
            $post_vps['ipv6'] = $post_vps['ips6'];
         }

         // Add the IPv6 Subnet
         if (!empty($post_vps['ips6_subnet'])) {
            $post_vps['ipv6_subnet'] = $post_vps['ips6_subnet'];
            foreach ($post_vps['ipv6_subnet'] as $k => $v) {
               $tmp = explode('/', $v);
               $post_vps['ipv6_subnet'][$k] = $tmp[0];
            }
         }

         $numips = (empty($params['configoptions'][(new Functions)->v_fn('ips')]) || $params['configoptions'][(new Functions)->v_fn('ips')] == 0 ? $params['configoption13'] : $params['configoptions'][(new Functions)->v_fn('ips')]);
         $numips6 = (empty($params['configoptions'][(new Functions)->v_fn('ips6')]) || $params['configoptions'][(new Functions)->v_fn('ips6')] == 0 ? $params['configoption17'] : $params['configoptions'][(new Functions)->v_fn('ips6')]);
         $numips6_subnet = (empty($params['configoptions'][(new Functions)->v_fn('ips6_subnet')]) || $params['configoptions'][(new Functions)->v_fn('ips6_subnet')] == 0 ? $params['configoption18'] : $params['configoptions'][(new Functions)->v_fn('ips6_subnet')]);

         // Fixes for SolusVM imported ConfigOptions
         if (empty($numips) && !empty($params['configoptions']['Extra IP Address'])) {
            $numips = $params['configoptions']['Extra IP Address'];
         }

         // Remove some IPs
         if ($numips < count($post_vps['ips'])) {

            $i = 0;
            $newips = array();

            foreach ($post_vps['ips'] as  $k => $v) {

               // We have completed
               if ($numips == $i) {
                  break;
               }

               $newips[$k] = $v;
               $i++;
            }

            $post_vps['ips'] = $newips;

            // Add some IPs
         } elseif ($numips > count($post_vps['ips'])) {

            $toadd = $numips - count($post_vps['ips']);

            // Assign the IPs
            foreach ($data['ips'] as $k => $v) {

               if (in_array($v['ip'], $post_vps['ips'])) {
                  continue;
               }

               $post_vps['ips'][$k] = $v['ip'];

               if ($numips == count($post_vps['ips'])) {
                  break;
               }
            }

            // Were there enough IPs
            if (count($post_vps['ips']) < $numips) {
               return 'There are insufficient IPs on the server';
            }
         }

         // Remove some IPv6 Subnets
         if ($numips6_subnet < count($post_vps['ipv6_subnet'])) {

            $i = 0;
            $newips = array();

            foreach ($post_vps['ipv6_subnet'] as  $k => $v) {

               // We have completed
               if ($numips6_subnet == $i) {
                  break;
               }

               $newips[$k] = $v;
               $i++;
            }

            $post_vps['ipv6_subnet'] = $newips;

            // Add some IP Subnet
         } elseif ($numips6_subnet > count($post_vps['ipv6_subnet'])) {

            $toadd = $numips6_subnet - count($post_vps['ipv6_subnet']);

            // Assign the IP Subnets
            foreach ($data['ips6_subnet'] as $k => $v) {

               if (in_array($v['ip'], $post_vps['ipv6_subnet'])) {
                  continue;
               }

               $post_vps['ipv6_subnet'][$k] = $v['ip'];

               if ($numips6_subnet == count($post_vps['ipv6_subnet'])) {
                  break;
               }
            }

            // Were there enough IPs
            if (count($post_vps['ipv6_subnet']) < $numips6_subnet) {
               return 'There are insufficient IPv6 Subnets on the server';
            }
         }

         // Remove some IPv6
         if ($numips6 < count($post_vps['ipv6'])) {

            $i = 0;
            $newips = array();

            foreach ($post_vps['ipv6'] as  $k => $v) {

               // We have completed
               if ($numips6 == $i) {
                  break;
               }

               $newips[$k] = $v;
               $i++;
            }

            $post_vps['ipv6'] = $newips;

            // Add some IPs
         } elseif ($numips6 > count($post_vps['ipv6'])) {

            $toadd = $numips6 - count($post_vps['ipv6']);

            // Assign the IPs
            foreach ($data['ips6'] as $k => $v) {

               if (in_array($v['ip'], $post_vps['ipv6'])) {
                  continue;
               }

               $post_vps['ipv6'][$k] = $v['ip'];

               if ($numips6 == count($post_vps['ipv6'])) {
                  break;
               }
            }

            // Were there enough IPs
            if (count($post_vps['ipv6']) < $numips6) {
               return 'There are insufficient IPv6 Addresses on the server';
            }
         }

         // Are there any configurable options
         if (!empty($params['configoptions'])) {
            foreach ($params['configoptions'] as $k => $v) {
               if (!isset($post_vps[$k])) {
                  $post_vps[$k] = $v;
               }
            }
         }

         $post_vps['editvps'] = 1;

         if ($loglevel > 0) \App\Helpers\LogActivity::Save('Post Array : ' . var_export($post_vps, 1));

         $ret = Virtualizor_Curl::make_api_call($params["serverip"], $params["serverpassword"], 'index.php?act=editvs&vpsid=' . $id . '&vps_uuid=' . $uuid, array(), $post_vps);
      } // End of OLD module

      unset($ret['scripts']);
      unset($ret['iscripts']);
      unset($ret['ostemplates']);
      unset($ret['isos']);

      if ($loglevel > 0) \App\Helpers\LogActivity::Save('Post Result : ' . var_export($ret, 1));

      if (empty($ret)) {
         return 'Could not load the server data after processing.' . Virtualizor_Curl::error($params["serverip"]);
      }

      if (!empty($ret['done'])) {

         $result = "success";

         $tmp_ips = array();

         if (!empty($post_vps['ips'])) {
            foreach ($post_vps['ips'] as $k => $v) {
               $tmp_ips[] = $v;
            }
         }

         if (!empty($post_vps['ipv6_subnet'])) {
            foreach ($post_vps['ipv6_subnet'] as $k => $v) {
               $tmp_ips[] = $v;
            }
         }

         if (!empty($post_vps['ipv6'])) {
            foreach ($post_vps['ipv6'] as $k => $v) {
               $tmp_ips[] = $v;
            }
         }

         if (!empty($post_vps['ips_int'])) {
            foreach ($post_vps['ips_int'] as $k => $v) {
               $tmp_ips[] = $v;
            }
         }

         //\App\Helpers\LogActivity::Save(var_export($tmp_ips, 1));

         // The Dedicated IP
         DB::table('tblhosting')
            ->where('id', $serviceid)
            ->update(array(
               'dedicatedip' => $tmp_ips[0]
            ));

         // Extra IPs
         $tmp_cnt = count($tmp_ips);
         if (!empty($tmp_cnt)) {
            unset($tmp_ips[0]);
            DB::table('tblhosting')
               ->where('id', $serviceid)
               ->update(array(
                  'assignedips' => implode("\n", $tmp_ips)
               ));
         }
      } else {

         if (!empty($ret['error'])) {
            return 'Errors : ' . implode('<br>', $ret['error']);
         }

         $result = 'Unknown error occured. Please check logs';
      }

      return $result;
   }

   public function TerminateAccount($params)
   {

      global $virtualizor_conf;

      $loglevel = (int) @$_REQUEST['loglevel'];
      $serviceid = $params["serviceid"]; # Unique ID of the product/service in the WHMCS Database

      if (!empty($virtualizor_conf['loglevel'])) {
         $loglevel = $virtualizor_conf['loglevel'];
      }

      $id = $params['customfields']['vpsid'];

      if (!empty($params['customfields']['vps_uuid'])) {
         $uuid = $params['customfields']['vps_uuid'];
      }

      $ctrlpanel = (empty($params['configoptions'][(new Functions)->v_fn('ctrlpanel')]) ? -1 : $params['configoptions'][(new Functions)->v_fn('ctrlpanel')]);

      if (!empty($virtualizor_conf['admin_ui']['disable_terminate'])) {
         return 'Termination has been disabled by the Global Administrator';
      }

      if (empty($params['customfields']['vpsid'])) {
         $params['customfields']['vpsid'] = $this->virtualizor_getvpsid($params['serviceid']);
      }

      // Setup cPanel licenses if cPanel configurable option is set
      if ($ctrlpanel != -1 && $ctrlpanel != 'none') {

         if ($ctrlpanel == 'cpanel' && !empty($virtualizor_conf['cp']['buy_cpanel_login']) && !empty($virtualizor_conf['cp']['buy_cpanel_apikey'])) {

            $data = Virtualizor_Curl::make_api_call($params["serverip"], $params["serverpassword"], 'index.php?act=vs&vpsid=' . $id . '&vps_uuid=' . $uuid);

            $data = $data['vs'][$params['customfields']['vpsid']]['ips'];

            $cpanel_ip = array_shift($data);

            \App\Helpers\LogActivity::Save("CPANEL : cPanel delete for ip $cpanel_ip");

            $url = 'https://www.buycpanel.com/api/cancel.php?';
            $login = 'login=' . $virtualizor_conf['cp']['buy_cpanel_login'] . '&';
            $key = 'key=' . $virtualizor_conf['cp']['buy_cpanel_apikey'] . '&';
            $currentip = 'currentip=' . $cpanel_ip . '&';
            $url .= $login . $key . $currentip;

            $ret = file_get_contents($url);

            $ret = json_decode($ret);

            if ($ret->success == 0) {
               return 'Errors : cPanel Licensing : ' . $ret->faultstring;
            }
         }

         if ($ctrlpanel == 'cpanel' && !empty($virtualizor_conf['cp']['cpanel_manage2_username']) && !empty($virtualizor_conf['cp']['cpanel_manage2_password'])) {

            // TODO: virt_remove_cpanel_license($params);

         }
      }

      $data = Virtualizor_Curl::make_api_call($params["serverip"], $params["serverpassword"], 'index.php?act=vs&delete=' . $id . '&delete_uuid=' . $uuid);

      if (empty($data)) {
         return 'Could not load the server data.' . Virtualizor_Curl::error($params["serverip"]);
      }

      // echo "<pre>";print_r($params);echo "</pre>";
      // echo "<pre>";print_r($data);echo "</pre>";

      // If the VPS has been deleted
      if ($data['done']) {

         if ($loglevel > 0) \App\Helpers\LogActivity::Save('Data after termination : ' . var_dump($data, 1));

         // vpsid of virtualizor
         $query = DB::table('tblcustomfields')->select('id')->where('relid', $params["pid"])->where('fieldname', 'vpsid')->get();
         $res = (array) $query[0];

         // vps_uuid of virtualizor
         $query1 = DB::table('tblcustomfields')->select('id')->where('relid', $params["pid"])->where('fieldname', 'vps_uuid')->get();
         $res1 = (array) $query1[0];

         DB::table('tblcustomfieldsvalues')
            ->where('relid', $params["serviceid"])
            ->where('fieldid', $res['id'])
            ->update(
               array('value' => '')
            );

         DB::table('tblcustomfieldsvalues')
            ->where('relid', $params["serviceid"])
            ->where('fieldid', $res1['id'])
            ->update(
               array('value' => '')
            );

         if ($loglevel > 0) \App\Helpers\LogActivity::Save("UPDATE `tblcustomfieldsvalues` SET `value` = '' WHERE `relid` = '" . $params["serviceid"] . "' AND `fieldid` = '" . $res['id'] . "'");

         // Do we have to preserve th einformation about the IP
         if (empty($virtualizor_conf['admin_ui']['preserve_info'])) {
            // The Dedicated IP
            DB::table('tblhosting')
               ->where('id', $params["serviceid"])
               ->update(array(
                  'dedicatedip' => '',
                  'assignedips' => ''
               ));
         }
         $result = "success";
      } else {
         $result = empty($data['error_msg']) ? "There was some error deleting the VPS" : $data['error_msg'];
      }

      return $result;
   }

   // TODO: AdminServicesTabFields
   public function AdminServicesTabFields($params)
   {

      // if(!empty($_GET['vapi_mode'])){
      //     ob_end_clean();
      // }

      // $code = virtualizor_newUI($params, 'clientsservices.php?vapi_mode=1&userid='.$params['userid'], '../modules/servers');

      // $fieldsarray = array(
      //  'VPS Information' => '<div style="width:100%" id="tab1"></div>'.$code,
      // );

      // return $fieldsarray;

   }

   public function AdminLink($params)
   {

      global $virtualizor_conf;

      // Find the servers hostname
      $res = DB::table('tblservers')->select('hostname')->where('id', $params['serverid'])->get();
      $server_details = (array) $res[0];
      $params['serverhostname'] = $server_details['hostname'];

      $serverip = empty($params['serverhostname']) ? $params['serverip'] : $params['serverhostname'];

      $code = '<a href="https://' . $serverip . ':4085/index.php?act=login" target="_blank">Virtualizor Admin Panel</a>';
      return $code;
   }

   public function LoginLink($params)
   {

      global $virtualizor_conf;

      // Find the servers hostname
      $res = DB::table('tblservers')->select('hostname')->where('id', $params['serverid'])->get();
      $server_details = (array) $res[0];
      $params['serverhostname'] = $server_details['hostname'];

      $serverip = empty($params['serverhostname']) ? $params['serverip'] : $params['serverhostname'];
      $port = (!empty($virtualizor_conf['use_sso_on_80']) ? 80 : 4083);
      $code = "<a href=\"https://" . $serverip . ":" . $port . "/\" target=\"_blank\" style=\"color:#cc0000\">Login to Virtualizor</a>";
      return $code;
   }

   public function AdminCustomButtonArray()
   {
      # This function can define additional functions your module supports, the example here is a reboot button and then the reboot function is defined below
      $buttonarray = array(
         "Start VPS" => "start",
         "Reboot VPS" => "reboot",
         "Stop VPS" => "stop",
         "Poweroff VPS" => "poweroff",
         "Suspend Network" => "suspend_net",
         "Unsuspend Network" => "unsuspend_net"
      );
      return $buttonarray;
   }

   public function ClientAreaCustomButtonArray()
   {

      global $virtualizor_conf;
      if (!empty($virtualizor_conf['client_ui']['hide_sidebar'])) {
         return array();
      }

      # This function can define additional functions your module supports, the example here is a reboot button and then the reboot function is defined below
      $buttonarray = array(
         "Start VPS" => "start",
         "Reboot VPS" => "reboot",
         "Stop VPS" => "stop",
         "Poweroff VPS" => "poweroff",
      );
      return $buttonarray;
   }

   public function start($params)
   {

      global $virt_action_display, $virt_errors;

      $virt_resp = Virtualizor_Curl::action($params, 'act=start&do=1');

      if (empty($virt_resp['done'])) {
         $virt_action_display = 'The VPS failed to start';
         return $virt_action_display;
      }

      // Done
      return "success";
   }

   public function stop($params)
   {

      global $virt_action_display, $virt_errors;

      $virt_resp = Virtualizor_Curl::action($params, 'act=stop&do=1');

      if (empty($virt_resp)) {
         $virt_action_display = 'Failed to stop the VPS';
         return $virt_action_display;
      }

      // Done
      return "success";
   }

   public function reboot($params)
   {

      global $virt_action_display, $virt_errors;

      $virt_resp = Virtualizor_Curl::action($params, 'act=restart&do=1');

      if (empty($virt_resp)) {
         $virt_action_display = 'Failed to reboot the VPS';
         return $virt_action_display;
      }

      // Done
      return "success";
   }

   public function poweroff($params)
   {

      global $virt_action_display, $virt_errors;

      $virt_resp = Virtualizor_Curl::action($params, 'act=poweroff&do=1');

      if (empty($virt_resp)) {
         $virt_action_display = 'Failed to poweroff the VPS';
         return $virt_action_display;
      }

      // Done
      return "success";
   }

   public function suspend_net($params)
   {

      global $virt_action_display, $virt_errors;

      $id = $params['customfields']['vpsid'];

      if (!empty($params['customfields']['vps_uuid'])) {
         $uuid = $params['customfields']['vps_uuid'];
      }

      $action = 'act=vs&suspend_net=' . $id . '&suspend_net_uuid=' . $uuid;

      $virt_resp = Virtualizor_Curl::make_api_call($params["serverip"], $params["serverpassword"], 'index.php?' . $action, array(), $post);

      if (empty($virt_resp['done'])) {
         $virt_action_display = 'Failed to suspend the VPS network';
         return $virt_action_display;
      }

      // Done
      return "success";
   }

   public function unsuspend_net($params)
   {

      global $virt_action_display, $virt_errors;

      $id = $params['customfields']['vpsid'];

      if (!empty($params['customfields']['vps_uuid'])) {
         $uuid = $params['customfields']['vps_uuid'];
      }

      $action = 'act=vs&unsuspend_net=' . $id . '&unsuspend_net_uuid=' . $uuid;

      $virt_resp = Virtualizor_Curl::make_api_call($params["serverip"], $params["serverpassword"], 'index.php?' . $action, array(), $post);

      if (empty($virt_resp['done'])) {
         $virt_action_display = 'Failed to unsuspend the VPS network';
         return $virt_action_display;
      }

      // Done
      return "success";
   }

   public function TestConnection($params)
   {

      $admin = Virtualizor_Curl::make_api_call($params["serverip"], $params["serverpassword"], 'index.php?act=addvs');
      $client = Virtualizor_Curl::e_make_api_call($params["serverip"], $params["serverpassword"], 0, 'index.php');

      if (empty($admin) || empty($client)) {
         return array('error' => 'FAILED: Could not connect to Virtualizor. Please make sure that all Ports from 4081 to 4085 are open on your WHMCS Server or please check the server details entered are as displayed on Admin Panel >> Configuration >> Server Info.');
      } else {
         return array('success' => true);
      }
   }

   private function virtualizor_getvpsid($serviceid)
   {

      $vpsid = 0;

      $customfields = DB::table('tblcustomfields')
         ->join('tblcustomfieldsvalues', 'tblcustomfields.id', '=', 'tblcustomfieldsvalues.fieldid')
         ->select('tblcustomfields.fieldname', 'tblcustomfieldsvalues.value')
         ->where('tblcustomfieldsvalues.relid', '=', $serviceid)
         ->get();

      foreach ($customfields as $customfield) {
         if ($customfield->fieldname == 'vpsid') {
            $vpsid = $customfield->value;
         }
      }

      return $vpsid;
   }

   function virtualizor_getcustomfields($serviceid)
   {

      $data = array();

      $customfields = DB::table('tblcustomfields')
         ->join('tblcustomfieldsvalues', 'tblcustomfields.id', '=', 'tblcustomfieldsvalues.fieldid')
         ->select('tblcustomfields.fieldname', 'tblcustomfieldsvalues.value')
         ->where('tblcustomfieldsvalues.relid', '=', $serviceid)
         ->get();

      foreach ($customfields as $customfield) {

         if (strpos($customfield->fieldname, '|') !== false) {
            $exploded = explode('|', $customfield->fieldname);
            $customfield->fieldname = $exploded[0];
         }


         if (strpos($customfield->value, '|') !== false) {
            $exploded = explode('|', $customfield->value);
            $customfield->value = $exploded[0];
         }

         $data[$customfield->fieldname] = $customfield->value;
      }

      return $data;
   }

   public function virtualizor_newUI($params, $url_prefix = "", $modules_url = '')
   {
      global $virt_action_display, $virt_errors, $virt_resp, $virtualizor_conf, $whmcsmysql;

      if (empty($params['customfields']['vpsid'])) {
         return 'VPS not provisioned';
      }
      if (!$url_prefix) {
         $url_prefix = route('pages.services.myservices.servicedetails', $params['serviceid']);
         // $url_prefix = route('virtualizor');
      }
      if (!$modules_url) {
         $modules_url = \Module::getPath();
      }
      $url_prefix .= "?action=productdetails";
      $modules_url .= "/Servers";
      $theme_url = \Module::asset('virtualizor:');

      // New method of Virtualizor Module
      if (Request::get('give')) {

         //error_reporting(-1);

         $var['APP'] = 'Virtualizor'; // NOT USED
         $var['site_name'] = 'WHMCS';
         $var['API'] = $url_prefix . '&id=' . $params['serviceid'] . '&api=json&'; // POST
         $var['giver'] = $url_prefix . '&id=' . $params['serviceid'] . '&';
         $var['url'] = $url_prefix . '&id=' . $params['serviceid'] . '&';
         $var['copyright'] = 'Virtualizor';
         $var['version'] = '2.1';
         $var['logo'] = '';
         $var['theme'] = dirname(__FILE__) . "/ui/";
         $var['theme_path'] = dirname(__FILE__) . "/ui/";
         $var['theme_url'] = $theme_url . "/";
         $var['images'] = $theme_url . '/images/';
         $var['virt_dev_license'] = ' ';
         $var['virt_pirated_license'] = ' ';

         if (Request::get('give') == 'index.html') {

            // We are zipping if possible
            if (function_exists('ob_gzhandler')) {
               ob_start('ob_gzhandler');
            }

            // Read the file
            $data = file_get_contents($var['theme_path'] . 'index.html');

            $filetime = filemtime($var['theme_path'] . 'index.html');
         }

         if (Request::get('give') == 'combined.js') {

            // Read the file
            $data = '';
            $jspath = $var['theme_path'] . 'js2/';
            $files = array(
               'jquery.min.js',
               'jquery.dataTables.min.js',
               'jquery.tablesorter.min.js',
               'jquery.flot.min.js',
               'jquery.flot.pie.min.js',
               'jquery.flot.stack.min.js',
               'jquery.flot.time.min.js',
               'jquery.flot.tooltip.min.js',
               'jquery.flot.symbol.min.js',
               'jquery.flot.axislabels.js',
               'jquery.flot.selection.min.js',
               'jquery.flot.resize.min.js',
               'jquery.scrollbar.min.js',
               'popper.min.js',
               'select2.js',
               'bootstrap.min.js',
               'jquery.responsivetabs.js',
               'virtualizor.js',
               'haproxy.js',
            );

            foreach ($files as $k => $v) {
               //echo $k.'<br>';
               $data .= file_get_contents($jspath . '/' . $v) . "\n\n";
            }

            // We are zipping if possible
            if (function_exists('ob_gzhandler')) {
               ob_start('ob_gzhandler');
            }

            // Type javascript
            header("Content-type: text/javascript; charset: UTF-8");

            // Set a zero Mtime
            $filetime = filemtime($var['theme_path'] . 'js2/virtualizor.js');
         }

         if (Request::get('give') == 'style.css') {

            // Read the file
            $data = '';
            $jspath = $var['theme_path'] . 'css2/';
            $files = array(
               'bootstrap.min.css',
               'all.min.css',
               'jquery.dataTables.min.css',
               'select2.css',
               'jquery.scrollbar.css',
               'style.css',
            );

            foreach ($files as $k => $v) {
               //echo $k.'<br>';
               $data .= file_get_contents($jspath . '/' . $v) . "\n\n";
            }

            // Type CSS
            header("Content-type: text/css; charset: UTF-8");

            // We are zipping if possible
            if (function_exists('ob_gzhandler')) {
               ob_start('ob_gzhandler');
            }
         }

         foreach ($var as $k => $v) {
            $data = str_replace('[[' . $k . ']]', $v, $data);
         }

         // Parse the languages
         vload_lang($params['clientsdetails']['language']);
         echo vparse_lang($data);

         die();
         exit(0);
      }

      if (Request::get('api') == 'json') {

         // Overwrite certain variables
         $_GET['svs'] = $params['customfields']['vpsid'];
         $_GET['SET_REMOTE_IP'] = $_SERVER['REMOTE_ADDR'];

         $res = Virtualizor_Curl::action($params, http_build_query($_GET), $_POST);

         $res['uid'] = 0;

         echo json_encode($res);
         die();
         exit(0);
      }

      if (Request::get('b') == 'novnc' || (!empty(Request::get('novnc'))) && Request::get('act') == 'vnc') {

         $data = Virtualizor_Curl::action($params, 'act=vnc&novnc=1');
         $data['info']['virt'] = "";

         // Find the servers hostname
         $res = DB::table('tblservers')->select('hostname')->where('id', $params['serverid'])->get();
         $server_details = (array) $res[0];
         $params['serverhostname'] = $server_details['hostname'];

         // fetch the novnc file
         $modules_url_vnc = dirname(__FILE__);
         $novnc_viewer = file_get_contents($modules_url_vnc . '/novnc/novnc.html');

         $novnc_password = $data['info']['password'];
         $vpsid = $params['customfields']['vpsid'];
         $novnc_serverip = empty($params['serverhostname']) ? $params['serverip'] : $params['serverhostname'];
         $proto = 'http';
         $port = 4081;
         $virt_port = 4082;
         $websockify = 'websockify';
         if (!empty($_SERVER['HTTPS']) || @$_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
            $proto = 'https';
            $port = 4083;
            $virt_port = 4083;
            $websockify = 'novnc/';
            $novnc_serverip = empty($params['serverhostname']) ? $params['serverip'] : $params['serverhostname'];
         }

         if ($data['info']['virt'] == 'xcp') {
            $vpsid .= '-' . $data['info']['password'];

            if (!empty($data['info']['host']) && !empty($data['info']['serid'])) {
               $novnc_serverip = $data['info']['host'];
            }
         }

         echo $novnc_viewer = vlang_vars_name($novnc_viewer, array(
            'HOST' => $novnc_serverip,
            'PORT' => $port,
            'VIRTPORT' => $virt_port,
            'PROTO' => $proto,
            'WEBSOCKET' => $websockify,
            'TOKEN' => $vpsid,
            'PASSWORD' => $novnc_password,
            'MODULE_URL' => $theme_url
         ));


         die();
      }

      // Java VNC
      if (Request::get('act') == 'vnc' && !empty(Request::get('launch'))) {

         $response = Virtualizor_Curl::action($params, 'act=vnc&launch=1&giveapplet=1', '', true);

         if (empty($response)) {
            return false;
         }

         // Is the applet code in the API Response ?
         if (!empty($response['info']['applet'])) {

            $applet = $response['info']['applet'];
         } else {

            $virttype = preg_match('/xcp/is', $params['configoption1']) ? 'xcp' : strtolower($params['configoption1']);

            // NonXCP
            if ($virttype != 'xcp') {

               if (!empty($response['info']['port']) && !empty($response['info']['ip']) && !empty($response['info']['password'])) {
                  $applet = '<APPLET ARCHIVE="https://s2.softaculous.com/a/virtualizor/files/VncViewer.jar" CODE="com.tigervnc.vncviewer.VncViewer" WIDTH="1" HEIGHT="1">
							<PARAM NAME="HOST" VALUE="' . $response['info']['ip'] . '">
							<PARAM NAME="PORT" VALUE="' . $response['info']['port'] . '">
							<PARAM NAME="PASSWORD" VALUE="' . $response['info']['password'] . '">
							<PARAM NAME="Open New Window" VALUE="yes">
						</APPLET>';
               }

               // XCP
            } else {

               if (!empty($response['info']['port']) && !empty($response['info']['ip'])) {
                  $applet = '<APPLET ARCHIVE="https://s2.softaculous.com/a/virtualizor/files/TightVncViewer.jar" CODE="com.tightvnc.vncviewer.VncViewer" WIDTH="1" HEIGHT="1">
							<PARAM NAME="SOCKETFACTORY" value="com.tightvnc.vncviewer.SshTunneledSocketFactory">
							<PARAM NAME="SSHHOST" value="' . $response['info']['ip'] . '">
							<PARAM NAME="HOST" value="localhost">
							<PARAM NAME="PORT" value="' . $response['info']['port'] . '">
							<PARAM NAME="Open New Window" VALUE="yes">
						</APPLET>';
               }
            }
         }

         echo $applet;

         die();
      }

      if (!empty($virtualizor_conf['client_ui']['direct_login'])) {
         return "<center><a href=\"https://" . $params["serverip"] . ":4083/\" target=\"_blank\">Login to Virtualizor</a></center>";
      }

      $code = '';
      $code .= '
		<script data-cfasync="false" type="text/javascript">

			var panel_checker = "";
			var panel_load_try_counter = 0;
			function iResize(){
				try{
					document.getElementById("virtualizor_manager").style.height = 
					document.getElementById("virtualizor_manager").contentWindow.document.documentElement.scrollHeight + "px";
				}catch(e){ };
			}

			setInterval("iResize()", 1000);

			function load_virtpanel(){
				var divID = "tab1";
				if (!document.getElementById(divID)) {
					divID = "domain";
				}
				
				// If we get the div with virtualizor_load_div then do not create new element
				if(document.getElementById("virtualizor_load_div")){
					myDiv = document.getElementById("virtualizor_load_div");
				}else{
					var myDiv = document.createElement("div");
					myDiv.id = "virtualizor_load_div";
				}
				
				myDiv.innerHTML = \'<center style="padding:10px; background-color: #FAFBD9;">Loading Panel options ...</center><br /><br /><br />\';
				
				document.getElementById(divID).appendChild(myDiv);
				
				// If we get the div with virtualizor_manager then do not create new element
				if(document.getElementById("virtualizor_manager")){
					iframe = document.getElementById("virtualizor_manager");
				}else{
					var iframe = document.createElement("iframe");
					iframe.id = "virtualizor_manager";
				}
				
				iframe.width = "100%";
				iframe.style.display = "none";
				iframe.style.border = "none";
				iframe.scrolling = "no";
				iframe.src = "' . $url_prefix . '&id=' . $params['serviceid'] . '&give=index.html#act=vpsmanage";
				document.getElementById(divID).appendChild(iframe);
				
				document.getElementById("virtualizor_manager").onload = function(){
					$("#virtualizor_load_div").hide();
					$(this).show();
					iResize();
				};
				
				$(".moduleoutput").each(function(){
					this.style.display = "none";
				});
			};

			function check_js_loaded(){
				
				if(panel_load_try_counter >= 30){
					clearInterval(panel_checker);
					var divID = "tab1";
					if (!document.getElementById(divID)) {
						divID = "domain";
					}
					document.getElementById(divID).innerHTML = "Failed to detect jQuery, please check jQuery is loaded properly or not";
					return false;
				}
				
				if(window.jQuery){
					load_virtpanel();
					clearInterval(panel_checker);
				}else{
					panel_load_try_counter++;
				}
			};

			panel_checker = setInterval(check_js_loaded,1000);

		</script>
		';
    //   $this->start($params);
      return $code;
   }

   public function ClientArea($params = [])
   {
      return $this->virtualizor_newUI($params);
   }

   public function vpssetup(Req $req)
   {    //keypass lama
    //   $key =  '4ronqkc6jbkbe92i1slreqcxjuw4sfjf';
    //   $pass = 'oqagrlf89d1zsaih5rybr5sss9sujkq1';
      
        //keypass baru
      $key =  'vyfm0ABIerOF8OHIOL8G8q0mY3v6qFfb';
      $pass = '0KbFX7SvlpFWQLVtW85cWHHI2JpwZjiy';
      $ip = '103.28.12.120';
      @date_default_timezone_set('Asia/Jakarta');
      $now = date("Y-m-d h:i:s");
      $userID = $req->user;
      $productID = $req->product;

      //cek total tagihan all layanan 
      $dataLayanan = DB::table('tblhosting')
         ->join('tblcustomfieldsvalues', 'tblcustomfieldsvalues.relid', '=', 'tblhosting.id')
         ->join('tblcustomfields', 'tblcustomfields.id', '=', 'tblcustomfieldsvalues.fieldid')
         ->join('tblproducts', 'tblproducts.id', '=', 'tblhosting.packageid')
         ->where('tblcustomfields.fieldname', 'vpsid')
         ->where('tblcustomfields.type', 'product')
         ->where('tblhosting.id', $productID)
         ->whereIn('tblhosting.domainstatus', ['Active', 'Suspended'])
         ->where('tblhosting.userid', $userID)
         ->select('tblhosting.id', 'tblhosting.userid', 'tblcustomfieldsvalues.value as vpsID', 'tblhosting.domain as hostname', 'tblproducts.name as paket')
         ->get();
      // dd($dataLayanan);
      $totalLayanan = 0;
      $total = 0;
      $allVPS_ID = array();

      $RAM = $req->ram;
      $cpu = $req->cpu;
      //print_r($dataLayanan);
      foreach ($dataLayanan as $layanan) {

         $allVPS_ID[] = $layanan->vpsID;
         $spek = (new Resourcefunctions)->paketVPS(trim($layanan->paket));
         $hargaPaket = $spek['perhour'];

         //Additional RAM
         //additional_ram|Additional RAM
         $AdditionalRAM = DB::table('tblhostingconfigoptions')
            ->join('tblproductconfigoptions', 'tblproductconfigoptions.id', '=', 'tblhostingconfigoptions.optionid')
            ->join('tblproductconfigoptionssub', 'tblproductconfigoptionssub.id', '=', 'tblhostingconfigoptions.optionid')
            ->where('tblproductconfigoptions.id', 3)
            ->where('tblhostingconfigoptions.relid', $layanan->id)
            ->select('tblproductconfigoptionssub.optionname as qty')
            ->first();


         // dd(\DB::getQueryLog());
         // /* ..print_r($AdditionalRAM); */
         // //echo	$AdditionalRAM->qty.'<br>';
         // dd($AdditionalRAM->toSql());
         if (isset($AdditionalRAM->qty) && intval($AdditionalRAM->qty) > 0) {  // intval($AdditionalRAM->qty) > 0
            $ramnya = intval($AdditionalRAM->qty) / 1024;
            $hargaPaket = $hargaPaket + ($ramnya * 150);
         }
         //Additional Cores
         /* $AdditionalCores=DB::table('tblhostingconfigoptions')
				->join('tblproductconfigoptions','tblproductconfigoptions.id','=','tblhostingconfigoptions.optionid')
				->where('tblproductconfigoptions.optionname','Additional Cores')
				->where('tblhostingconfigoptions.relid',$vpsData->id)
				->select('tblhostingconfigoptions.id','tblhostingconfigoptions.qty')
				->first(); */


         //additional_cores|Additional Cores
         $AdditionalCores = DB::table('tblhostingconfigoptions')
            ->join('tblproductconfigoptions', 'tblproductconfigoptions.id', '=', 'tblhostingconfigoptions.optionid')
            ->join('tblproductconfigoptionssub', 'tblproductconfigoptionssub.id', '=', 'tblhostingconfigoptions.optionid')
            ->where('tblproductconfigoptions.id', 1)
            ->where('tblhostingconfigoptions.relid', $layanan->id)
            ->select('tblproductconfigoptionssub.optionname as qty')
            ->first();
         //print_r($AdditionalCores);	

         if (isset($AdditionalCores->qty) && intval($AdditionalCores->qty) > 0) {
            $hargaPaket = $hargaPaket + (intval($AdditionalCores->qty) * 150);
         }

         //Additional Bandwidth
         // $AdditionalBandwidth = DB::table('tblhostingconfigoptions')
         //     ->join('tblproductconfigoptions', 'tblproductconfigoptions.id', '=', 'tblhostingconfigoptions.optionid')
         //     ->where('tblproductconfigoptions.optionname', 'Additional Bandwidth')
         //     ->where('tblhostingconfigoptions.relid', $vpsData->id)
         //     ->select('tblhostingconfigoptions.id', 'tblhostingconfigoptions.qty')
         //     ->first();
         // dd($AdditionalBandwidth);
         // if ($AdditionalBandwidth['qty'] > 0) {
         //     $hargaPaket = $hargaPaket + ($AdditionalBandwidth->qty * 150);
         // }

         //sum
         $totalLayanan += $hargaPaket;
      }

      $vps = DB::table('tblhosting')
         ->join('tblcustomfieldsvalues', 'tblcustomfieldsvalues.relid', '=', 'tblhosting.id')
         ->join('tblcustomfields', 'tblcustomfields.id', '=', 'tblcustomfieldsvalues.fieldid')
         ->join('tblproducts', 'tblproducts.id', '=', 'tblhosting.packageid')
         ->where('tblcustomfields.fieldname', 'vpsid')
         ->where('tblcustomfields.type', 'product')
         ->where('tblhosting.id', $productID)
         ->where('tblhosting.userid', $userID)
         ->select('tblhosting.id', 'tblhosting.userid', 'tblcustomfieldsvalues.value as vpsID', 'tblhosting.domain as hostname', 'tblproducts.name as paket')
         ->first();

      if (!$vps->vpsID) {
         $alert = 'Server is not found or Suspended';
      } else {
         //spek paket
         $spek = (new Resourcefunctions)->paketVPS(trim($vps->paket));
         // dd($spek);
         $tu_RAM = 0;
         $tu_Cores = 0;
         $tu_Bandwidth = 0;
         $tu_space = 0;
         $admin = new Virtualizor_Admin_API($ip, $key, $pass);
         $post = [
            'vpsid' =>  $vps->vpsID,
            'vsstatus' => 'u',
         ];
         $getVPS = $admin->listvs($page = 0, $reslen = 0, $post);
         // $admin->r($getVPS);
         //die();
         if ($getVPS) {
            $DataVps = (object) $getVPS[$vps->vpsID];

            /* $admin->r($spek);
				exit(); */
            // dd($spek);
            $vpsRAM = $spek['ram'];
            $vpsCORE = $spek['core'];
            $vpsSPACE = $spek['space'];
            $vpsBANDWIDTH = $spek['bandwidth'];
            $vpsPerday = $spek['perday'];
            $vpsPerhour = $spek['perhour'];

            $totalUPGRADE = 0;
            //	echo '<br>';
            //ram or core
            $format = [1 => 1, 2 => 2, 3 => 4, 4 => 6, 5 => 8, 6 => 16];
            $formatCore = [1 => 1, 2 => 2, 3 => 4, 4 => 6, 5 => 8, 6 => 16];
            $formatOUT = [1 => 1, 2 => 2, 4 => 3, 6 => 4, 8 => 5, 16 => 6];
            /* ------------ echo $RAM; */
            /* if($RAM > $vpsRAM){
					$kuarang=$vpsRAM;
					$upRAM=addons('ram',$vpsRAM,$format[$RAM]);
					$totalUPGRADE+=$upRAM['price'];
					//upgrade
					
					$vpsRAM=$format[$RAM];
					$plusRAM=$format[$RAM] - $kuarang;
					
				}else{
					$vpsRAM=$format[$RAM];	
					$plusRAM=0;
					
				} */

            // $vpsRAM = $vpsRAM * 1024;

            if ($RAM != 0) {
               $harga_ram = (new Resourcefunctions)->additional_addons('ram');
               $totalUPGRADE += $RAM * $harga_ram;
               $valRAM = $RAM * 1024;
               $new_ram = $vpsRAM + $valRAM;
               $new_ram =  $valRAM;
            } else {
               $valRAM = '0';
               $new_ram = $vpsRAM;
            }
            //additional_addons
            //---------------- core sini
            /* if($cpu > $vpsCORE){
					
					$upCORE=addons('core',$vpsCORE,$format[$cpu]);
					$totalUPGRADE+=$upCORE['price'];
					
					$plusCORE=$formatCore[$cpu] - $vpsCORE;
					$vpsCORE=$formatCore[$cpu];
				}else{
					$vpsCORE=$formatCore[$cpu];
					$plusCORE=0;
				} */

            //new flow

            if ($cpu != 0) {
               $harga_core = (new Resourcefunctions)->additional_addons('core');
               $totalUPGRADE += $cpu * $harga_core;
               $var_core = $cpu;
               $new_core = $vpsCORE + $cpu;
            } else {
               $var_core = '0';
               $new_core = $vpsCORE;
            }


            //echo $totalUPGRADE;exit();
            /** ------------------ bandwith**/
            /* $HitungBandwith=$vpsBANDWIDTH + $bandwidth;
				
				if($HitungBandwith > $vpsBANDWIDTH){
					$upBandwidth=addons('bandwidth',$vpsBANDWIDTH,$bandwidth);
					$totalUPGRADE+=$upBandwidth['price'];	  
					$plusBandwidth=$upBandwidth['up'];			  
					$vpsBANDWIDTH=$HitungBandwith;
				}else{
					$vpsBANDWIDTH=$vpsBANDWIDTH;
					$plusBandwidth=0;
				} */
            //$config=DB::table('tblproductconfigoptions')->where('id',4)->select('id')->first();
            /* $dataB=DB::table('tblhostingconfigoptions')
									->where('relid', $vps->id)
									->where('optionid', $config->id)
									->select('qty')->first();
				//print_r($dataB);
				$dataB-> */
            //echo $plusBandwidth;
            /* DB::table('tblhostingconfigoptions')
									->where('relid', $vps->id)
									->where('optionid', $config->id)
									->update(['qty' => $plusBandwidth]); */


            // 	echo $totalUPGRADE;
            //echo $totalUPGRADE.'</br>'; 
            //$total=$vpsPerhour +  $totalUPGRADE;


            //jika deposit mencukupi jalankan
            $deposit = (new Resourcefunctions)->cekDeposit($userID);


            $total = $total + $totalLayanan;
            /* print_r($total);
					exit(); */

            $errormsg = '';
            if ($deposit >=  $totalUPGRADE) {

               $vpsRAM = $vpsRAM * 1024;

               if ($RAM != 0) {
                  $harga_ram = (new Resourcefunctions)->additional_addons('ram');
                  $totalUPGRADE = $RAM * $harga_ram;
                  $valRAM = $RAM * 1024;
                  $new_ram = $vpsRAM + $valRAM;
                  $param = array();
                  $param['userid']            = $vps->userid;
                  $param['itemdescription1']    = $vps->paket . ' (' . $vps->hostname . ') Additional RAM up to ' . $RAM . ' GB';
                  $param['itemamount1']        = $totalUPGRADE;
                  //$param['itemtaxed1']		= false;
                  /* $admin->r($param);
						exit(); */
                  $sent = (new Resourcefunctions)->createInvoice($param);
                  // dd($sent);
                  // \Log::debug('CreateInvoice');
                  // \Log::debug($sent);
                  //$admin->r($param);exit();

                  if ($sent['result'] == 'success') {
                     $appy = (new Resourcefunctions)->ApplyCreditInvoice($sent['invoiceid']);
                     // dd($appy);
                     if ($appy['invoicepaid'] == 'true') {
                        $paramUpgrade = [
                           'vpsid' => $DataVps->vpsid,
                           'ram'        => $new_ram
                        ];

                        /* $admin->r($paramUpgrade);exit();			
									die(); */

                        //$admin->stop($DataVps->vpsid);
                        $sentUpgrade = $admin->managevps($paramUpgrade);
                        // dd($sentUpgrade);
                        if (empty($sentUpgrade['error'])) {
                           $now = date("Y-m-d H:i:s");
                           $time_aktivite = DB::table('jamaktivasi')
                              ->where('product_id', $productID)
                              ->where('type', 'ram')
                              ->select('id')->first();

                           if (isset($time_aktivite->id)) {
                              DB::table('jamaktivasi')
                                 ->where('id', $time_aktivite->id)
                                 ->update(['jamaktivasi' => $now]);
                           } else {
                              DB::table('jamaktivasi')->insert(['product_id' => $productID, 'jamaktivasi' => $now, 'type' => 'ram']);
                           }
                        } else {
                           $valRAM = '0';

                           foreach ($sentUpgrade['error'] as $k => $v) {
                              $errormsg .= $v . '</br>';
                           }
                           //$alert=$msg;

                        }
                     } else {
                        $valRAM = '0';
                     }
                  } else {
                     $valRAM = '0';
                  }
                  //$admin->start($DataVps->vpsid);



               } else {
                  $valRAM = '0';
                  $new_ram = $vpsRAM;

                  $spek = (new Resourcefunctions)->paketVPS(trim($vps->paket));
                  $vpsRAM = $spek['ram'];
                  $vpsRAM = $vpsRAM * 1024;

                  $paramUpgrade = [
                     'vpsid' => $DataVps->vpsid,
                     'ram'        => $vpsRAM
                  ];
                  /* $admin->r($paramUpgrade);exit();			
								die(); */
                  $sentUpgrade = $admin->managevps($paramUpgrade);
               }


               $config = DB::table('tblproductconfigoptions')->where('id', 3)->select('id')->first();
               $option = DB::table('tblproductconfigoptionssub')
                  ->where('configid', $config->id)
                  ->where('optionname', $valRAM)
                  ->select('id')->first();

               DB::table('tblhostingconfigoptions')
                  ->where('relid', $vps->id)
                  ->where('configid', $config->id)
                  ->update(['optionid' => $option->id]);



               //cpu
               if ($cpu != 0) {
                  $harga_core = (new Resourcefunctions)->additional_addons('core');

                  $hargaCore = $cpu * $harga_core;
                  $var_core = $cpu;
                  $new_core = $vpsCORE + $cpu;

                  $param = array();
                  $param['userid']            = $vps->userid;
                  $param['itemdescription1']    = $vps->paket . ' (' . $vps->hostname . ') Additional Cores Up to ' . $var_core . ' Core';
                  $param['itemamount1']        = $hargaCore;
                  //$param['itemtaxed1']		= false;
                  $sent = (new Resourcefunctions)->createInvoice($param);
                  if ($sent['result'] == 'success') {
                     $appy = (new Resourcefunctions)->ApplyCreditInvoice($sent['invoiceid']);
                     if ($appy['invoicepaid'] == 'true') {
                        $paramUpgrade = [
                           'vpsid' => $DataVps->vpsid,
                           'cores'        => $new_core,
                        ];

                        //$admin->r($paramUpgrade);exit();			
                        //die(); 
                        $sentUpgrade = $admin->managevps($paramUpgrade);
                        //$admin->stop($DataVps->vpsid);

                        if (empty($sentUpgrade['error'])) {
                           $now = date("Y-m-d H:i:s");
                           $time_aktivite = DB::table('jamaktivasi')
                              ->where('product_id', $productID)
                              ->where('type', 'cpu')
                              ->select('id')->first();
                           if (isset($time_aktivite->id)) {
                              DB::table('jamaktivasi')
                                 ->where('id', $time_aktivite->id)
                                 ->update(['jamaktivasi' => $now]);
                           } else {
                              DB::table('jamaktivasi')->insert(['product_id' => $productID, 'jamaktivasi' => $now, 'type' => 'cpu']);
                           }
                        } else {
                           $valRAM = '0';

                           foreach ($sentUpgrade['error'] as $k => $v) {
                              $errormsg .= $v . '</br>';
                           }
                           //$alert=$msg;

                        }

                        //$admin->start($DataVps->vpsid);



                     } else {
                     }
                  } else {
                     $var_core = '0';
                  }
               } else {
                  $var_core = '0';
                  $new_core = $vpsCORE;
                  $spek = (new Resourcefunctions)->paketVPS(trim($vps->paket));
                  $core = $spek['core'];
                  //$vpsRAM=$vpsRAM * 1024;

                  $paramUpgrade = [
                     'vpsid' => $DataVps->vpsid,
                     'cores'        => $core
                  ];
                  /* $admin->r($paramUpgrade);exit();			
								die(); */
                  $sentUpgrade = $admin->managevps($paramUpgrade);
                  //$admin->stop($DataVps->vpsid);



               }
               $admin->stop($DataVps->vpsid);

               // $plusCORE = 0;
               // $plusCORE = ($plusCORE == 0) ? '-' : $plusCORE;
               $config = DB::table('tblproductconfigoptions')->where('id', 1)->select('id')->first();
               $option = DB::table('tblproductconfigoptionssub')
                  ->where('configid', $config->id)
                  ->where('optionname', $var_core)
                  ->select('id')->first();
               //echo $config->id;
               //echo $plusCORE;
               DB::table('tblhostingconfigoptions')
                  ->where('relid', $vps->id)
                  ->where('configid', $config->id)
                  ->update(['optionid' => $option->id]);



               /* $vpsRAM=$vpsRAM * 1024;
					$paramUpgrade=[
									'vpsid' => $DataVps->vpsid,
									/* 'bandwidth' => $vpsBANDWIDTH, */
               //'cores'		=> $new_core,
               //'ram'		=> $new_ram
               //]; */
               //$admin->r($paramUpgrade);exit();			
               //die(); 
               //$sentUpgrade = $admin->managevps($paramUpgrade);
               //$admin->r($sentUpgrade);exit();
               /* if(empty($sentUpgrade['error'])){
						$error=false;
						$alert='manage resources success';
						
						//aktivitas
						$now=date("Y-m-d H:i:s");
						$time_aktivite=DB::table('jamaktivasi')->where('product_id',$productID)->select('id')->first();
						if($time_aktivite->id){
							DB::table('jamaktivasi')
									->where('id', $time_aktivite->id)
									->update(['jamaktivasi' => $now]);
						}else{
							DB::table('jamaktivasi')->insert(['product_id' => $productID, 'jamaktivasi' => $now]);
									
						}
						
					}else{
						
						$msg='';
						foreach($sentUpgrade['error'] as $k=>$v ){
							$msg.=$v.'</br>';
						}
						$alert=$msg;
						$command = 'SendAdminEmail';
						$postData = array(
							'messagename' => 'Error Upgrade Server '.$productID.' : '.$alert,
							
						);
						$adminUsername = ''; // Optional for WHMCS 7.2 and later
						$results = localAPI($command, $postData, $adminUsername);
						//print_r($results);	
					}
					*/
               // sleep(5);

               if (!empty($errormsg)) {
                  $alert = $errormsg;
               } else {
                  $admin->start($DataVps->vpsid);
                  $error = false;
                  $alert = 'manage resources success';
               }
            } else {
               $alert = 'Deposit is not enough!';
            }
         } else {
            $alert = 'Server is not found or not active';
         }
      }

      echo json_encode(['error' => $error, 'alert' => $alert]);
   }

   public function checkvps(Req $req)
   {
       //keypass lama
    //   $key =  '4ronqkc6jbkbe92i1slreqcxjuw4sfjf';
    //   $pass = 'oqagrlf89d1zsaih5rybr5sss9sujkq1';
      
        //keypass baru
      $key =  'vyfm0ABIerOF8OHIOL8G8q0mY3v6qFfb';
      $pass = '0KbFX7SvlpFWQLVtW85cWHHI2JpwZjiy';
      $ip = '103.28.12.120';
      @date_default_timezone_set('Asia/Jakarta');
      $now = date("Y-m-d h:i:s");

      $productID = intval($req->get('id'));
      $userID = intval($req->get('user'));

      $vps = DB::table('tblhosting')
         ->join('tblcustomfieldsvalues', 'tblcustomfieldsvalues.relid', '=', 'tblhosting.id')
         ->join('tblcustomfields', 'tblcustomfields.id', '=', 'tblcustomfieldsvalues.fieldid')
         ->join('tblproducts', 'tblproducts.id', '=', 'tblhosting.packageid')
         ->where('tblcustomfields.fieldname', 'vpsid')
         ->where('tblcustomfields.type', 'product')
         ->where('tblhosting.id', $productID)
         ->where('tblhosting.userid', $userID)
         ->select('tblhosting.id', 'tblcustomfieldsvalues.value as vpsID', 'tblproducts.name as paket')
         ->first();
      if (!$vps->vpsID) {
         $alert = 'Server not found';
         $param = array('error' => true);
      } else {

         /*default paket spek*/
         $getVPS_paket = (new Resourcefunctions)->paketVPS(trim($vps->paket));
         //print_r($getVPS_paket);
         $default_ram = $getVPS_paket['ram'];
         $default_core = $getVPS_paket['core'];



         /*get virtualizor spek*/
         $admin = new Virtualizor_Admin_API($ip, $key, $pass);
         $post = array();
         $post = [
            'vpsid' =>  $vps->vpsID,
            'vsstatus' => 'u'
         ];
         $getVPS = $admin->listvs($page = 0, $reslen = 0, $post);
         // $admin->r($getVPS);
         // HOTFIX: [2021-12-30 16:34:18] local.ERROR: Undefined offset: 849 {"userId":188,"exception":"[object] (ErrorException(code: 0): Undefined offset: 849 at /Users/cecepaprilianto/Desktop/qwords/cbms-auto/Modules/Servers/Virtualizor/Http/Controllers/VirtualizorController.php:3058)
         $DataVps = (object) $getVPS[$vps->vpsID];
         $format = [0 => 0, 1 => 1, 2 => 2, 4 => 3, 6 => 4, 8 => 5, 16 => 6];
         $formatCore = [0 => 0, 1 => 1, 2 => 2, 4 => 3, 6 => 4, 8 => 5, 16 => 6];

         //from virtulizor spek

         $virtualizor_ram = ($DataVps->ram / 1024);
         $virtualizor_core = $DataVps->cores;

         //Additional Addons
         $addons_ram = ($default_ram < $virtualizor_ram) ? ($virtualizor_ram - $default_ram) : 0;
         $addons_core = ($default_core < $virtualizor_core) ? ($virtualizor_core - $default_core) : 0;



         $AdditionalBandwidth = DB::table('tblhostingconfigoptions')
            ->join('tblproductconfigoptions', 'tblproductconfigoptions.id', '=', 'tblhostingconfigoptions.optionid')
            ->where('tblproductconfigoptions.optionname', 'Additional Bandwidth')
            ->where('tblhostingconfigoptions.relid', $vps->id)
            ->select('tblhostingconfigoptions.id', 'tblhostingconfigoptions.qty')
            ->first();
         //print_r($AdditionalBandwidth);

         /* $param=array(
                            'error'	=> false,
                            'cores' => (int) $formatCore[$DataVps->cores],
                            'hdd' => (int)  $DataVps->space,
                            'bandwidth' => (int)  $AdditionalBandwidth->qty,
                            'ram' => $format[($DataVps->ram / 1024)],
                        ); */
         $param = array(
            'error'    => false,
            'cores' => (int) $addons_core,
            'hdd' => (int)  $DataVps->space,
            // 'bandwidth' => (int)  $AdditionalBandwidth->qty ,
            'ram' => (int)$addons_ram,
         );
      }
      // dd($AdditionalBandwidth);
      return json_encode($param);
   }

   public function cancelvps(Req $req)
   {
       //keypass lama
    //   $key =  '4ronqkc6jbkbe92i1slreqcxjuw4sfjf';
    //   $pass = 'oqagrlf89d1zsaih5rybr5sss9sujkq1';
      
        //keypass baru
      $key =  'vyfm0ABIerOF8OHIOL8G8q0mY3v6qFfb';
      $pass = '0KbFX7SvlpFWQLVtW85cWHHI2JpwZjiy';
      $ip = '103.28.12.120';
      @date_default_timezone_set('Asia/Jakarta');
      $now = date("Y-m-d h:i:s");
      /* print_r($_POST); */

      $productID = (int) $req->get('id');
      $user = (int) $req->get('user');
      $error = true;
      $alert = '';
      $vps = DB::table('tblhosting')
         ->join('tblcustomfieldsvalues', 'tblcustomfieldsvalues.relid', '=', 'tblhosting.id')
         ->join('tblcustomfields', 'tblcustomfields.id', '=', 'tblcustomfieldsvalues.fieldid')
         ->join('tblproducts', 'tblproducts.id', '=', 'tblhosting.packageid')
         ->where('tblcustomfields.fieldname', 'vpsid')
         ->where('tblcustomfields.type', 'product')
         ->where('tblhosting.id', $productID)
         ->where('tblhosting.userid', $user)
         ->select('tblhosting.id', 'tblcustomfieldsvalues.value as vpsID', 'tblhosting.domain as hostname', 'tblproducts.name as paket', 'tblhosting.userid')
         ->first();

      //print_r($vps);

      if (!$vps->vpsID) {
         $alert = 'Server is not found or Suspended..!';
      } else {


         $admin = new Virtualizor_Admin_API($ip, $key, $pass);
         $post = ['vpsid' => $vps->vpsID];
         $getVitualizor = $admin->listvs($page = 0, $reslen = 0, $post);

         if (!empty($getVitualizor[$vps->vpsID])) {

            $delete = $admin->delete_vs($vps->vpsID);
            //$delete['done']=1;
            DB::table('tblhosting')
               ->where('id', $vps->id)
               ->update(['domainstatus' => 'Terminated', 'notes' => 'Terminated by client']);


            //$admin->r($delete);
            //$delete['done']=1;
            if ($delete['done']) {

               /* $param						=array();
			$param['userid']			= $vps->userid;
			$getVPS_paket=paketVPS(trim($vps->paket));
									
			$param['itemdescription1']	= $vps->paket .' ('.$vps->hostname.')' ;
			$param['itemamount1']		= $getVPS_paket['perhour'];
			$param['itemtaxed1']		= '1'; */

               //Additional RAM
               /* $AdditionalRAM=Capsule::table('tblhostingconfigoptions')
				->join('tblproductconfigoptions','tblproductconfigoptions.id','=','tblhostingconfigoptions.optionid')
				->where('tblproductconfigoptions.optionname','Additional RAM')
				->where('tblhostingconfigoptions.relid',$vps->id)
				->select('tblhostingconfigoptions.qty')
				->first(); */


               /* $AdditionalRAM=Capsule::table('tblhostingconfigoptions')
									->select('tblproductconfigoptionssub.optionname as qty')
									->join('tblproductconfigoptionssub','tblhostingconfigoptions.optionid','=','tblproductconfigoptionssub.id')
									->where('tblhostingconfigoptions.relid',$vps->id)
									->where('tblhostingconfigoptions.configid',3)->first(); */
               //$admin->r($AdditionalRAM);
               /* $i=2;
			if(intval($AdditionalRAM->qty)){
				//$getPrice=addonsInvoice('ram',$getVPS_paket['ram'],$AdditionalRAM->qty);
				$ramnya=intval($AdditionalRAM->qty) / 1024;
				$getPrice=additional_addons('ram');
				$hargaRam=$getPrice * $ramnya;
				// $getPrice['up'];
				$param['itemdescription'.$i]	= 'Additional RAM up to '. $ramnya.' GB';
				$param['itemamount'.$i]			= $hargaRam;
				$param['itemtaxed'.$i]			= '1';
				
				$i=$i+1;
			} */


               //Additional Cores
               /* $AdditionalCores=Capsule::table('tblhostingconfigoptions')
				->join('tblproductconfigoptions','tblproductconfigoptions.id','=','tblhostingconfigoptions.optionid')
				->where('tblproductconfigoptions.optionname','Additional Cores')
				->where('tblhostingconfigoptions.relid',$vps->id)
				->select('tblhostingconfigoptions.id','tblhostingconfigoptions.qty')
				->first(); */
               /* $AdditionalCores=Capsule::table('tblhostingconfigoptions')
									->select('tblproductconfigoptionssub.optionname as qty')
									->join('tblproductconfigoptionssub','tblhostingconfigoptions.optionid','=','tblproductconfigoptionssub.id')
									->where('tblhostingconfigoptions.relid',$vps->id)
									->where('tblhostingconfigoptions.configid',1)->first();	 */



               //$admin->r($AdditionalCores);
               /* if(intval($AdditionalCores->qty)){
				$getPrice=additional_addons('core');
				$hargaCore=$getPrice * intval($AdditionalCores->qty);
				
				
				$param['itemdescription'.$i]	= 'Additional Cores Up to '. intval($AdditionalCores->qty).' Core';
				$param['itemamount'.$i]			= $hargaCore;
				$param['itemtaxed'.$i]			= '1';
				$i=$i+1;
			} */

               //Additional Bandwidth
               /* $cek=Capsule::table('bandwidth_mode')->where('vps_id',$vps->vpsID)->select('id')->first();
			if($cek->id){
				$spek=paketVPS(trim($vps->paket));
				$bandwidthDefault=$spek['bandwidth'];
				$bandwidthUSED=ceil($bandwidthUSED);
				
				$HisBandwith=Capsule::table('bandwidth_usage')
												->where('vps_id',$vps->vpsID)
												->select('bandwidth')
												->orderBy('id', 'desc')
												->first();
				
				if($HisBandwith->bandwidth){
					 $bandwidthDefault=$HisBandwith->bandwidth;
				}
				
				if($bandwidthUSED > $bandwidthDefault){
					$get_bandwidth=$bandwidthUSED - $bandwidthDefault;
					Capsule::table('bandwidth_usage')->insert(
						['vps_id' => $vps->vpsID ,'bandwidth' => $bandwidthUSED,'waktu' => $now]
					);

					$getPrice=addonsInvoice('bandwidth',0,$get_bandwidth);
					$param['itemdescription'.$i]	= 'Over Bandwidth '. $get_bandwidth.' GB';
					$param['itemamount'.$i]			= $getPrice['price'];
					$param['itemtaxed'.$i]			= '1';
					$i=$i+1;
				}
				
				
			} */


               /* $AdditionalBandwidth=Capsule::table('tblhostingconfigoptions')
			->join('tblproductconfigoptions','tblproductconfigoptions.id','=','tblhostingconfigoptions.optionid')
			->where('tblproductconfigoptions.optionname','Additional Bandwidth')
			->where('tblhostingconfigoptions.relid',$vps->id)
			->select('tblhostingconfigoptions.id','tblhostingconfigoptions.qty')
			->first();
			
			if($AdditionalBandwidth->qty){
				$getPrice=addonsInvoice('bandwidth',0,$AdditionalBandwidth->qty);
				$param['itemdescription'.$i]	= 'Additional Bandwidth Up to '. $AdditionalBandwidth->qty.' GB';
				$param['itemamount'.$i]			= $getPrice['price'];
				$param['itemtaxed'.$i]			= '1';
				$i=$i+1;
			} */


               /* print_r($param);
			exit(); */
               //$sent=createInvoice($param);
               //$admin->r($sent);
               //if($sent['result'] == 'success'){
               /* Capsule::table('vps_kavm_invoice')->insert(
					['user_id' => $vpsData->userid,'vps_id' => $vpsData->vpsID, 'invoice' => $sent['invoiceid'], 'total' => getInvoiceTotalVPS($sent['invoiceid']), 'date_created' => date('Y-m-d') ]
				); */

               //$appy=ApplyCreditInvoice($sent['invoiceid']);
               /* if($appy['invoicepaid'] != 'true'){
					//suspen sik yo
					$suspend=$admin->suspend($data->vps_id);
					//echo 'suspand';
					//$admin->r($suspend);
					
				} */
               $error = false;
               $alert = 'Server cancellation success';
               //}else{
               //	$alert='Error cancelling Server, Please Contact Support';

               //}

            } else {

               $alert = 'Error deleting on Master Server';
            }
         } else {
            $alert = 'Error cancelling Server';
         }
      }

      echo json_encode(['error' => $error, 'alert' => $alert]);
   }
   
   public function get_server_pass_from_cbms($pass)
   {
      return \App\Helpers\Sanitize::decode((new \App\Helpers\Pwd())->decrypt($pass));
   }
}

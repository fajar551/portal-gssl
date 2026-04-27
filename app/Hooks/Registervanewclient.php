<?php

namespace App\Hooks;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class Registervanewclient
{
	public function handle(Request $request)
	{
		$userid = $request->input('userid');
		$dataclient = DB::table('tblclients')->where('id', $userid)->first();

		$dataclientcount = DB::table('fixedva')->where('clientid', $userid)->count();

		if ($dataclientcount == 0) {
            $userId = $dataclient->id;
			$companyname = $dataclient->companyname;
			$firstname = $dataclient->firstname;

			if ($companyname != '' && $companyname != ' ') {
				$name = $companyname;
				$name = str_replace(" ", "", $name);
			} else {
				$name = $firstname;
				if (strlen($name) > 10) {
					$name = $firstname;
					$name = str_replace(" ", "", $name);
				}
			}

			// MULAI DARI SINI API NICEPAY
			// $merFixAcctId = str_pad($userId, 7, "0", STR_PAD_LEFT);
            // $merFixAcctId = str_pad($merFixAcctId, 8, "2", STR_PAD_LEFT);
			$merFixAcctId = str_pad($userId, 7, "0", STR_PAD_LEFT);
            $merFixAcctId = str_pad($merFixAcctId, 8, "2", STR_PAD_LEFT); // Ubah padding menjadi 8 karakter dengan nol
            Log::info("Merchant Fixed Account ID: $merFixAcctId");

			$iMid = 'QWORDS0005';
			$iKey = '0BvSWubMfGqCLNpPC77NcTNVv9lZH+4XwHy2fY2a/NkUzp4BBWWdp2qGyHkF03m7hoiAZNTOQw6JCNiLY3MZ5g==';
			$merchantToken2 = $iMid . $merFixAcctId . $iKey;
			$merchantToken = hash('sha256', $iMid . $merFixAcctId . $iKey);
			$timeoutconnect = '60';
			$timeoutread = '90';

			$sock = 0;
			$apiUrl;
			$port = 443;
			$status;
			$headers = "";
			$body = "";
			$request;
			$errorcode;
			$errormsg;
			$log;
			$timeout;

			$postfields = array(
				'iMid' => $iMid,
				'customerId' => $merFixAcctId,
				'customerNm' => $name,
				'merchantToken' => $merchantToken,
			);

			$apiUrl = "https://www.nicepay.co.id/nicepay/api/vacctCustomerRegist.do";

			// API OPEN SOCKET
			$host = parse_url($apiUrl, PHP_URL_HOST);
			$tryCount = 0;
			if (! $sock = @fsockopen("ssl://" . $host, 443, $errno, $errstr, $timeoutconnect)) {
				while ($tryCount < 5) {
					if ($sock = @fsockopen("ssl://" . $host, 443, $errno, $errstr, $timeoutconnect)) {
						return true;
					}
					sleep(2);
					$tryCount++;
				}
				$errorcode = $errno;
				switch ($errno) {
					case -3:
						$errormsg = 'Socket creation failed (-3)';
					case -4:
						$errormsg = 'DNS lookup failure (-4)';
					case -5:
						$errormsg = 'Connection refused or timed out (-5)';
					default:
						$errormsg = 'Connection failed (' . $errno . ')';
						$errormsg .= ' ' . $errstr;
				}
			}

			// API OPEN REQUEST
			$host = parse_url($apiUrl, PHP_URL_HOST);
			$uri = parse_url($apiUrl, PHP_URL_PATH);
			$headers = "";
			$body = "";

			$querystring = '';
			if (is_array($postfields)) {
				foreach ($postfields as $key => $val) {
					if (is_array($val)) {
						foreach ($val as $val2) {
							if ($key != "key")
								$querystring .= urlencode($key) . '=' . urlencode($val2) . '&';
						}
					} else {
						if ($key != "key")
							$querystring .= urlencode($key) . '=' . urlencode($val) . '&';
					}
				}
				$querystring = substr($querystring, 0, -1);
			} else {
				$querystring = $postfields;
			}

			$postdata = $querystring;

			/* Write */
			$request = "POST " . $uri . " HTTP/1.0\r\n";
			$request .= "Connection: close\r\n";
			$request .= "Host: " . $host . "\r\n";
			$request .= "Content-type: application/x-www-form-urlencoded\r\n";
			$request .= "Content-length: " . strlen($postdata) . "\r\n";
			$request .= "Accept: */*\r\n";
			$request .= "\r\n";
			$request .= $postdata . "\r\n";
			$request .= "\r\n";
			if ($sock) {
				fwrite($sock, $request);

				/* Read */
				stream_set_blocking($sock, FALSE);

				$atStart = true;
				$IsHeader = true;
				$timeout = false;
				$start_time = time();
				while (! feof($sock) && ! $timeout) {
					$line = fgets($sock, 4096);
					$diff = time() - $start_time;
					if ($diff >= $timeoutread) {
						$timeout = true;
					}
					if ($IsHeader) {
						if ($line == "") // for stream_set_blocking
						{
							continue;
						}
						if (substr($line, 0, 2) == "\r\n") // end of header
						{
							$IsHeader = false;
							continue;
						}
						$headers .= $line;
						if ($atStart) {
							$atStart = false;
							if (! preg_match('/HTTP\/(\\d\\.\\d)\\s*(\\d+)\\s*(.*)/', $line, $m)) {
								$errormsg = "Status code line invalid: " . htmlentities($line);
								fclose($sock);
								return false;
							}
							$http_version = $m[1];
							$status = $m[2];
							$status_string = $m[3];
							continue;
						}
					} else {
						$body .= $line;
					}
				}

				fclose($sock);

				if ($timeout) {
					$errorcode = "10200";
					$errormsg = "Socket Timeout(" . $diff . "SEC)";
					$resultData =  false;
				}

				if (!json_decode($body)) {
					$resultData = json_decode($body, true);
				}
				$resultData = json_decode($body, true);
				// dd($resultData);
			} else {
				$resultData = false;
			}

			// Ensure $resultData['vacctInfoList'] is countable
			if (isset($resultData['vacctInfoList']) && is_array($resultData['vacctInfoList'])) {
				for ($i = 0; $i < count($resultData['vacctInfoList']); $i++) {
					$bankCD = $resultData['vacctInfoList'][$i]['bankCd'];
					$vacctNo = $resultData['vacctInfoList'][$i]['vacctNo'];

					switch ($bankCD) {
						case 'BMRI':
							$bankmandiri = $vacctNo;
							break;
						case 'BDIN':
							$bankdanamon = $vacctNo;
							break;
						case 'BNIA':
							$bankcimb = $vacctNo;
							break;
						case 'BNIN':
							$bankbni = $vacctNo;
							break;
						case 'IBBK':
							$bankbii = $vacctNo;
							break;
						case 'BRIN':
							$bankbri = $vacctNo;
							break;
						case 'CENA':
							$bankbca = $vacctNo;
							break;
						case 'HNBN':
							$bankhana = $vacctNo;
							break;
						case 'BBBA':
							$bankpermata = $vacctNo;
							break;

						default:
							$kosong = 'isi';
							break;
					}
				}
			} else {
				// Handle the case where $resultData['vacctInfoList'] is not an array
				// Log an error, throw an exception, or set a default value
				return false;
			}

			$queryinsertva = DB::table('fixedva')->insert([
				'id' => NULL,
				'clientid' => $userid,
				'bcava' => $bankbca ?? null,
				'mandiriva' => $bankmandiri ?? null,
				'bniva' => $bankbni ?? null,
				'briva' => $bankbri ?? null,
				'biiva' => $bankbii ?? null,
				'permatabankva' => $bankpermata ?? null,
				'hanabankva' => $bankhana ?? null,
				'danamonva' => $bankdanamon ?? null,
				'atmbersamava' => $bankhana ?? null,
				'cimbva' => $bankcimb ?? null
			]);
			return true;
		}
	}
}

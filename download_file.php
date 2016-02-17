<?php
session_start();
include_once 'deere_service.php';

if(isset($_GET['file_id']) && $_GET['file_id'] != '' && isset($_GET['file_name']) && $_GET['file_name'] != '' && isset($_GET['file_size']) && $_GET['file_size'] != '') {
		$file_id = $_GET['file_id'];
		$file_name = $_GET['file_name'];
		$file_size = $_GET['file_size'];
		$file_content = '';
		
		$file_size_kb  = sprintf('%0.2f', $file_size/1024);
		$file_download_size_kb_limit = 16 * 1024; // 16 MB
		$no_of_chunks = 0;
		
		$file_endpoint = $_SESSION['catalog_urls']['files'].'/'.$file_id;
		
		
		// Get the file content in chunks, if the file size is greater than 16 MB
		if($file_size_kb > $file_download_size_kb_limit) {
			if($file_size_kb%$file_download_size_kb_limit == 0) {
				$no_of_chunks = $file_size_kb/$file_download_size_kb_limit;
			}
			else {
				$no_of_chunks = floor($file_size_kb/$file_download_size_kb_limit) + 1;
			}
		}
		
		// Perform the file download in chunks of max 16 MB
		if($no_of_chunks > 0) {
			for($count = 1; $count <= $no_of_chunks; $count++) {
				$offset = ($count-1) * ($file_download_size_kb_limit * 1024);
				
				if($count == $no_of_chunks) {
					$size = $file_size - ($count-1) * ($file_download_size_kb_limit * 1024);
				}
				else {
					$size = $file_download_size_kb_limit * 1024;
				}
				
				$file_content .= download_file($file_endpoint, $deereService, $offset, $size);
			}
		}
		else {
			$file_content = download_file($file_endpoint, $deereService);
		}
		
	header('Content-Disposition: attachment; filename='.$file_name);
	header('Content-Type: application/x-zip-compressed;charset=UTF-8');
	header('Content-Length: ' . strlen($file_content));
	header('Connection: close');
	echo $file_content;
	exit;
}

function download_file($file_endpoint, $deereService, $offset = null, $size = null) {
	if($offset != '' && $size != '') {
		$file_endpoint = $file_endpoint.'?offset='.$offset.'&size='.$size;
	}
	//echo 'URL: '.$file_endpoint.' > '.$offset.' > '.$size.'<br><br>';
	
	// Prepare Authorization headers
	$authorization_headers = $deereService->getRequestAuthorizationHeaders($file_endpoint, 'GET', '', array("Accept" =>"application/octet-stream", "Content-Type"=>"application/vnd.deere.axiom.v3+xml"));
	
	// Make Curl request
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $file_endpoint);

// Check if we have to use a proxy server
  if(isset($_SESSION['config_params']['use_deere_proxy']) && $_SESSION['config_params']['use_deere_proxy'] == 1 && isset($_SESSION['config_params']['deere_proxy']) && $_SESSION['config_params']['deere_proxy'] != '') {
    curl_setopt($ch, CURLOPT_PROXY, $_SESSION['config_params']['deere_proxy']);
  }
	curl_setopt($ch, CURLOPT_VERBOSE, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	
	curl_setopt($ch, CURLOPT_AUTOREFERER, false);
	curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_AUTOREFERER, false);
	

	$headers = array();
	$headers[] = 'Content-Type: application/vnd.deere.axiom.v3+xml';
	$headers[] = 'Accept: application/octet-stream';
	$headers[] = 'Authorization: '.$authorization_headers;
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	
	$http_result = curl_exec($ch);
	$error = curl_error($ch);
	$http_code = curl_getinfo($ch ,CURLINFO_HTTP_CODE);
	curl_close($ch);
	
	// Check for return of full/partial file content
	if($error == '' && ($http_code == 200 || $http_code == 206)) {
		return $http_result;
	}
	else {
		die('Oops, an error occurred: '.$http_code.': '.$error);
	}
}
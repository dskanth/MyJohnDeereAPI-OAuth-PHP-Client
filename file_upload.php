<?php
session_start();
include_once 'deere_service.php';

if(!isset($_POST['user_orgn']) || $_POST['user_orgn'] == '') {
	$msg = '<span style="color:red; font-weight:bold;">No Organization was selected!</span>';
}
// Check if the file was uploaded
else if($_FILES['file']['name'] == '') {
	$msg = '<span style="color:red; font-weight:bold;">File was not uploaded!</span>';
}
// Check the file size for more than 32 MB
else if($_FILES['file']['size'] > 33554432) {
	$msg = '<span style="color:red; font-weight:bold;">The file size exceeds 32 MB!</span>';
}
// Check the file extension
else if($_FILES['file']['type'] != 'application/x-zip-compressed' && $_FILES['file']['type'] != 'application/zip') {
	$msg = '<span style="color:red; font-weight:bold;">The uploaded file was not a zip file!</span>';
}
else {
	$org_id = $_POST['user_orgn'];
	$org_name = '';
	
	// Move the uploaded file to "uploads" path
	$file_tmp_name = $_FILES['file']['tmp_name'];
	$file_name = $_FILES['file']['name'];
	$file_unique_name = time().'_'.$file_name;
	move_uploaded_file($file_tmp_name, 'uploads/'.$file_unique_name);
	
	// Fetch the Organization's File List URL
	$org_files_url = '';
	
	// Get the user's organization from session
	foreach($_SESSION['user_orgs'] as $k => $org) {
		if($org['id'] == $org_id) {
			$org_name = $org['name'];
			$org_links = $_SESSION['user_orgs'][$k]['links'];
			break;
		}
	}
	
	// Get the links for file list, for the selected organization
	foreach($org_links as $org_link) {
		if($org_link['rel'] == 'files') {
			$org_files_url = $org_link['uri'];
		}
	}
	
	if($org_files_url != '') {
		// Make a POST call to create a file id
		$file_details = array('name' => $file_name);
		
		$post_file_result = $deereService->requestResponseWithHeaders( $org_files_url, 'POST', json_encode($file_details), array("Accept" =>"application/vnd.deere.axiom.v3+json", "Content-Type"=>"application/vnd.deere.axiom.v3+json") );
		//print_r($post_file_result); exit;
		
		if(isset($post_file_result['response_headers']) && count($post_file_result['response_headers']) > 0) {
			$loc_hdr = getLocationHeader($post_file_result['response_headers']);
			
			$file_id = explode("files/", $loc_hdr);
			$file_id = end($file_id);
			
			// Make a CURL PUT call with file contents
			$put_file_endpoint = $_SESSION['catalog_urls']['files'].'/'.$file_id;
			
			$authorization_headers = $deereService->getRequestAuthorizationHeaders($put_file_endpoint, 'PUT', '', array("Accept" =>"application/vnd.deere.axiom.v3+json", "Content-Type"=>"application/zip"));
			
			$put_url = $_SESSION['catalog_urls']['files'].'/'.$file_id;
			$file_name_with_full_path = realpath('uploads/'.$file_unique_name);
			$fileStream = fopen($file_name_with_full_path, "rb");
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $put_url);
		  // Check if we have to use a proxy server
		  if(isset($_SESSION['config_params']['use_deere_proxy']) && $_SESSION['config_params']['use_deere_proxy'] == 1 && isset($_SESSION['config_params']['deere_proxy']) && $_SESSION['config_params']['deere_proxy'] != '') {
		    curl_setopt($ch, CURLOPT_PROXY, $_SESSION['config_params']['deere_proxy']);
		  }
			curl_setopt($ch, CURLOPT_PUT, 1);
			curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_VERBOSE, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			
			curl_setopt($ch, CURLOPT_INFILE, $fileStream);
			curl_setopt($ch, CURLOPT_INFILESIZE, filesize($file_name_with_full_path));

			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_AUTOREFERER, false);
			
		
			$headers = array();
			$headers[] = 'Content-Type: application/zip';
			$headers[] = 'Accept: application/vnd.deere.axiom.v3+json';
			$headers[] = 'Authorization: '.$authorization_headers;
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			$http_result = curl_exec($ch);
			$error = curl_error($ch);
			$http_code = curl_getinfo($ch ,CURLINFO_HTTP_CODE);
			curl_close($ch);
			
			if($error == '' && $http_code == 204) {
				$msg = '<span style="color:green; font-weight:bold;">File (ID: '.$file_id.') has been created successfully in the "'.$org_name.'" organization</span>';
				$_SESSION['uploaded_file_name'] = $file_name_with_full_path;
			}
			else {
				$msg = '<span style="color:red; font-weight:bold;">Oops, file could not  be uploaded. Please try later!</span>';
			}
		}
	}
}
$_SESSION['user_msg'] = $msg;
header("Location: create_file.php");
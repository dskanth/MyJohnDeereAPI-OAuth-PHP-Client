<?php

class OrgController extends BaseController {

	/*
	|--------------------------------------------------------------------------
	| Org Controller
	|--------------------------------------------------------------------------
	|
	| Here we check whether the user is authenticated with Deere and if yes, 
	| we parse the response. Else, we redirect the user to login page
	|
	*/
	
	// Return the list of files in an organization
	public function organizationFiles() {
		//return 'Files of org id: '.Route::input('id');
		
		$deere_user_session_id = Session::get('deere_user_id');
				
		if ($deere_user_session_id == '') {
			Session::put('deere_user_forward_to', Route::getCurrentRoute()->getPath()); return Redirect::to( 'user_oauth' );
		}
				
		$serviceFactory = new \OAuth\ServiceFactory();
		$tw = Artdarek\OAuth\Facade\OAuth::consumer( 'Deere' );
		
		$organization_files_endpoint = '/organizations/'.Route::input('id').'/files';
		$org_files_result = $tw->requestResponseWithHeaders( $organization_files_endpoint );
		
		if(trim($org_files_result['response']) == '') {
			$response_code = $org_files_result['response_headers'][0];
			if (stripos($response_code, '403') !== false) {
				return '<span style="color:red;">User is not authorized to view the files of this Organization.</span>';
			}
		}
		
		$org_files_result = json_decode( $org_files_result['response'], true );
		
		$org_file_count = 0;
		$org_files = '';
		
		if(isset($org_files_result['total']) && $org_files_result['total'] > 0) {
			$org_file_count = $org_files_result['total'];
		}
		if(isset($org_files_result['values']) && count($org_files_result['values']) > 0) {
			$org_files = $org_files_result['values'];
		}
		
		return View::make('organization_files', array('user_name' => $deere_user_session_id, 'org_name' => Route::input('name'), 'org_file_count' => $org_file_count, 'org_files' => $org_files));
	}
	
	// Download a file of an organization
	public function fileDownload() {
		//return 'File to be downloaded: '.Route::input('id');
		
		$deere_user_session_id = Session::get('deere_user_id');
				
		if ($deere_user_session_id == '') {
			Session::put('deere_user_forward_to', Route::getCurrentRoute()->getPath()); return Redirect::to( 'user_oauth' );
		}
				
		$serviceFactory = new \OAuth\ServiceFactory();
		$tw = Artdarek\OAuth\Facade\OAuth::consumer( 'Deere' );
		
		$file_view_endpoint = '/files/'.Route::input('id');
		
		$file_metadata_result = json_decode($tw->request( $file_view_endpoint, 'GET', '', array() ), true);
		//print_r($file_metadata_result); exit;
		
		$file_data_result = $tw->request( $file_view_endpoint, 'GET', '', array( "Accept" =>"application/octet-stream" ) );
		//return $file_data_result;
		//exit;
		
		header('Content-Disposition: attachment; filename='.$file_metadata_result['name']);
		header('Content-Type: application/x-zip-compressed;charset=UTF-8');
		header('Content-Length: ' . strlen($file_data_result));
		header('Connection: close');
		return $file_data_result;
		exit;
	}
	
	// Fetch and return the Location header from the response passed
	public function getLocationHeader($response_headers) {
		$loc_header = '';
			foreach($response_headers as $resp_header) {
				if (stripos($resp_header, 'Location:') !== false) {
					$loc_header = $resp_header;
					break;
				}
			}
			return $loc_header;
	}
	
	// Function to display form to upload a file
	public function uploadFile() {
		$deere_user_session_id = Session::get('deere_user_id');
				
		if ($deere_user_session_id == '') {
			Session::put('deere_user_forward_to', Route::getCurrentRoute()->getPath()); return Redirect::to( 'user_oauth' );
		}
		
		$serviceFactory = new \OAuth\ServiceFactory();
		$tw = Artdarek\OAuth\Facade\OAuth::consumer( 'Deere' );
		
		$user_organizations_endpoint = '/organizations;count=100;';
		
		$org_result = json_decode( $tw->request( $user_organizations_endpoint ), true );
		$user_orgs = array();
		if(isset($org_result['values']) && count($org_result['values']) > 0) {
			$user_orgs = $org_result['values'];
		}
		//print_r($user_orgs); exit;
		return View::make('upload', array('user_name' => Session::get('deere_user_id'), 'deere_user_orgs' => $user_orgs));
	}
	
	// Function to process the upload of file
	public function processUploadedFile() {
		$deere_user_session_id = Session::get('deere_user_id');
				
		if ($deere_user_session_id == '') {
			Session::put('deere_user_forward_to', Route::getCurrentRoute()->getPath()); return Redirect::to( 'user_oauth' );
		}
		
		if(!isset($_POST['user_orgn']) || $_POST['user_orgn'] == '') {
			$msg = '<span style="color:red; font-weight:bold;">No Organization was selected!</span>';
		}
		
		// Check if the file was uploaded
		else if(Input::file('file') == '') {
			$msg = '<span style="color:red; font-weight:bold;">File was not uploaded!</span>';
		}
		// Check the file size
		else if(Input::file('file')->getClientSize() > 10000000) {
			$msg = '<span style="color:red; font-weight:bold;">The file size exceeds 10 MB!</span>';
		}
		// Check the file extension
		else if(Input::file('file')->getClientMimeType() != 'application/x-zip-compressed') {
			$msg = '<span style="color:red; font-weight:bold;">The uploaded file is not a zip file!</span>';
		}
		else {
			// Move the uploaded file to "uploads" path
			$file_unique_name = time().'_'.Input::file('file')->getClientOriginalName();
			Input::file('file')->move(base_path().'/uploads', $file_unique_name);
			$file_name = Input::file('file')->getClientOriginalName();
			
			$serviceFactory = new \OAuth\ServiceFactory();
			$tw = Artdarek\OAuth\Facade\OAuth::consumer( 'Deere' );
		
			// Make POST call to create a file id
			$org_id = $_POST['user_orgn']; //$org_result['values'][0]['id'];
			$file_details = array('name' => $file_name);
			$post_file_endpoint = '/organizations/'.$org_id.'/files';
		
			$post_file_result = $tw->requestResponseWithHeaders( $post_file_endpoint, 'POST', json_encode($file_details), array("Accept" =>"application/vnd.deere.axiom.v3+json", "Content-Type"=>"application/vnd.deere.axiom.v3+json") );
			//print_r($post_file_result); exit;
			
			if(isset($post_file_result['response_headers']) && count($post_file_result['response_headers']) > 0) {
				$loc_hdr = $this->getLocationHeader($post_file_result['response_headers']);
				$file_id = explode("files/", $loc_hdr);
				$file_id = end($file_id);
				
				$put_file_endpoint = '/files/'.$file_id;
				
				$authorization_headers = $tw->getRequestAuthorizationHeaders($put_file_endpoint, 'PUT', '', array("Accept" =>"application/vnd.deere.axiom.v3+json", "Content-Type"=>"application/zip"));
				
				$url = "https://api.deere.com/platform/files/".$file_id;
				$file_name_with_full_path = realpath(base_path().'/uploads/'.$file_unique_name);
				$fileStream = fopen($file_name_with_full_path, "rb");
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_PUT, 1);
				curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_VERBOSE, 1);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_INFILE, $fileStream);
				curl_setopt($ch, CURLOPT_INFILESIZE, filesize($file_name_with_full_path));

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
					$msg = '<span style="color:green; font-weight:bold;">File (ID: '.$file_id.') has been created successfully in the organization</span>';
				}
				else {
					$msg = '<span style="color:red; font-weight:bold;">Oops, file could not  be uploaded. Please try later!</span>';
				}
			}
		}
		Session::flash('upload_msg', $msg);
		return Redirect::to( 'create_file' );
	}
	
	// Display the user orgs and files
	public function listFiles() {
		$deere_user_session_id = Session::get('deere_user_id');
		
		if ($deere_user_session_id == '') {
			Session::put('deere_user_forward_to', Route::getCurrentRoute()->getPath());
			return Redirect::to( 'user_oauth' );
		}
		
		$serviceFactory = new \OAuth\ServiceFactory();
		$tw = Artdarek\OAuth\Facade\OAuth::consumer( 'Deere' );
		
		$user_organizations_endpoint = '/organizations;count=100;';
		
		$org_result = json_decode( $tw->request( $user_organizations_endpoint ), true );
		$user_orgs = array();
		if(isset($org_result['values']) && count($org_result['values']) > 0) {
			$user_orgs = $org_result['values'];
		}
		//print_r($user_orgs); exit;
		
		return View::make('organizations', array('user_name' => Session::get('deere_user_id'), 'deere_user_orgs' => $user_orgs));
	}
}


<?php
session_start();
include_once 'deere_service.php';
?>
<style type="text/css">
#file_details { float: left; margin-left: 75px; }
#org_files_div { float: left; min-width: 200px; padding: 10px 20px; margin: 5px 10px; border: 1px solid #ccc; }
#org_files_div table td { color: green; font-size: 18px; }
#file_links { margin-top:10px; text-align: center; }
</style>
<?php
if(isset($_GET['org_id']) && $_GET['org_id'] != '') {
	$org_id = $_GET['org_id'];
	
	// Fetch the Organization's File List URL
	$org_files_url = isset($_GET['uri'])?$_GET['uri']:'';
	
	// Get the user's organization from session
	foreach($_SESSION['user_orgs'] as $k => $org) {
		if($org['id'] == $org_id) {
			$org_name = $org['name'];
			$org_links = $_SESSION['user_orgs'][$k]['links'];
			break;
		}
	}
	
	if($org_files_url == '') {
		// Get the links for file list, for the selected organization
		foreach($org_links as $org_link) {
			if($org_link['rel'] == 'files') {
				$org_files_url = $org_link['uri'];
			}
		}
	}
	
	$org_file_count = $org_files = '';
	
	if($org_files_url != '') {
		$org_files_result = $deereService->requestResponseWithHeaders( $org_files_url );
		
		if(trim($org_files_result['response']) == '') {
			$response_code = $org_files_result['response_headers'][0];
			if (stripos($response_code, '403') !== false) {
				echo '<span style="color:red;">User is not authorized to view the files of this Organization.</span>';
			}
		}
		else {
			$org_files_result = json_decode( $org_files_result['response'], true );
			
			$org_file_count = 0;
			$org_files = '';
			
			if(isset($org_files_result['total']) && $org_files_result['total'] > 0) {
				$org_file_count = $org_files_result['total'];
			}
			if(isset($org_files_result['values']) && count($org_files_result['values']) > 0) {
				$org_files = $org_files_result['values'];
			}
		}
	}
	
	echo 'Files found in <b><i>"'.$org_name.'"</i></b> Organization: <b>'.$org_file_count.'</b><br><br>';
	
	if($org_file_count > 0) {
		echo '<div id="file_details">';
		
		foreach($org_files as $file) {
			echo '<div id="org_files_div" id="file_'.$file['id'].'">';
			echo '<table><tr><td>ID:</td><td>'.$file['id'].'</td></tr>';
			echo '<tr><td>Name:</td><td>'.$file['name'].'</td></tr>';
			echo '<tr><td>Type:</td><td>'.$file['type'].'</td></tr>';
			echo '<tr><td>Size:</td><td>'.sprintf('%0.2f', ($file['nativeSize'] / 1024)).' KB</td></tr>';
			
			// Display Download link only for valid files
			if($file['type'] != 'INVALID' && $file['type'] != 'UNKNOWN') {
				echo '<tr><td>&nbsp;</td><td><a href="download_file.php?file_id='.$file['id'].'&file_name='.$file['name'].'&file_size='.$file['nativeSize'].'" target="_blank">Download</a></td></tr>';
			}
			else {
				echo '<tr><td colspan="2">&nbsp;</td></tr>';
			}
			echo '</table></div>';
		}
		echo '</div>';
		
		// Display the links for next page or previous pages
		echo '<br><div id="file_links">';
		foreach($org_files_result['links'] as $links) {
			if($links['rel'] == 'previousPage') {
				echo '<a href="view_org_files.php?uri='.$links['uri'].'&org_id='.$org_id.'">Previous Page</a>&nbsp;&nbsp;&nbsp;';
			} else if($links['rel'] == 'nextPage') {
				echo '<a href="view_org_files.php?uri='.$links['uri'].'&org_id='.$org_id.'">Next Page</a>&nbsp;&nbsp;&nbsp;';
			}
		}
		echo '<div>';
	}
}
<?php
// Uploads a file to an organization
// @author Gordon Wang <WangGordon@JohnDeere.com>
// @author Dinesh Vishwakarma <VishwakarmaDinesh@JohnDeere.com>
// @date July 2016
require "Header.php";

$oauth = new ProxyAwareOAuth($settings["App_Key"], $settings["App_Secret"], $settings["Proxy"], $settings["ProxyAuth"]);
loadToken($oauth);

// Get current user
$response = json_decode($oauth->get($settings["MyJohnDeere_API_URL"], $headers));
$currentUserUri = getURL($response, "currentUser");

// Get organizations available to current user
$response = json_decode($oauth->get($currentUserUri, $headers));
$organizationsUri = getURL($response, "organizations");

// Page through all organizations
$organizations = [];
while($organizationsUri)
{
	$response = json_decode($oauth->get($organizationsUri, $headers));
	$organizationsUri = getURL($response, "nextPage");

	foreach($response->values as $organization)
	{
		if(($link = getURL($organization, "files")))
			$organizations[$organization->name] = $link;
	}
}
?>

<div class="page-title">Upload a file</div>
<?php
// Upload files if all form inputs set
if(!empty($_POST["orgLink"]) && !empty($_FILES["fileUpload"]["name"]) && isset($_POST["upload"]))
{
	// POST to the organization's /files URL and get a file ID
	$body = '{ "name" : "'.$_FILES["fileUpload"]["name"].'"}'; // The JSON POST body
	$headers["Content-Type"] = "application/vnd.deere.axiom.v3+json";
 	$response = $oauth->post($_POST["orgLink"], $body, $headers);

 	// Get the returned file URL, get contents of file
 	$fileLocation = getHeader($response, "Location");
 	$contents = file_get_contents($_FILES["fileUpload"]["tmp_name"]);

 	// PUT the file data to the file URL
 	$headers["Content-Type"] = "application/zip";
 	$oauth->put($fileLocation, $contents, $headers);
 	
 	echo "<div id='file-upload-complete'>Uploaded ".$_FILES["fileUpload"]["name"]."</div>";
}
else if(isset($_POST["upload"]))
	echo "<div id='incomplete-form-submission'>Incomplete submission.</div>";
?>

<form action="UploadFile.php" method="post" enctype="multipart/form-data">
	<table id="file-upload-options">
		<tr>
			<td id="organization-selection-container">
				Select an organization to receive file.<br>
				<?php
				// Echo radio button choices for organizations
				foreach($organizations as $key=>$value)
					echo "<input type='radio' name='orgLink' value='$value'> $key<br>";
				?>
			</td>
			<td style="width:1%"></td>
			<td id="file-upload-container" valign="top">
				Select a file to upload.<br>
				Only valid ZIP archives and PDFs will upload properly.<br>
				<input type="file" name="fileUpload"><br><br>
				<button type="submit" name="upload">Upload</button>
			</td>
		</tr>
	</table>
</form>

<div class="footer"></div>
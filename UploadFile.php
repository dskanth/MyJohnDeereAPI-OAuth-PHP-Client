<?php
// Uploads a file to an organization
// @author Gordon Wang <WangGordon@JohnDeere.com>
// @author Dinesh Vishwakarma <VishwakarmaDinesh@JohnDeere.com>
// @date July 2016
require "Header.php";

// Gets a file's link from a response header
// Isolates the "Location" header
// @param $response -- HTML response header to parse
// @return file link, false if not found
function getFileLocation($response)
{
	$response = explode("\n", $response);
 	foreach($response as $header)
 	{
 		if(strpos($header, "Location") !== false)
 		{
 			$link = explode(" ", $header)[1];
 			return trim($link);
 	}	}
 	return false;
}

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

<div class="border">
	<div class="file-upload-title">Upload File</div>
	<form class="container" action="UploadFile.php" method="post" <?php echo isset($_POST["orgSubmit"]) || isset($_POST["upload"]) ? "hidden" : ""; ?>>
	Select an organization to receive file.<br>
	<?php
	// Echo radio button choices for organizations
	foreach($organizations as $key=>$value)
		echo "<input type='radio' name='orgSelection' value='$value'> $key<br>";
	?>
	<button type="submit" name="orgSubmit" value="organizationSubmitted">Submit</button>
	</form>

	<form class="container" action="UploadFile.php" method="post" enctype="multipart/form-data" <?php echo isset($_POST["orgSubmit"]) ? "" : "hidden"; ?>>
		Select a file to upload.<br>
		Only valid ZIP archives and PDFs will upload properly.<br>
		<input type="text" name="fileLink" value="<?php echo $_POST["orgSelection"] ?? ""; ?>" hidden>
		<input type="file" name="fileUpload"><br><br>
		<button type="submit" name="upload">Upload</button>
	</form>
<?php
if(isset($_POST["upload"]))
{
	// POST to the organization's /files URL and get a file ID
	$body = '{ "name" : "'.$_FILES["fileUpload"]["name"].'"}'; // The JSON POST body
	$headers["Content-Type"] = "application/vnd.deere.axiom.v3+json";
 	$response = $oauth->post($_POST["fileLink"], $body, $headers);
 	
 	// Get the returned file URL, get contents of file
 	$fileLocation = getFileLocation($response);
 	$contents = file_get_contents($_FILES["fileUpload"]["tmp_name"]);

 	// PUT the file data to the file URL
 	$headers["Content-Type"] = "application/zip";
 	$oauth->put($fileLocation, $contents, $headers);
 	
 	echo "
 	<div id='upload-success'>
 		Uploaded ".$_FILES["fileUpload"]["name"]."<br>
 		<button onclick='".'window.location.href="index.php"'."'>Home</button>
 	</div>";
}
?>
</div>

<div class="footer"></div>
<!DOCTYPE html>
<meta charset="UTF-8">
<title>MyJohnDeere API</title>
<link rel="stylesheet" type="text/css" href="include/style.css">
<link rel="shortcut icon" href="include/favicon.ico">
<script src="include/jquery-3.0.0.js" type="text/javascript"></script>

<?php
// Determine if a tab item should be highlighted
function boldLinks(string $link)
{
	echo strpos($_SERVER["REQUEST_URI"], $link) === FALSE ? "" : "style='color:#000000;border-bottom:3px solid #367c2b;padding-bottom:6px'";
}
?>
<table class="content">
<tr>
	<td id="myjd-header">
		<a href="index.php">MyJohnDeere API</a>
		<div id="myjd-subheader">PHP Sample App</div>
	</td>
	<td>
		<img id="deere-logo" src="include/deereLogo.png" align="right">
	</td>
</tr>
<tr>
	<td colspan="2" id="separator-green">&nbsp;</td>
</tr>
<tr>
	<td colspan="2" id="separator-yellow">&nbsp;</td>
</tr>
</table>
<div id='tab-header'>
	<span class="header-item-left header-item"><a class="header-link" href="index.php" <?php boldLinks("index.php"); ?>>OAuth</a></span>
	<span class="header-item"><a class="header-link" href="UploadFile.php" <?php boldLinks("UploadFile.php"); ?>>Upload File</a></span>
	<span class="header-item"><a class="header-link" href="ListFiles.php" <?php boldLinks("ListFiles.php"); ?>>List Files</a></span>
	<span class="header-item"><a class="header-link" href="ListMonitor.php" <?php boldLinks("ListMonitor.php"); ?>>File List Monitor</a></span>
</div>

<?php
require_once "APICredentials.php";
require_once "ProxyAwareOAuth.php";

// The default accept headers used by all OAuth requests
$headers = ["Accept" => "application/vnd.deere.axiom.v3+json"];

// Exception handler
// Redirects to the error page, where the exception message and trace are printed
// @param $e -- the thrown exception
function exception_handler(Throwable $e)
{
	$message = urlencode($e->getMessage());
	$location = urlencode("<b>".$e->getFile()."</b> line <b>".$e->getLine()."</b>");
	$trace = urlencode(nl2br($e->getTraceAsString()));

	echo "
	<form name='exception' action='error.php' method='post' hidden>
		<input name='message' value='$message'>
		<input name='location' value='$location'>
		<input name='trace' value='$trace'>
		<input type='submit'>
	</form>
	<script> document.exception.submit(); </script>";
}
set_exception_handler("exception_handler");

// Saves settings to APICredentials.php
function saveSettings()
{
	global $settings;
	$fd = fopen("APICredentials.php", "w");
	$data = '
<?php

$settings =
[
	"MyJohnDeere_API_URL" => "'.$settings["MyJohnDeere_API_URL"].'",
	"App_Key" => "'.$settings["App_Key"].'",
	"App_Secret" => "'.$settings["App_Secret"].'",
	"Proxy" => "'.$settings["Proxy"].'",
	"ProxyAuth" => "'.$settings["ProxyAuth"].'"
];

?>';
	fwrite($fd, $data);
	fclose($fd);
}

// Displays an error message and stops scripts
function noTokenFound()
{
	echo "
	<div id='error-border'>
		<div class='error'>
			No valid token found.<br>
			<button onclick='".'window.location.href="index.php"'."'>Home</button>
		</div>
	</div>
	";
	exit(-1);
}

// Loads an OAuth token from file
// @param $oauth -- reference to OAuth object
function loadToken(&$oauth)
{
	$fd = file_exists("savedToken.txt") ? fopen("savedToken.txt", "r") : false;
	if($fd == false)
		noTokenFound();

	fgets($fd);
	$savedToken = trim(fgets($fd));
	$savedSecret = trim(fgets($fd));
	fclose($fd);

	if(empty($savedToken) || empty($savedSecret))
		noTokenFound();

	$oauth->setToken($savedToken, $savedSecret);
}

// Grabs a URL from a response
// @param $response -- decoded json response
// @param $rel -- the URL to grab
// @return URL, false if not found
function getURL($response, $rel)
{
	if(!isset($response->links))
		return false;

	foreach($response->links as $link)
	{
		if($link->rel == $rel)
			return $link->uri;
	}
	return false;
}

// Parses an HTML header
// @param $response -- HTML response header to parse
// @return file link, false if not found
function getHeader($response, $target)
{
	$response = explode("\n", $response);
 	foreach($response as $header)
 	{
 		if(strpos($header, $target) !== false)
 		{
 			$link = explode(" ", $header)[1];
 			return trim($link);
		}
	}
 	return false;
}
?>
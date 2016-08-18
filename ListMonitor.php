<?php
// Monitor differences in file lists between two calls
// @author Gordon Wang <WangGordon@JohnDeere.com>
// @date August 2016

require "Header.php";

$lastCallTime = "never";
$deereEtag = [];
$fileChanges = [];

// Load any saved data
if(file_exists("ListMonitorInfo"))
{
	$fd = fopen("ListMonitorInfo", "r");
	$lastCallTime = fgets($fd);
	$deereEtag = unserialize(fgets($fd));
	fclose($fd);
}
// After the "Check" button is pressed
if(isset($_POST["check"]))
{
	$oauth = new ProxyAwareOAuth($settings["App_Key"], $settings["App_Secret"], $settings["Proxy"], $settings["ProxyAuth"]);
	loadToken($oauth);

	// Get current user
	$response = json_decode($oauth->get($settings["MyJohnDeere_API_URL"], $headers));
	$currentUserUri = getURL($response, "currentUser");

	// Get organizations of current user
	$response = json_decode($oauth->get($currentUserUri, $headers));
	$organizationsUri = getURL($response, "organizations");

	// Get links to files for all available organizations
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

	// Call all file links with saved tags, update tags
	foreach($organizations as $name=>$link)
	{
		$headers["X-Deere-Signature"] = isset($deereEtag[$link]) ? $deereEtag[$link] : "nil";
		$response = $oauth->get($link, $headers, true);
		$tag = getHeader($response["header"], "x-deere-signature");
		$deereEtag[$link] = $tag ? $tag : $deereEtag[$link];
		$fileChanges[$name] = $response["body"];
	}

	// Update the save file for tags
	$lastCallTime = date("H:i:s eO d M Y");
	$fd = fopen("ListMonitorInfo", "w");
	fwrite($fd, $lastCallTime.PHP_EOL);
	fwrite($fd, serialize($deereEtag));
	fclose($fd);

	echo "<div class='downloadSuccess'>Successfully checked file list at $lastCallTime</div>";
}
?>

<div class="page-title">File List Monitor</div>
<div class="organization-files">
	This page monitors differences in an organization's files between two calls.<br>
	Last call made: <?php echo $lastCallTime; ?><br>

	<form action="ListMonitor.php" method="post">
		<button name="check" value="check">Check</button>
	</form>
</div>


<?php
// Output file changes
if(isset($_POST["check"]) && !empty($deereEtag))
{
	echo "
	<div class='organization-files'>
		<div id='changes-header'>Changes</div>";

	foreach($fileChanges as $name=>$response)
	{
		echo "<div>$name</div>";
		if($response == "")
		{
			echo "No changes for this organization.<br><br>";
			continue;
		}

		$files = json_decode($response);
		foreach($files->values as $change)
		{
			if(getURL($change, "delete"))
				echo "<div class='deleted-file'>Deleted file with ID $change->id.</div>";
			else
				echo "<div class='new-file'>New file $change->name ($change->id).</div>";
		}
		echo "<br><br>";
	}
	echo "</div>";
}
?>
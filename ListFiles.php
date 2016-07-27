<?php
// List files available to the current user
// Finds all files in all organizations
// @author Gordon Wang <WangGordon@JohnDeere.com>
// @author Pravin Taralkar <TaralkarPravin@JohnDeere.com>
// @date July 2016
require "Header.php";

$oauth = new ProxyAwareOAuth($settings["App_Key"], $settings["App_Secret"], $settings["Proxy"], $settings["ProxyAuth"]);
loadToken($oauth);

// Downloads a file in 10 KB chunks
// @param $name -- the URL-encoded name of the file
// @param $link -- the file's download link
// @param $size -- the size of the file in KB
function downloadFile(string $name, string $link, float $size)
{
	// Make the download directory
	if(!file_exists('downloads'))
		mkdir('downloads');

	global $oauth;
	$name = urldecode($name);
	$downloadHeaders = ["Accept" => "application/octet-stream"];
	$fd = fopen('downloads/'.$name, "w");

	// Download the file in chunks of 10 KB
	for($i = 0; $i < $size; $i+=10)
	{
		// If less than 10 KB remaining, set an appropriate range
		$end = ($i + 10)*1000 > $size ? $size*1000-1 : ($i + 10)*1000;
		$downloadHeaders["Range"] = "bytes=".($i*1000)."-".$end;

		$oauth->get($link, $downloadHeaders);
		fwrite($fd, $oauth->get($link, $downloadHeaders));
	}

	fclose($fd);
	echo "<div class='downloadSuccess'>Downloaded <a href='downloads/$name' download>$name</a></div>";
}

if(!empty($_POST["downloadLink"]))
	downloadFile($_POST["fileName"], $_POST["downloadLink"], floatval(substr($_POST["fileSize"], 0, -3)));
?>

<div class="border" style="width:80%">
<div id="title">Available Files</div>
<table class="file-table">
<?php
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

// Go through all organizations and get files
foreach($organizations as $name => $link)
{
	echo "<div class='container'><b>$name</b></div>";

	// Call to organization's /files, get a nextPage link if available
	$response = json_decode($oauth->get($link, $headers));
	$next = getURL($response, "nextPage");
	$fileList = $response->values;

	// Go through all next pages, appending new data to $fileList
	while($next)
	{
		$response = json_decode($oauth->get($next, $headers));
		$next = getURL($response, "nextPage");
		$fileList = array_merge($fileList, $response->values);
	}

	// Convert $fileList array to an HTML table; the format is:
	// ------------------------------------------------------------
	// | 0 - ID   | 1 - File 1 ID | 2 - File 2 ID | 3 - File 3 ID | Row 0
	// ------------------------------------------------------------
	// | 4 - Name | 5 - F1 Name   | 6 - F2 Name   |	7 - F3 Name   | Row 1
	// ------------------------------------------------------------
	// | 8 - Size | 9 - F1 Size   | 10 - F2 Size  | 11 - F3 Size  | Row 2
	// ------------------------------------------------------------
	// | 12 empty | 13 - F1 Link  | 14 - F2 Link  | 15 - F3 Link  | Row 3
	// ------------------ repeated for all files in organization...
	// The calculations below fit $fileList's files into the above indices
	$rows = -1;
	$files = [];
	for($i = 0; $i < count($fileList); $i++)
	{
		if($i % 3 == 0)
			$rows++;

		$files[$i%3+12*$rows] = $fileList[$i]->id;
		$files[$i%3 + 12*$rows+3] = $fileList[$i]->name;
		$files[$i%3 + 12*$rows+6] = floatval($fileList[$i]->nativeSize)/1000.00. " KB";
		$files[$i%3 + 12*$rows+9] = getURL($fileList[$i], "self");
	}

	// Generate and print the HTML table
	$rowTitles = [0 => "ID", 1 => "Name", 2 => "Size", 3 => ""];
	for($i = 0; $i < ($rows+1)*12; $i++)
	{
		if(!isset($files[$i]))
			continue;

		if($i % 3 == 0 && $rowTitles[($i/3)%4] == "")
			echo "<tr><td class='download-row-title'>".$rowTitles[($i/3)%4]."</td>";
		else if($i % 3 == 0)
			echo "<tr><td class='row-title'>".$rowTitles[($i/3)%4]."</td>";

		if(filter_var($files[$i], FILTER_VALIDATE_URL))
			echo "
			<td class='download-button-cell'>
				<form action='ListFiles.php' method='post'>
					<input name='fileName' value='".urlencode($files[$i-6])."' hidden>
					<input name='fileSize' value='".$files[$i-3]."' hidden>
					<input name='downloadLink' value='$files[$i]' hidden>
					<button type='submit'>Download</button>
				</form>
			</td>";
		else
			echo "<td class='cell'>$files[$i]</td>";

		if($i % 3 == 2)
			echo "</tr>";
	}
}
?>
</table>
</div>

<div class="footer">&nbsp;</div>

<?php
// List files available to the current user
// Finds all files in all organizations
// @author Gordon Wang <WangGordon@JohnDeere.com>
// @author Pravin Taralkar <TaralkarPravin@JohnDeere.com>
// @author Tony Gil <tonygil@agrigis.com.br> 
// @date August 2018

require "Header.php";

$oauth = new ProxyAwareOAuth($settings["App_Key"], $settings["App_Secret"], $settings["Proxy"], $settings["ProxyAuth"]);
loadToken($oauth);

// Downloads a file in 10 KB chunks
// @param $name -- the URL-encoded name of the file
// @param $link -- the file's download link
// @param $size -- the size of the file in KB


if(isset($_POST['field-option']))
{
	if($_POST['field-option'] == "download") {
		downloadFile($_POST["fieldName"], $_POST["downloadLink"], floatval(substr($_POST["fieldSize"], 0, -3)));
	} else if($_POST['field-option'] == "delete") {
		deleteFile($_POST["fieldName"], $_POST["downloadLink"]);
	} else if($_POST['field-option'] == "operations") {
		debug_to_console("view Operations CLICKED");
		viewOperations($_POST["fieldName"], $_POST["organizationId"], $_POST["fieldId"], $_POST["downloadLink"]);
	} else if($_POST['field-option'] == "view") {
		debug_to_console("view Image CLICKED");
		//viewFile($_POST["fileName"], $_POST["downloadLink"]);
	}
}

debug_to_console("link [".$_POST['link']."]  fieldName [".$_POST['fieldName']."]");



?>

<div class="page-title">Field Operation</div>
<?php
// Get current user
$response = json_decode($oauth->get($settings["MyJohnDeere_API_URL"], $headers));
$currentUserUri = getURL($response, "currentUser");

// Get organizations of current user
$response = json_decode($oauth->get($currentUserUri, $headers));
$organizationsUri = getURL($response, "organizations");

// Get links to files for all available organizations
$fields = [];
while($organizationsUri)
{
	
	debug_to_console("Organization [ ".$organizationsUri." ]");
	$response = json_decode($oauth->get($organizationsUri, $headers));
	$json = json_encode($response);
	debug_to_console("response [".$json."]");
	$organizationsUri = getURL($response, "nextPage");

	foreach($response->values as $organization)
	{
		if(($link = getURL($organization, "fields"))) {
			$fields[$organization->name] = $link;
			$organizationId[$organization->name] = $organization->id;
		}
	}
}

// Go through all organizations and get files
foreach($fields as $name => $link)
{
	echo "<div class='organization-files'><div class='organization-header'>$name</div>";

	// Call to organization's /files, get a nextPage link if available
	$response = json_decode($oauth->get($link, $headers));
	$next = getURL($response, "nextPage");
	debug_to_console("nextPageURL [$next]");
	$fieldList = $response->values;

	// Go through all next pages, appending new data to $fieldList
	while($next)
	{
		$response = json_decode($oauth->get($next, $headers));
		$next = getURL($response, "nextPage");
		$fieldList = array_merge($fieldList, $response->values);
	}

	// Convert $fieldList array to an HTML table; the format is:
	// ------------------------------------------------------------
	// | 0 - ID   | 1 - File 1 ID | 2 - File 2 ID | 3 - File 3 ID | Row 0
	// ------------------------------------------------------------
	// | 4 - Name | 5 - F1 Name   | 6 - F2 Name   |	7 - F3 Name   | Row 1
	// ------------------------------------------------------------
	// | 8 - Size | 9 - F1 Size   | 10 - F2 Size  | 11 - F3 Size  | Row 2
	// ------------------------------------------------------------
	// | 12 empty | 13 - F1 Link  | 14 - F2 Link  | 15 - F3 Link  | Row 3
	// ------------------ repeated for all files in organization...
	// The calculations below fit $fieldList's files into the above indices
	$rows = -1;
	$files = [];
	for($i = 0; $i < count($fieldList); $i++)
	{

		$linkField[$i] = getURL($fieldList[$i], "self");
		$fieldName[$i] = $fieldList[$i]->name;
		$fieldId[$i] = $fieldList[$i]->id;
		$fieldSize = floatval($fieldList[$i]->nativeSize)/1000.00. " KB";
		echo "<table><tr>";
		echo "<td>$fieldName[$i]</br>$fieldId[$i]</td>";
		echo "<td></td>";
		echo "
			<td class='download-button-cell'>
				<form action='ListFields.php' method='post'>
					<input name='fieldName' value='".urlencode($fieldName[$i])."' hidden>
					<input name='fieldId' value='".$fieldId[$i]."' hidden>
					<input name='organizationId' value='".urlencode($organizationId[$name])."' hidden>
					<input name='fieldSize' value='".$fieldSize."' hidden>
					<input name='downloadLink' value='$linkField[$i]' hidden>
					<button type='submit' name='field-option' value='operations'>Operations</button>
					<button type='submit' name='field-option' value='view'>View</button>
				</form>
			</td>";
		echo "<td></td>";
		echo "</tr></table>";
		$linkFieldOperations = $linkField[$i]."/fieldOperations";
		debug_to_console("LINK FIELD OPERATIONS [$linkFieldOperations]");
		$responseNew = json_decode($oauth->get($linkFieldOperations, $headers));

		$jsonNew = json_encode($responseNew);
		$fieldOperations = $responseNew->values;
		$jsonFieldOperationsResponse = json_encode($fieldOperations);
		//debug_to_console("$jsonNew");
		debug_to_console("Operations response:");
		debug_to_console($jsonFieldOperationsResponse);
		//foreach ($fieldOperations as $key => $value) {
		foreach ($fieldOperations as $value) {
			//debug_to_console("key[$key] value[$value]");
			$safra = $value->cropSeason;
			$operation = $value->fieldOperationType;
			$machine = $value->adaptMachineType;
			$startDate = $value->startDate;
			$endDate = $value->endDate;
			$tmpJsonDebug = json_encode($value);

			debug_to_console("value: safra: $safra   operation: $operation   machine: $machine   startDate: $startDate   endDate: $endDate ");
			debug_to_console("valueJSON: $tmpJsonDebug");
		}	
		debug_to_console("Left key value loop ");
	}



	// Generate and print the HTML table
	$rowTitles = [0 => "ID", 1 => "Name", 2 => "Size", 3 => ""];
	echo "<table class='file-table'>";
	for($i = 0; $i < ($rows+1)*12; $i++)
	{
		if(!isset($files[$i]))
			continue;

		if($i % 3 == 0 && $rowTitles[($i/3)%4] == "") {
			echo "<tr><td class='download-row-title'>".$rowTitles[($i/3)%4]."</td>";
		} else if($i % 3 == 0) {
			echo "<tr><td class='row-title'>".$rowTitles[($i/3)%4]."</td>";
			if ($rowTitles[($i/3)%4] == "ID") {
				//debug_to_console("Found ID");
				$flagId = 1;
			} else {
				$flagId = 0;
			}
		}

		if(filter_var($files[$i], FILTER_VALIDATE_URL)) {
			echo "
			<td class='download-button-cell'>
				<form action='ListFields.php' method='post'>
					<input name='fieldName' value='".urlencode($files[$i-6])."' hidden>
					<input name='fieldSize' value='".$files[$i-3]."' hidden>
					<input name='downloadLink' value='$files[$i]' hidden>
					<button type='submit' name='field-option' value='download'>Download</button>
					<button type='submit' name='field-option' value='view'>View</button>
				</form>
			</td>";
		} else {
			echo "<td class='cell'>$files[$i]</td>";
			if ($flagId) {
				debug_to_console($files[$i]);
			}
		}

		if($i % 3 == 2) {
			echo "</tr>";
		}
	}
	echo "</table></div>";
}
?>

<div class="footer"></div>

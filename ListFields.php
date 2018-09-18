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

?>
<section>
<nav>
<div class="page-title">Fields</div>
<?php
// Get current user
$response = json_decode(($oauth->get($settings["MyJohnDeere_API_URL"], $headers)));
$currentUserUri = getURL($response, "currentUser");

// Get organizations of current user
$response = json_decode(($oauth->get($currentUserUri, $headers)));
$organizationsUri = getURL($response, "organizations");

// Get links to files for all available organizations
$fields = [];
while($organizationsUri)
{
	
	debug_to_console("Organization [ ".$organizationsUri." ]");
	$organizationJson = $oauth->get($organizationsUri, $headers);
	$response = json_decode(($oauth->get($organizationsUri, $headers)));
	$json = json_encode($response);
	debug_to_console("responseOrganization [".$json."]");
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


	echo "<div class='organization-fields'><div class='organization-header'>$name</div>";

	// Call to organization's /files, get a nextPage link if available
	$response = json_decode($oauth->get($link, $headers));
	$next = getURL($response, "nextPage");
	debug_to_console("nextPageURL [$next]");
	$fieldList = $response->values;

	// Go through all next pages, appending new data to $fieldList
	while($next)
	{
		$response = json_decode(($oauth->get($next, $headers)));
		$next = getURL($response, "nextPage");
		$fieldList = array_merge($fieldList, $response->values);
	}

	$rows = -1;
	$files = [];
	for($i = 0; $i < count($fieldList); $i++)
	{

		$linkField[$i] = getURL($fieldList[$i], "self");
		$fieldName[$i] = $fieldList[$i]->name;
		$fieldId[$i] = $fieldList[$i]->id;

		try {
			$fieldSize = floatval($fieldList[$i]->nativeSize)/1000.00. " KB";
		} catch (Throwable $t) {
			$fieldSize = "NULL"; // Executed only in PHP 7, will not match in PHP 5
		} catch (Exception $e) {	
			$fieldSize = "NULL"; // Executed only in PHP 5, will not be reached in PHP 7
		}


		echo "<table width='400'><tr>";

		echo "<td width='200'><div class='tooltip'>$fieldName[$i]
  			<span class='tooltiptext'>$fieldId[$i]</span>
			</div><td>";

		//echo "<td>$fieldName[$i]</br>$fieldId[$i]</td>";
		echo "<td></td>";
		echo "
			<td>
				<form action='ListFields.php' method='post'>
					<input name='fieldName' value='".urlencode($fieldName[$i])."' hidden>
					<input name='fieldId' value='".$fieldId[$i]."' hidden>
					<input name='organizationId' value='".urlencode($organizationId[$name])."' hidden>
					<input name='fieldSize' value='".$fieldSize."' hidden>
					<input name='downloadLink' value='$linkField[$i]' hidden>
					<button type='submit' name='field-option' value='operations'>Operations</button>
				</form>
			</td>";
		/*
		echo "
			<td>
				<form action='ListFields.php' method='post'>
					<input name='fieldName' value='".urlencode($fieldName[$i])."' hidden>
					<input name='fieldId' value='".$fieldId[$i]."' hidden>
					<input name='organizationId' value='".urlencode($organizationId[$name])."' hidden>
					<input name='fieldSize' value='".$fieldSize."' hidden>
					<input name='downloadLink' value='$linkField[$i]' hidden>
					<button type='submit' name='field-option' value='view'>View</button>
				</form>
			</td>";
		*/
		//echo "<td></td>";
		echo "</tr></table>";
		$linkFieldOperations = $linkField[$i]."/fieldOperations";
		debug_to_console("LINK FIELD OPERATIONS [$linkFieldOperations]");
		$responseNew = json_decode(($oauth->get($linkFieldOperations, $headers)));

		$jsonNew = json_encode($responseNew);
		$fieldOperations = $responseNew->values;
		$jsonFieldOperationsResponse = json_encode($fieldOperations);
		//debug_to_console("$jsonNew");
		debug_to_console("Operations response:");
		debug_to_console($jsonFieldOperationsResponse);
		debug_to_console("Entering key value loop ");
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
	echo "<table class='field-operations-table'>";
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
			<td>
				<form action='ListFields.php' method='post'>
					<input name='fieldName' value='".urlencode($files[$i-6])."' hidden>
					<input name='fieldSize' value='".$files[$i-3]."' hidden>
					<input name='downloadLink' value='$files[$i]' hidden>
					<button type='submit' name='field-option' value='download'>Download</button>
				</form>
			</td>";
			echo "
			<td>
				<form action='ListFields.php' method='post'>
					<input name='fieldName' value='".urlencode($files[$i-6])."' hidden>
					<input name='fieldSize' value='".$files[$i-3]."' hidden>
					<input name='downloadLink' value='$files[$i]' hidden>
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
	echo "</nav>";

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
			debug_to_console("view  CLICKED");
		} else if($_POST['field-option'] == "image") {
			debug_to_console(" Image CLICKED");
			//downloadImageNew(urldecode($_POST["fieldName"]), urldecode($_POST["fieldOperationId"]));
			//viewFile($_POST["fileName"], $_POST["downloadLink"]);
		}
	}

	echo "</section>";
}

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
function downloadImage(string $name, string $link, string $headerAccept) {
	global $oauth;

	//$link = "https://sandboxapi.deere.com/platform/fieldOperations/$fieldOperationId/measurementTypes/HarvestYieldContour";
	

	debug_to_console("inside downloadImage [$link]");
	// Make the download directory
	//if(!file_exists('downloads'))
	//	mkdir('downloads');

	$name = urldecode($name);
	debug_to_console("inside downloadImage [$name]");
	//$downloadHeaders = ["Accept" => "image/png"];
	//$downloadHeaders = ["Accept" => "application/octet-stream"];
	$downloadHeaders = ["Accept" => "$headerAccept"];

	debug_to_console("pre oauth->get");
	$responseImage = $oauth->get($link, $downloadHeaders);
	debug_to_console("post response");

	echo $responseImage;

	echo "<div class='downloadSuccess'>Downloaded <a href='downloads/$name' download>$name</a></div>";
}



function downloadImageOld(string $name, string $fieldOperationId) {
	global $oauth;

	$link = "https://sandboxapi.deere.com/platform/fieldOperations/$fieldOperationId/measurementTypes/HarvestYieldContour";
	

	debug_to_console("inside downloadImageOld [$link]");
	// Make the download directory
	//if(!file_exists('downloads'))
	//	mkdir('downloads');

	$name = urldecode($name);
	debug_to_console("inside downloadImageOld [$name]");
	//$downloadHeaders = ["Accept" => "image/png"];
	$downloadHeaders = ["Accept" => "application/octet-stream"];

	debug_to_console("pre oauth->get");
	$responseImage = $oauth->get($link, $downloadHeaders);
	debug_to_console("post response");

	echo $responseImage;

	echo "<div class='downloadSuccess'>Downloaded <a href='downloads/$name' download>$name</a></div>";
}

// Deletes a file
// @param $name -- the URL-encoded name of the file
// @param $link -- the file's link
function deleteFile(string $name, string $link) {
	global $oauth, $headers;
	$oauth->delete($link, $headers);
	echo "<div class='downloadSuccess'>Deleted $name</div>";
}


function viewOperations(string $fieldName, string $orgId, string $fieldId, string $link) {
	global $oauth, $headers;
	debug_to_console("string $fieldName, string $orgId, string $fieldId, string $link");

	//$fieldSize = floatval($fieldList[$i]->nativeSize)/1000.00. " KB";
	$linkFieldOperations = $link."/fieldOperations";
	debug_to_console("LINK FIELD OPERATIONS [$linkFieldOperations]");
	$responseNew = json_decode($oauth->get($linkFieldOperations, $headers));

	$jsonNew = json_encode($responseNew);
	$fieldOperations = $responseNew->values;

	//debug_to_console("JSON FIELD OPERATIONS => $jsonNew");
	$next = getURL($responseNew, "nextPage");
	while($next) {
		$jsonNew = $oauth->get($next, $headers);
		$responseNew = json_decode($jsonNew);
		$next = getURL($responseNew, "nextPage");
		$fieldOperations = array_merge($fieldOperations, $responseNew->values);
		//debug_to_console("JSON FIELD OPERATIONS => $jsonNew");
	}



	echo "<script>  
		function downloadObjectAsJson(operationId){
			var json = document.getElementById('json_'+operationId).innerText;
			//var dataStr = 'data:text/json;charset=utf-8,' + encodeURIComponent(json);
			var dataStr = 'data:text/json;charset=utf-8,' + json;
			var downloadAnchorNode = document.createElement('a');
			downloadAnchorNode.setAttribute('href',     dataStr);
			downloadAnchorNode.setAttribute('download', 'fieldOperations_'+operationId+'.json');
			document.body.appendChild(downloadAnchorNode); // required for firefox
			downloadAnchorNode.click();
			downloadAnchorNode.remove();
  		} 
		function downloadImage(linkFieldOperationImage){
			console.log('image download called '+linkFieldOperationImage);
  		}
		function target_popup(form) {
			window.open('', 'formpopup', 'width=1200,height=700,resizeable,scrollbars');
			form.target = 'formpopup';
		}
		</script>"; 
	echo "<article>";
	$fieldName = str_replace("+", " ", $fieldName);
	echo "<div class='page-title'><div class='tooltip'>$fieldName<span class='tooltiptext'>$fieldId</span></div></div>";
	echo "<div class='operations-fields'>";
	//echo "<table class='file-table'>";
	foreach ($fieldOperations as $key => $value) {
		//foreach ($fieldOperations as $value) {
		//debug_to_console("key[$key] value[$value]");
		$jsonDownload = json_encode($value);
		$safra = $value->cropSeason;
		$cropName = $value->cropName;
		$operation = $value->fieldOperationType;
		$machine = $value->adaptMachineType;
		$startDate = $value->startDate;
		$endDate = $value->endDate;
		$operationId = $value->id;
		$linksArray = $value->links;
		$linkFieldOperation = getURL($value, "self");

		

		switch ($operation) {
			case "harvest":
				$linkFieldOperationImage = getURL($value, "harvestYieldResult");
				$linkFieldOperationApplication = "";
				$arrayPrint[$operationId]["linkFieldOperationImage"] = getURL($value, "harvestYieldResult");
				$flagEnabled = "";
				break;
			case "application":
				$linkFieldOperationApplication = getURL($value, "self");
				$linkFieldOperationImage = "";
				$arrayPrint[$operationId]["linkFieldOperationApplication"] = getURL($value, "self");
				//$flagEnabled = "";
				break;
			default:
				$linkFieldOperationImage = "";
				$linkFieldOperationApplication = "";
				$flagEnabled = "disabled";
				break;
		}

		$countOps = count($linksArray);
		$i = 0;
		
		foreach ($linksArray as $keyin => $valueIn) {
			$linkType = $valueIn->rel;
			$linkUri = $valueIn->uri;
			debug_to_console("$i/$countOps [$linkType]  [$linkUri]  links field operation type <=> [".gettype($valueIn)."]");
			$i++; 
			/*
			$linkShape = null;
			$linkShapeAsync = null;
			$linkMoistureResult = null;
			$linkYieldResult = null;
			$linkMeasurementTypes = null;
			*/
			switch($linkType) {
				case "shapeFile":
					$linkShape = $linkUri;
					debug_to_console("shapefile FOUND [$linkUri] response type[".gettype($response)."]");
					break;
				case "shapeFileAsync":
					$arrayPrint[$operationId]["linkShape"] = $linkUri;
					$linkShapeAsync = $linkUri;
					//debug_to_console("shapeFileAsync FOUND");
					break;
				case "harvestYieldResult":
					$arrayPrint[$operationId]["linkShape"] = $linkUri;
					$linkYieldResult = $linkUri;
					//debug_to_console("harvestYieldResult FOUND");
					break;
				case "harvestMoistureResult":
					$arrayPrint[$operationId]["linkShape"] = $linkUri;
					$linkMoistureResult = $linkUri;
					//debug_to_console("harvestMoistureResult FOUND");
					break;
				case "measurementTypes":
					$arrayPrint[$operationId]["linkShape"] = $linkUri;
					$linkMeasurementTypes = $linkUri;
					debug_to_console("measurementTypes FOUND");
					break;
				default:
					debug_to_console("default");
					break;



			}
		}
		echo "<a id='json_$operationId' hidden>$jsonDownload</a>";

		$beginDay = dateConvert($startDate);
		$endDay = dateConvert($endDate);
		$beginTime = timeConvert($startDate);
		$endTime = timeConvert($endDate);
		//echo "<td class='cell'>$beginDay</br>$endDay</td>";
		//echo "<td class='cell'>$end</td>";

		$arrayPrint[$operationId]["safra"] = $safra;
		$arrayPrint[$operationId]["operation"] = $operation;
		$arrayPrint[$operationId]["machine"] = $machine;
		$arrayPrint[$operationId]["cropName"] = $cropName;
		$arrayPrint[$operationId]["beginDay"] = $beginDay;
		$arrayPrint[$operationId]["beginTime"] = $beginTime;
		$arrayPrint[$operationId]["endDay"] = $endDay;
		$arrayPrint[$operationId]["endTime"] = $endTime;

		//$tmpJsonDebug = json_encode($value);
		//debug_to_console("value: safra: $safra   operation: $operation   machine: $machine   startDate: $startDate   endDate: $endDate ");
		//debug_to_console("valueJSON: $tmpJsonDebug");
	}	
	printOut($arrayPrint);
	//echo "</table>";
	echo "</div>";
	echo "</article>";
	//debug_to_console("Left key value loop count arrayPrint[".count($arrayPrint)."]");

}

function checkDisabled($link) {
	if ($link > "") {
		$flagDisabled = "";
	} else {
		$flagDisabled = "disabled";
	}
	//debug_to_console("checkDisabled [$link] [$flagDisabled]");
	return $flagDisabled;
}

function dateConvert($dateString) {
	//return date("d/m/Y", strtotime($dateString));
	return date("j/M/y", strtotime($dateString));
}
function timeConvert($dateString) {
	return date("d/m/Y H:i", strtotime($dateString));
}

function printOut($arrayPrint) {
	global $cropName,$fieldName;
	//echo "<h2>$fieldName - $cropName</h2>";
	
	//$count = count($arrayPrint);
	//debug_to_console("Inside PrintOut count[$count]");

	echo "<div class='organization-header'>Operation Details</div>";
	echo "<div class='organization-files'>";
	echo "<table class='file-table'>";

	foreach ($arrayPrint as $operationId => $arrayPrintInside) {
		$safra = $arrayPrintInside["safra"];
		$operation = $arrayPrintInside["operation"];
		$machine = $arrayPrintInside["machine"];
		$cropName = $arrayPrintInside["cropName"];
		$beginDay = $arrayPrintInside["beginDay"];
		$beginTime = $arrayPrintInside["beginTime"];
		$endDay = $arrayPrintInside["endDay"];
		$endTime = $arrayPrintInside["endTime"];

		$linkFieldOperationApplication = $arrayPrintInside["linkFieldOperationApplication"];
		$linkFieldOperationImage = $arrayPrintInside["linkFieldOperationImage"];


		echo "<tr>";


		echo "<td  class='cell-large'><div class='tooltip'>$safra  -  $operation  -  $machine  -  $cropName
  			<span class='tooltiptext'>$operationId</span>
			</div><td>";
		echo "<td></td>";
		
		/*

		echo "<td  class='cell-large'><div class='tooltip'>".$arrayPrintInside["safra"]."  -  ".$arrayPrintInside["operation"]."  -  ".$arrayPrintInside["machine"]."  -  ".$arrayPrintInside["cropName"]."
  			<span class='tooltiptext'>".$arrayPrintInside["operationId"]."</span>
			</div><td>";
		*/


		echo "<td  class='cell'><div class='tooltip'>$beginDay</br>$endDay
  			<span class='tooltiptext'>$beginTime</br>$endTime</span>
			</div><td>";
		echo "<td></td>";


		$flagDisabled = checkDisabled($linkFieldOperationApplication);
		echo "
			<td  class='cell'>
				<form action='FieldOperationDetailsApplication.php' method='post' onsubmit='target_popup(this)'>
					<input name='fieldName' value='".urlencode($fieldName)."' hidden>
					<input name='cropName' value='".urlencode($cropName)."' hidden>
					<input name='operation' value='".urlencode($operation)."' hidden>
					<input name='fieldOperationId' value='".urlencode($operationId)."' hidden>
					<input name='link' value='".urlencode($linkFieldOperation)."' hidden>
					<input name='linkBase' value='".urlencode($linkFieldOperationApplication)."' hidden>
					<button type='submit' name='field-option' value='viewOperation' $flagDisabled>Application</button>
				</form>
			</td>";


		$flagDisabled = checkDisabled($linkFieldOperationImage);
		echo "
			<td  class='cell'>
				<form action='FieldOperationDetailsHarvest.php' method='post' onsubmit='target_popup(this)'>
					<input name='fieldName' value='".urlencode($fieldName)."' hidden>
					<input name='cropName' value='".urlencode($cropName)."' hidden>
					<input name='operation' value='".urlencode($operation)."' hidden>
					<input name='fieldOperationId' value='".urlencode($operationId)."' hidden>
					<input name='link' value='".urlencode($linkFieldOperation)."' hidden>
					<input name='linkBase' value='".urlencode($linkFieldOperationImage)."' hidden>
					<button type='submit' name='field-option' value='viewOperation' $flagDisabled>HARVEST YIELD</button>
				</form>
			</td>";
		echo '<td class="cell"><button onclick="downloadObjectAsJson(\''.$operationId.'\');">JSON</button></td>';
		echo "</tr>";
	} 
	echo "</table>";

	echo "</div>";
}


?>

<div class="footer"></div>



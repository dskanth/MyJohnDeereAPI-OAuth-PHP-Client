<?php
// List files available to the current user
// Finds all files in all organizations
// @author Gordon Wang <WangGordon@JohnDeere.com>
// @author Pravin Taralkar <TaralkarPravin@JohnDeere.com>
// @author Tony Gil <tonygil@agrigis.com.br> 
// @date August 2018

// adapted from ImageDisplay02a.php 



require "Header.php";

$oauth = new ProxyAwareOAuth($settings["App_Key"], $settings["App_Secret"], $settings["Proxy"], $settings["ProxyAuth"]);
loadToken($oauth);


$cropName = str_replace("+"," ",urldecode($_POST["cropName"]));
$fieldName = str_replace("+"," ",urldecode($_POST["fieldName"]));
$linkBase = urldecode($_POST["linkBase"]);
$responseJson = $oauth->get($linkBase, $headers);
debug_to_console("responseJson [$responseJson]");
$response = json_decode($responseJson,TRUE,20); 
$arrayContent = array("area","yield","wetMass","averageYield","averageMoisture","averageWetMass");

$arrayFinal["Total"] = getData($response);


foreach ($response as $key => $value) {

	if ($key == "varietyTotals") {
		//debug_to_console("FOUND varietyTOTALS");
		$arrayBackVariety = getVariety($value);
	}

	if ($key == "links") {
		$arrayBackVariety = getLinks($value);
		debug_to_console("FOUND LINKS [".count($arrayBackVariety)."]");
		foreach ($arrayBackVariety as $key1 => $value1) {
			if ($key1 == "mapImage") {
				$imageData = getImageData($value1);
				//debug_to_console("imageData[$imageData]");
			}
			//debug_to_console("links key[$key1] value[$value1]");
		}
	}
}

$operationId = $_POST["fieldOperationId"];
$linkApplicationShapefileAsync = "https://sandboxapi.deere.com/platform/fieldOps/$operationId";
$linkDownloadShapeAsync = linkDownloadShapeAsync($linkApplicationShapefileAsync);

printOut();

function getData($arrayIn) {
	global $arrayContent;
	debug_to_console("FOUND??  looking getData");
	$response = $arrayIn;
	foreach ($arrayIn as $key => $value) {
		if (in_array($key,$arrayContent)) {
			$arrayResultTotal[$key]["value"] = $value["value"];
			$arrayResultTotal[$key]["unitId"] = $value["unitId"];
			//debug_to_console("FOUND function getData [$key] value[".$arrayResultTotal[$key]["value"]."] unit[".$arrayResultTotal[$key]["unitId"]."]");
		}
	}
	return $arrayResultTotal;
}

function getVariety($arrayIn) {
	global $arrayFinal;
	foreach ($arrayIn as $key => $value) {
		$name = $value["name"];
		$arrayFinal[$name] = getData($value);
		//debug_to_console("FOUND entered varietyTOTALS [$key] name[$name]");
	}
}
function getLinks($arrayIn) {
	foreach ($arrayIn as $key => $value) {
		$parameter = $value["rel"];
		$link = $value["uri"];
		$arrayOut[$parameter] = $link;
		//debug_to_console("FOUND entered LINKS [$key] rel[$parameter] uri[$link]");
	}
	return $arrayOut;
}
function linkDownloadShapeAsync(string $link) {
	global $oauth,$headers;
	debug_to_console("inside linkDownloadShapeAsync [$link]");
	$linkShape = $oauth->getShapeAsync($link, $headers);
	//debug_to_console("post responseShapeAsync linkShape[$linkShape]");

	return $linkShape;
}

function getImageData(string $link) {
	global $oauth,$headers;
	debug_to_console("inside downloadImage[$link]");
	$downloadHeaders = ["Accept" => "application/vnd.deere.axiom.v3.image+json"];
	//$oauth->getImage($link, $downloadHeaders);
	$imageData = $oauth->getImage($link, $downloadHeaders);
	//debug_to_console("post responseShapeAsync linkImage[$linkImage]");

	//echo $linkImage;
	return $imageData;

}
function printOut() {
	global $arrayFinal,$cropName,$fieldName,$linkDownloadShapeAsync,$imageData, $operationId;
	$flagFirstRun = true;
	//echo "<h2>$fieldName - $cropName</h2>";


	$imageDataRaw = $imageData["image"];
	$imageExtent = $imageData["extent"]; // array 4 items
	$imageLegend =  $imageData["legend"]["unitId"];
	$imageRanges =  $imageData["legend"]["ranges"]; // array 0 to 6

	debug_to_console("imageDataRaw[$imageDataRaw]");

	foreach ($imageExtent as $key => $value) {
		debug_to_console("extent[$key] [$value]");
	}



	echo "<div class='page-title'>Harvest Details $fieldName - $cropName</div>";
	echo "<section><nav>";
	echo "<div class='organization-files'>";
	foreach ($arrayFinal as $resultType => $arrayResultTotal) { 
		echo "<h3>Variety - $resultType</h3>";
		echo "<table class='file-table'>";
		foreach ($arrayResultTotal as $parameter => $array) {
			echo "<tr>";
			$value = $array[value];
			$unitId = $arrayResultTotal[$parameter][unitId];
			echo "<td>$parameter</td><td>$value</td><td>$unitId</td>";
		}
		echo "</table>";
		if ($flagFirstRun) {

			
			echo "<table><tr>";
			echo '<td class="cell"><a href="';
			echo $linkDownloadShapeAsync;
			echo '"><button>Down Shape</button></a></td>';
		

			$pngData = substr($imageDataRaw,22);
			echo '<td class="cell"><button onclick="downloadFile(\''.$operationId.'.png\',\'';
			echo $pngData;
			echo '\')">Image Download</button></td>';
			echo "</tr></table>";

			$flagFirstRun = false;
		}
	}

	echo "</div>";
	echo "</nav><article>";
	if (gettype($imageData == "array")) {
		echo imageLayerGenerate($imageData);
	} else {
		echo "<h1>Server timed out, please reload page.</h1>";
	}
	echo "</article></section>";

	echo "<img src='$imageDataRaw' />";
}

// include openlayers header in Header.php
function imageLayerGenerate(array $imageData) {
	$imageDataRaw = $imageData["image"];
	$imageExtent = $imageData["extent"]; // array 4 items ex. ["minimumLatitude"]
	$imageLegend =  $imageData["legend"]["unitId"];
	$imageRanges =  $imageData["legend"]["ranges"]; // array 0 to 6
	foreach ($imageRanges as $id => $legendArray) {
		$legendText[$id] = "legend hexcolor-$id $legendArray[hexColor] $legendArray[minimum]  $legendArray[maximum]  $legendArray[percent]";
		//debug_to_console("legend[$legendText[$id]]");

	}

	// the license key WT0nyCwGilB5wasYzhereA2Bc9yZf4fiPxsDl1xR8_ejKlmF2Kgw3sGuNth3RnS
	// will NOT work and serves only as a placeholder
	// get your free Bing license key online (AUg 2018)
	$output =  "  <div id='map' class='map600'></div>
	    <script type='text/javascript'>
		                        
		        var projection = ol.proj.get('EPSG:3857'); 
		          
		        var bing = new ol.layer.Tile({
		            source: new ol.source.BingMaps({
		              imagerySet: 'Aerial',
		              key: 'WT0nyCwGilB5wasYzhereA2Bc9yZf4fiPxsDl1xR8_ejKlmF2Kgw3sGuNth3RnS'
		            })
		        });
	";
	$output .=  "

		var minLon = ".$imageExtent[minimumLongitude].";
		var minLat = ".$imageExtent[minimumLatitude].";
		var maxLon = ".$imageExtent[maximumLongitude].";
		var maxLat = ".$imageExtent[maximumLatitude].";
		//var extent = [minlon, minlat, maxlon, maxlat];
		var extent = new ol.extent.applyTransform([minLon, minLat, maxLon, maxLat], ol.proj.getTransform('EPSG:4326','EPSG:3857'));
	
             
        
                var view = new ol.View({
                    center: [0, 0],
                    zoom: 1
                });
                var overlay = new ol.Overlay({
                  element: document.getElementById('overlay'),
                  positioning: 'bottom-center'
                });
		var pngImageLayer = new ol.layer.Image({
			source: new ol.source.ImageStatic({
				imageExtent: extent,
				url: 'maps/colheitaMapa.png'
			})
		  });";
	$centerLon = ($imageExtent[minimumLongitude]+$imageExtent[maximumLongitude])/2;
	$centerLat = ($imageExtent[minimumLatitude]+$imageExtent[maximumLatitude])/2;
	$output .=  "var src = '$imageDataRaw';
		var pngImageBase64Layer = new ol.layer.Image({
			source: new ol.source.ImageStatic({
				imageExtent: extent,
                		imageLoadFunction : function(image){
                    			image.getImage().src = src;
                		}
			})
            	});

                var map = new ol.Map({
                    //layers: [bing, raster, layerOSGBtiles2, vector, perimeter],
                    layers: [bing, pngImageBase64Layer],
                    target: document.getElementById('map'),
                    view: new ol.View({
                      center: ol.proj.fromLonLat([$centerLon,$centerLat]),
                      projection: projection,
                      zoom: 14 
                    })
                });";
	$output .=  "function downloadFile(filename, text) {
		var element = document.createElement('a');
		//element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
		element.setAttribute('href', 'data:image/png;base64,' + text);
		element.setAttribute('download', filename);

		element.style.display = 'none';
		document.body.appendChild(element);

		element.click();

		document.body.removeChild(element);
	}";
	$output .=  "</script>";
	return $output;
}



?>

<?php
// TRASH CODE

// Downloads a file in 10 KB chunks
// @param $name -- the URL-encoded name of the file
// @param $link -- the file's download link
// @param $size -- the size of the file in KB

/*
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
*/

/*

function downloadImageNew(string $name) {
	global $oauth,$linkBase;

	//$linkImageContour = "https://sandboxapi.deere.com/platform/fieldOperations/$fieldOperationId/measurementTypes/HarvestYieldContour";
	//$linkShape = "https://sandboxapi.deere.com/platform/fieldOps/$fieldOperationId";

	//$link = $linkShape;
	//$link = $linkImageContour;
	$link = $linkBase;

	debug_to_console("inside downloadImageNew [$link]");
	// Make the download directory
	//if(!file_exists('downloads'))
	//	mkdir('downloads');

	$name = urldecode($name);
	debug_to_console("inside downloadImageNew [$name]");
	$downloadHeaders = ["Accept" => "application/vnd.deere.axiom.v3+json"];

	debug_to_console("pre oauth->get");
	$responseImage = $oauth->get($link, $downloadHeaders);
	debug_to_console("post response");

	//echo $responseImage;

	//echo "<div class='downloadSuccess'>Downloaded <a href='downloads/$name' download>$name</a></div>";
}
*/
?>

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
foreach ($headers as $key => $value) {
	debug_to_console("headers key[$key] [$value]");
}

$cropName = str_replace("+"," ",urldecode($_POST["cropName"]));
$fieldName = str_replace("+"," ",urldecode($_POST["fieldName"]));
$linkBase = urldecode($_POST["linkBase"]);
$responseJson = $oauth->get($linkBase, $headers);
$response = json_decode($responseJson,TRUE,20); 
$arrayContent = array("area","yield","wetMass","averageYield","averageMoisture","averageWetMass"); 

//echo "<div>$responseJson</div>";

$i = 0;
foreach ($response as $key => $value) {
	//debug_to_console("Application details key[$key]");
	if ($key == "product") {
		foreach ($value as $key2 => $value2) {
			if ($key2 == "carrier") {
				$component[$i]["name"] = $value2["name"];
				foreach ($value2 as $key4 => $value4) {
					if ($key4 == "rate") {
						$component[$i]["value"] = $value4["value"];
						$component[$i]["unitId"] = $value4["unitId"];
						//debug_to_console("inside rate key[".$component[$i]["name"]."] [".$component[$i]["value"]."] [".$component[$i]["unitId"]."]");
					}
				}
			} elseif ($key2 == "components") {
				foreach ($value2 as $key3 => $value3) {
					$i++;
					$component[$i]["name"] = $value3["name"];
					foreach ($value3 as $key4 => $value4) {
						if ($key4 == "rate") {
							$component[$i]["value"] = $value4["value"];
							$component[$i]["unitId"] = $value4["unitId"];
							//debug_to_console("inside rate key[".$component[$i]["name"]."] [".$component[$i]["value"]."] [".$component[$i]["unitId"]."]");
						}
					}
				}
			}
		}
	} else if ($key == "links"){
		foreach ($value as $key2 => $value2) {
			$tempType = $value2["rel"];
			switch ($tempType) {
				case "shapeFile":
				case "shapeFileAsync":
				case "applicationRateResult":
					$linkApplication[$tempType] = $value2["uri"];
					break;
				default:
					break;

			}
		}
				
		$linkApplicationRate = $linkApplication["applicationRateResult"];
		$linkApplicationShapefile = $linkApplication["shapeFile"];
		$linkApplicationShapefileAsync = $linkApplication["shapeFileAsync"]; // get shapeFile
		//debug_to_console("inside rate key[$linkApplicationRate] [$linkApplicationShapefile] [$linkApplicationShapefileAsync]");
	}
}

$linkDownloadShapeAsync = linkDownloadShapeAsync($linkApplicationShapefileAsync);
$imageData = getImageData($linkApplicationRate);
debug_to_console("imageData[$imageData]");

printOut();

function linkDownloadShapeAsync(string $link) {
	global $oauth,$headers;
	//$link = $linkApplicationShapefileAsync;

	debug_to_console("inside downloadImage [$link]");
	// Make the download directory
	//if(!file_exists('downloads'))
	//	mkdir('downloads');
	$linkShape = $oauth->getShapeAsync($link, $headers);
	debug_to_console("post responseShapeAsync linkShape[$linkShape]");

	//echo $linkShape;
	return $linkShape;
}
function getImageData(string $link) {
	global $oauth,$headers;
	debug_to_console("inside downloadImage [$link]");
	$downloadHeaders = ["Accept" => "application/vnd.deere.axiom.v3.image+json"];
	//$oauth->getImage($link, $downloadHeaders);
	$imageData = $oauth->getImage($link, $downloadHeaders);
	//debug_to_console("post responseShapeAsync linkImage[$linkImage]");

	//echo $linkImage;
	return $imageData;

}


function printOut() {
	global $component,$cropName,$fieldName, $linkDownloadShapeAsync, $imageData;

	$imageDataRaw = $imageData["image"];
	$imageExtent = $imageData["extent"]; // array 4 items
	$imageLegend =  $imageData["legend"]["unitId"];
	$imageRanges =  $imageData["legend"]["ranges"]; // array 0 to 6
	debug_to_console("imageLegend[$imageLegend]");
	debug_to_console("imageDataRaw[$imageDataRaw]");

	echo "<div class='page-title'>Application Details $fieldName</div>";

	echo "<section><nav>";

	echo "<div class='organization-files'>";
	foreach ($component as $key => $componentArray) { 
			$parameter = $componentArray["name"];
			$value = $componentArray["value"];
			$unitId = $componentArray["unitId"];

			echo "<tr>";
			echo "<td>$parameter</td>&nbsp&nbsp<td>$value</td>&nbsp&nbsp<td>$unitId</td>";
			echo "</tr>";
		//echo "<h3>Variety - $resultType</h3>";
		echo "<table class='file-table'>";
		echo "</table>";
	}
 
	echo "<table><tr>";
	echo '<td class="cell"><a href="';
	echo $linkDownloadShapeAsync;
	echo '">Down Shape</a></td>';
	echo "</tr></table>";
	echo "</div>";

	echo "</nav><article>";
	echo imageLayerGenerate($imageData);
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
// PLACE TRASH CODE HERE

/*
	echo '<td class="cell"><a href="';
	echo $linkDownloadShapeAsync;
	echo '">Image</a></td>';
	echo "</tr></table>";
			
*/


/*



	$downloadHeaders02 = ["Accept" => "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAkUAAACzCAMAAACw9JjeAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAMAUExURe8fJ2+/P3u9QQAAAAAAAOsfJwAAAG69QwAAAAAAAAAAAAAAAOogKQAAAHC+QwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAOsfJwAAAAAAAAAAAHHARQAAAAAAAAAAAAAAAAAAAG+/RHO+QwAAAHC+Q+weJwAAAAAAAHC+QwAAAAAAAAAAAAAAAAAAAAAAAAAAAG6/QwAAAAAAAAAAAOsfJ2+/QwAAAO8fLwAAAAAAAHG/QgAAAAAAAAAAAHG/Q+weKAAAAO0eKAAAAOkfKuwfKAAAAAAAAAAAAAAAAHC9QQAAAAAAAAAAAHG9QwAAAAAAAAAAAAAAAHK/RgAAAHG/Q+weJ+ofKAAAAHG+RAAAAHG9QnHAQ3G9RgAAAAAAAAAAAHC+Q268QgAAAOkfKgAAAAAAAOweKAAAAAAAAAAAAHC+Q+seKG+/R3G/Q3K/QusfKG+/RXC/Q3G/RHG/Q3C+Q3DAROseKAAAAGbMmWbMzGbM/2b/AGb/M2b/Zmb/mWb/zGb//5kAAJkAM5kAZpkAmZkAzJkA/5kzAJkzM5kzZpkzmZkzzJkz/5lmAJlmM5lmZplmmZlmzJlm/5mZAJmZM5mZZpmZmZmZzJmZ/5nMAJnMM5nMZpnMmZnMzJnM/5n/AJn/M5n/Zpn/mZn/zJn//8wAAMwAM8wAZswAmcwAzMwA/8wzAMwzM8wzZswzmcwzzMwz/8xmAMxmM8xmZsxmmcxmzMxm/8yZAMyZM8yZZsyZmcyZzMyZ/8zMAMzMM8zMZszMmczMzMzM/8z/AMz/M8z/Zsz/mcz/zMz///8AAP8AM/8AZv8Amf8AzP8A//8zAP8zM/8zZv8zmf8zzP8z//9mAP9mM/9mZv9mmf9mzP9m//+ZAP+ZM/+ZZv+Zmf+ZzP+Z///MAP/MM//MZv/Mmf/MzP/M////AP//M///Zv//mf//zP///zjuJAEAAAEAdFJOUyAQHwAAgADbAAAAAFcA1AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAEAAAABRAAAAAABwSwBnxgAA7AAAAAAAAABMAAAAnEAAEAAAWAAAAJDUAJ8AMP8AAAAARgAAALkAAAAAKACAp3AA2ADLai8AAAD7LgAYAACvAAAAmt8ghGB4MIqUSJ6G4gD//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////wC0YnPpAAAACXBIWXMAAA7DAAAOwwHHb6hkAAACeElEQVR4Xu3WP2tTcRSAYRWCi/SDZHBSKmR0MBQ6WIJdHLJIFUkdIkIHtWr996lrhNtSiLW3vOvzTOd35pd7z51zqFREpyI6FdGpiE5FdCqiUxGdiuhURKciOhXRqYhORXQqolMRnYroVESnIjoV0amITkV0KqJTEZ2K6FREpyI6FdGpiE5FdCridh7vf/81jJdUxEjv54+W92fr9Xr2cdhcUhE3+vblbLmzyWc92znbfzgsr1IR17v3cvd09Tef9dvl6e67YbtNRfzLqx9Hq6Gf1dH887C9jorYstz0szmAdn4ePb07rP5PRWyZrZ4fz4d5FBXRqYhORXQqolMRnYroVESnIjoV0amITkV0KqJTEZ2K6FREpyI6FdGpiE5FdCqiUxGdiuhURKciOhXRqYhORXQqolMRnYroVESnIjoV0amITkV0KqJTEZ2K6FREpyI6FdGpiE5FdCqiUxGdiuhURKciOhXRqYhORXQqolMRnYroVESnIjoV0amITkV0KqJTEZ2K6FREpyI6FdGpiE5FdCqiUxGdiuhURKciOhXRqYhORXQqolMRnYroVESnIjoV0amITkV0KqJTEZ2K6FREpyI6FdGpiE5FdCqiUxGdiuhURKciOhXRqYhORXQqolMRnYroVESnIjoV0amITkV0KqJTEd2IioTGDUYksjg8eP31wfCAbWM+NHvTycmnw4Mnk+nesIGrxv+ups8WB4e/TyYfhjdcuOXRM32zeDGMcMHpTKciOhXRqYhORXQqolMRnYroVESnIjoV0amITkV0KqJTEZ2K6FREpyI6FdGpiE5FdCqiUxGdiuhURKciOhXRqYjq/PwPt9h64Nb6a5gAAAAASUVORK5CYII="];
	$downloadHeaders03 = ["Accept" => "image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAkUAAACzCAMAAACw9JjeAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAMAUExURe8fJ2+/P3u9QQAAAAAAAOsfJwAAAG69QwAAAAAAAAAAAAAAAOogKQAAAHC+QwAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAOsfJwAAAAAAAAAAAHHARQAAAAAAAAAAAAAAAAAAAG+/RHO+QwAAAHC+Q+weJwAAAAAAAHC+QwAAAAAAAAAAAAAAAAAAAAAAAAAAAG6/QwAAAAAAAAAAAOsfJ2+/QwAAAO8fLwAAAAAAAHG/QgAAAAAAAAAAAHG/Q+weKAAAAO0eKAAAAOkfKuwfKAAAAAAAAAAAAAAAAHC9QQAAAAAAAAAAAHG9QwAAAAAAAAAAAAAAAHK/RgAAAHG/Q+weJ+ofKAAAAHG+RAAAAHG9QnHAQ3G9RgAAAAAAAAAAAHC+Q268QgAAAOkfKgAAAAAAAOweKAAAAAAAAAAAAHC+Q+seKG+/R3G/Q3K/QusfKG+/RXC/Q3G/RHG/Q3C+Q3DAROseKAAAAGbMmWbMzGbM/2b/AGb/M2b/Zmb/mWb/zGb//5kAAJkAM5kAZpkAmZkAzJkA/5kzAJkzM5kzZpkzmZkzzJkz/5lmAJlmM5lmZplmmZlmzJlm/5mZAJmZM5mZZpmZmZmZzJmZ/5nMAJnMM5nMZpnMmZnMzJnM/5n/AJn/M5n/Zpn/mZn/zJn//8wAAMwAM8wAZswAmcwAzMwA/8wzAMwzM8wzZswzmcwzzMwz/8xmAMxmM8xmZsxmmcxmzMxm/8yZAMyZM8yZZsyZmcyZzMyZ/8zMAMzMM8zMZszMmczMzMzM/8z/AMz/M8z/Zsz/mcz/zMz///8AAP8AM/8AZv8Amf8AzP8A//8zAP8zM/8zZv8zmf8zzP8z//9mAP9mM/9mZv9mmf9mzP9m//+ZAP+ZM/+ZZv+Zmf+ZzP+Z///MAP/MM//MZv/Mmf/MzP/M////AP//M///Zv//mf//zP///zjuJAEAAAEAdFJOUyAQHwAAgADbAAAAAFcA1AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAEAAAABRAAAAAABwSwBnxgAA7AAAAAAAAABMAAAAnEAAEAAAWAAAAJDUAJ8AMP8AAAAARgAAALkAAAAAKACAp3AA2ADLai8AAAD7LgAYAACvAAAAmt8ghGB4MIqUSJ6G4gD//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////wC0YnPpAAAACXBIWXMAAA7DAAAOwwHHb6hkAAACeElEQVR4Xu3WP2tTcRSAYRWCi/SDZHBSKmR0MBQ6WIJdHLJIFUkdIkIHtWr996lrhNtSiLW3vOvzTOd35pd7z51zqFREpyI6FdGpiE5FdCqiUxGdiuhURKciOhXRqYhORXQqolMRnYroVESnIjoV0amITkV0KqJTEZ2K6FREpyI6FdGpiE5FdCridh7vf/81jJdUxEjv54+W92fr9Xr2cdhcUhE3+vblbLmzyWc92znbfzgsr1IR17v3cvd09Tef9dvl6e67YbtNRfzLqx9Hq6Gf1dH887C9jorYstz0szmAdn4ePb07rP5PRWyZrZ4fz4d5FBXRqYhORXQqolMRnYroVESnIjoV0amITkV0KqJTEZ2K6FREpyI6FdGpiE5FdCqiUxGdiuhURKciOhXRqYhORXQqolMRnYroVESnIjoV0amITkV0KqJTEZ2K6FREpyI6FdGpiE5FdCqiUxGdiuhURKciOhXRqYhORXQqolMRnYroVESnIjoV0amITkV0KqJTEZ2K6FREpyI6FdGpiE5FdCqiUxGdiuhURKciOhXRqYhORXQqolMRnYroVESnIjoV0amITkV0KqJTEZ2K6FREpyI6FdGpiE5FdCqiUxGdiuhURKciOhXRqYhORXQqolMRnYroVESnIjoV0amITkV0KqJTEd2IioTGDUYksjg8eP31wfCAbWM+NHvTycmnw4Mnk+nesIGrxv+ups8WB4e/TyYfhjdcuOXRM32zeDGMcMHpTKciOhXRqYhORXQqolMRnYroVESnIjoV0amITkV0KqJTEZ2K6FREpyI6FdGpiE5FdCqiUxGdiuhURKciOhXRqYjq/PwPt9h64Nb6a5gAAAAASUVORK5CYII="];

	$downloadHeaders04 = ["Accept" => "com.deere.api.axiom.generated.v3.FieldOperationMapImage"];

	debug_to_console("pre oauth->get");
	//$responseImage = $oauth->get($link, $downloadHeaders);
	//echo $responseImage;
	$responseImage = $oauth->getImage($link, $downloadHeaders04);
	echo $responseImage;
	$responseImage = $oauth->getImage($link, $downloadHeaders02);
	echo $responseImage;
	$responseImage = $oauth->getImage($link, $downloadHeaders03);
	echo $responseImage;

*/

?>

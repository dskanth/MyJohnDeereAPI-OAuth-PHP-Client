<?php
// MyJohnDeere OAuth Workflow
// This page handles the OAuth workflow, up to acquiring the access token
// @author Gordon Wang <WangGordon@JohnDeere.com>
// @date July 2016
require "Header.php";

// MyJohnDeere automated verifier
// Replacing "oob" with an Internet-accessible callback page allows automation
// of the OAuth verifier.  See callback.php for an example callback page.
define("CALLBACK_URL", "oob");

// If settings changed, update and save new settings
if(!empty($_POST["appKey"]) && !empty($_POST["appSecret"]))
{
	$settings["App_Key"] = $_POST["appKey"];
	$settings["App_Secret"] = $_POST["appSecret"];
	saveSettings();
}
if(isset($_POST["proxyServer"]) && isset($_POST["proxyPort"]))
{
	$settings["Proxy"] = $_POST["proxyServer"].":".$_POST["proxyPort"];
	if($settings["Proxy"] == ":")
		$settings["Proxy"] = "";

	$settings["ProxyAuth"] = $_POST["proxyUser"].":".$_POST["proxyPassword"];
	if($settings["ProxyAuth"] == ":")
		$settings["ProxyAuth"] = "";

	saveSettings();
}

$accessToken = NULL;
$authorizationURL = NULL;

// Get an access token if an OAuth verifier was passed
if(!empty($_POST["oauthVerifier"]))
{
	$oauth = new ProxyAwareOAuth($settings["App_Key"], $settings["App_Secret"], $settings["Proxy"], $settings["ProxyAuth"]);

	// Grab the URL for access tokens
	$response = json_decode($oauth->get($settings["MyJohnDeere_API_URL"], $headers));
	$accessTokenURL = getURL($response, "oauthAccessToken");

	// Set the request token and grab the access token
	$oauth->setToken($_POST['reqToken'], $_POST['reqSecret']);
	$accessToken = $oauth->getAccessToken($accessTokenURL, $_POST["oauthVerifier"], OAUTH_HTTP_METHOD_GET);

	// Save the access token
	$fd = fopen("savedToken.txt", "w");
	fwrite($fd, time().PHP_EOL.$accessToken['oauth_token'].PHP_EOL.$accessToken['oauth_token_secret']);
	fclose($fd);

	// Delete any remaining request token secrets
	if(file_exists("savedRequestToken.txt"))
		unlink("savedRequestToken.txt");
}
// If the flag for request tokens is set
else if(!empty($_POST["getToken"]))
{
	// Delete saved token if it exists
	if(file_exists("savedToken.txt"))
		unlink("savedToken.txt");

	$oauth = new ProxyAwareOAuth($settings["App_Key"], $settings["App_Secret"], $settings["Proxy"], $settings["ProxyAuth"]);
	
	// Grab the URL for request tokens and base URL for authorization
	$response = json_decode($oauth->get($settings["MyJohnDeere_API_URL"], $headers));
	$requestTokenURL = getURL($response, "oauthRequestToken");
	$authorizationURL = substr(getURL($response, "oauthAuthorizeRequestToken"), 0, -7); // Remove "{token}" from end

	// Get request token and append to base URL for authorization	
	$requestToken = $oauth->getRequestToken($requestTokenURL, CALLBACK_URL, OAUTH_HTTP_METHOD_GET);
	$authorizationURL .= $requestToken['oauth_token'];

	// Save the request token secret, used by callback.php
	$fd = fopen("savedRequestToken.txt", "w");
	fwrite($fd, $requestToken["oauth_token_secret"]);
	fclose($fd);
}
?>

<script>
// When "Change" is clicked for Application Credentials
function changeAPICredentials()
{
	$("#savedCredentials").slideUp("slow");
	$("#inputCredentials").css("display", "");
}

// When "Change" is clicked for Proxy
function changeProxy()
{
	$("#savedProxy").slideUp("slow");
	$("#inputProxy").css("display", "");
}
</script>

<div class="border">
	<div class="section"><b>Proxy Configuration</b>
		<div id="savedProxy">
			<?php echo $settings["Proxy"] == "" ? "None" : $settings["Proxy"]; ?><br>
			<button onclick="changeProxy();">Change</button>
		</div>
		<form action="index.php" method="post" id="inputProxy" style="display:none">
			<table>
				<tr>
					<td><input name="proxyServer" type="text" size="40" value="<?php echo  $settings["Proxy"] == "" ? "":explode(":", $settings["Proxy"])[0]; ?>">
					<br>Proxy server</td>
					<td><input name="proxyPort" type="text" maxlength="5" value="<?php  echo  $settings["Proxy"] == "" ? "":explode(":", $settings["Proxy"])[1]; ?>">
					<br>Proxy port</td>
				</tr>
				<tr>
					<td>Proxy username:</td>
					<td><input type="text" name="proxyUser" size="35"></td>
				</tr>
				<tr>
					<td>Proxy password:</td>
					<td><input type="password" name="proxyPassword" size="35"></td>
				</tr>
			</table><br>
			<button type="submit" name="proxy" value="submit">Save</button>
		</form>
	</div>

	<div class="section"><b>Application Credentials</b><br>
<?php
// Attempt to load saved access tokens
$fd = file_exists("savedToken.txt") ? fopen("savedToken.txt", "r") : false;

if($fd == false) // no token found
	$accessToken = "none";
else if(time() - intval(fgets($fd)) > 60*60*24*300) // expired token found
	$accessToken = "expired";
else //valid token found
{
	$token = trim(fgets($fd)); // Remove \n from end
	$secret = trim(fgets($fd));
	
	if($token == "" || $secret == "")
		$accessToken = "none";
	else
		$accessToken = ["oauth_token" => $token, "oauth_token_secret" => $secret];
}
	
if($fd)
	fclose($fd);

// If app key or app secret isn't set, show form to set them
if($settings["App_Key"] == "" || $settings["App_Secret"] == "")
{
	echo "
	No valid credentials found.
	<form action='index.php' method='post'>
		<div id='inputCredentials'>
		<table class='content'>
			<tr>
				<td class='parameter'>Enter application key:</td>
				<td><input type='text' name='appKey' size='60'></input></td>
			</tr>
			<tr>
				<td>Enter application secret:</td>
				<td><input type='text' name='appSecret' size='60'></input><td>
			</tr>
		</table>
		<button type='submit' value='Submit'>Submit</button>
		</div>
	</form>";
}
// Otherwise, allow them to be changed (but not if there's an access token loaded)
else
{
	echo "
	<div id='savedCredentials'>
		<table class='content'>
			<tr>
				<td class='parameter'>Saved application key:</td>
				<td>".$settings["App_Key"]."</td>
			</tr>
			<tr>
				<td>Saved application secret:</td>
				<td>".$settings["App_Secret"]."<td>
			</tr>
		</table>";
	echo gettype($accessToken) == "array" ? "</div>": // Don't allow change if access token loaded
		"<button onclick='changeAPICredentials();'>Change</button></div>";
	
	// Form to change current app key and secret
	echo "
	<form action='index.php' method='post' id='inputCredentials' style='display:none'>
		<table class='content'>
			<tr>
				<td class='parameter'>Enter application key:</td>
				<td><input type='text' name='appKey' value='".$settings["App_Key"]."' size='60'></input></td>
			</tr>
			<tr>
				<td>Enter application secret:</td>
				<td><input type='text' name='appSecret' value='".$settings["App_Secret"]."' size='60'></input><td>
			</tr>
		</table>
		<button type='submit' value='Submit'>Submit</button>
	</form>";
}
?>
	</div>

	<div class="section"><b>OAuth Access Token</b><br>
<?php
// If no app key or secret, delete any remaing access tokens
if($settings["App_Key"] == "" || $settings["App_Secret"] == "")
{
	if(file_exists("savedToken.txt"))
		unlink("savedToken.txt");
	echo "Please set the application key and secret.";
}
// If there's a valid access token, display it
else if(gettype($accessToken) == "array")
{
	echo "
	Token successfully loaded.
	<table class='content'>
		<tr>
			<td class='parameter'>Token:</td>
			<td>".$accessToken['oauth_token']."</td>
		</tr>
		<tr>
			<td>Secret:</td>
			<td id='token-secret'>".$accessToken['oauth_token_secret']."</td>
		</tr>
	</table>
	<form action=index.php method=post>
		<button name='getToken' type='submit' value='getToken'>New Token</button>
	</form>";
}
// If there's no valid access token or authorization URL, display the button to get new token
else if($authorizationURL == NULL)
{
	echo $accessToken == "none"?"No token found.<br>":"Token expired.<br>";
	echo "
	<form action=index.php method=post>
		<button name='getToken' type='submit' value='getToken'>New Token</button>
	</form>";
}
// If there's a no valid token, but authorization URL is set
else
{
	if(CALLBACK_URL == "oob") // For no callback, show the form to enter verifier
		echo "
		Enter OAuth verifier from<br><a href='$authorizationURL' target='_blank'>$authorizationURL</a>
		<form action='index.php' method='post'>
			<table>
				<tr>
					<td class='parameter'>Request token:</td>
					<td><input type='text' size='60' name='reqToken' value='".$requestToken["oauth_token"]."' readonly></td>
				</tr>
				<tr>
					<td class='parameter'>Request token secret:</td>
					<td><input type='text' size='60' name='reqSecret' value='".$requestToken["oauth_token_secret"]."' readonly></td>
				</tr>
				<tr>
					<td class='parameter'>OAuth verifier:</td>
					<td><input type='text' size='60' name='oauthVerifier'></input></td>
				</tr>
			</table>
			<button type='submit' value='Submit'>Submit</button>
		</form>";
	else // If there's a callback URL, just redirect to the authorization URL
		header("Location: $authorizationURL");
}
?>
	</div>
</div>

<div class="footer">&nbsp;</div>
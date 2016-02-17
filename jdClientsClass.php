<?php
include_once 'includes/oauth/OAuthStore.php';
include_once 'includes/oauth/OAuthRequester.php';

class jdClientsClass {
	public $remote;
	public $conv;
	public $debug = false;
	private $geom;
	private $oauth;
	private $appID;
	private $reqHeaders;
	private $apiSecret;
	private $hostPlatformURL;
	private $oauth_callback;
	private $request;
	private $oauth_verifier_value;
	private $oauth_token_value;
	public $oauth_token_secret;
	public $cookie_val = '';

	public $access_token = '';
	public $access_token_secret = '';
	
	public $config = '';
	
	
	public function __construct($config = null) {
		$this->config = $config;
		
		ini_set('default_socket_timeout', 5*60);
		$this->hostPlatformURL = $this->config['api_catalog_url']; // Platform URL
		$this->appID = $this->config['consumer_key']; // Consumer KEY
		$this->apiSecret = $this->config['consumer_secret']; // Consumer SECRET
		$this->oauth_callback = 'oob'; //$this->config['oauth_callback']; // Oauth callback
		
		$options = array(
			'consumer_key' => $this->appID,
			'consumer_secret' => $this->apiSecret,
			'oauth_callback' => $this->oauth_callback, //'supportLandingPage', //'oob',
		);
		$this->oauth = OAuthStore::instance("Session", $options);
		//$this->oauth->addServerToken($this->appID, 'access', $this->access_token, $this->access_token_secret, '', '');
	}
	
	public function setConfig($config) {
		$this->config = $config;
		
		// Store the config in session
		$_SESSION['config_params'] = $config;
	}
	
	
	public function getRequestToken($uri) {
		$curl_options = array();
		//$curl_options[CURLOPT_HTTPHEADER] = array("Accept: application/vnd.deere.axiom.v3+json", "Content-type: application/vnd.deere.axiom.v3+json","Authorization: OAuth oauth_callback=\"OOB\", oauth_consumer_key=\"".$this->appID."\",oauth_consumer_secret=\"".$this->apiSecret."\",oauth_signature_method=\"HMAC-SHA1\"");

		$curl_options[CURLOPT_HTTPHEADER] = array("Accept: application/vnd.deere.axiom.v3+json", "Content-type: application/vnd.deere.axiom.v3+json");

		$curl_options[CURLOPT_SSL_VERIFYPEER] = false;
		$curl_options[CURLOPT_AUTOREFERER] = false;
		
		// Check if we have to use a proxy server
		if(isset($_SESSION['config_params']['use_deere_proxy']) && $_SESSION['config_params']['use_deere_proxy'] == 1 && isset($_SESSION['config_params']['deere_proxy']) && $_SESSION['config_params']['deere_proxy'] != '') {
				$curl_options[CURLOPT_PROXY] = $_SESSION['config_params']['deere_proxy'];
		}
		
		$method = "GET";
		$params = array();
		$params['oauth_callback'] = 'oob'; //"supportLandingPage"; //"OOB";
		$params['oauth_consumer_key'] = $this->appID;
		$params['oauth_consumer_secret'] = $this->apiSecret;
		$params['oauth_signature_method'] = "HMAC-SHA1";
	
		// Making a request manually
		

		$this->request = new OAuthRequester($uri, $method, $params);
		//print_r($this->request);
		$result = $this->request->doRequest(0,$curl_options);
		//print_r($result); exit;

		//$response = $result['body'];
		//print "THE RESPONSE: $response\n";
		//$jsonObj = json_decode($response,true);
		
		$items = array();
		
		$parts = preg_split('/&/',$result);

		foreach ($parts as $k=>$v) {
			//print "K: $k, V: $v\n";
			$items2 = preg_split('/=/',$v);
			//print_r($items2);
			$items[trim($items2[0])] = $items2[1];
		}
		//print_r($items); exit;
		return $items;
		

		foreach ($result as $key=>$value) {
			//print "THE KEY: $key, THE VALUE: $value\n";
			if ($key == "body") {
				$parts = preg_split('/&/',$value);

				foreach ($parts as $k=>$v) {
					//print "K: $k, V: $v\n";
					$items2 = preg_split('/=/',$v);
					//print_r($items2);
					$items[trim($items2[0])] = $items2[1];
				}
			}
		}
		//print_r($items); exit;
		$this->oauth_token_secret = $items['oauth_token_secret'];
		//return $items['oauth_token'];
		return $items;
	}


	function getVerificationCode($authorize_request_token_url)
    {
	    $request = curl_init();
	    $post_data = http_build_query(array(
	      'username' => $this->config['username'],
	      'password' => $this->config['password'],
	    ));

		$my_jd_login_url = 'https://my.deere.com/login';
	    curl_setopt($request, CURLOPT_URL, $my_jd_login_url);

	    // Don't include the headers in the output.
	    curl_setopt($request, CURLOPT_HEADER, true);

	    // Set the method to POST.
	    curl_setopt($request, CURLOPT_POST, true);

	    curl_setopt($request, CURLOPT_POSTFIELDS, $post_data);

	    // Only set CURLOPT_SSL_VERIFYPEER to FALSE (or 0) if you know
	    // and trust the site to which you are talking.
	    curl_setopt($request, CURLOPT_SSL_VERIFYPEER, 0);
	    curl_setopt($request, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($request, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
	    
	    //curl_setopt($request, CURLOPT_COOKIEJAR, 'cookies.txt');
	    curl_setopt($request, CURLOPT_COOKIEFILE, 'cookies.txt');

	    $result = curl_exec($request);
		
	    if (!$result) {
	      echo '<br><br>Received error during cURL request: (error number: ' . curl_errno($request) . ') ' . curl_error($request);
	    }

	    $status = curl_getinfo($request, CURLINFO_HTTP_CODE);
		$error = curl_error($request);
		
		$header_size = curl_getinfo($request, CURLINFO_HEADER_SIZE);
		$header = substr($result, 0, $header_size);
		$body = substr($result, $header_size);
		//print_r($header);

		preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $result, $matches);
		$cookies = array();
		foreach($matches[1] as $item) {
			parse_str($item, $cookie); 
			$cookies = array_merge($cookies, $cookie); 
		}
		//print_r($cookies);
		
		$this->cookie_val = $cookies['SMSESSION'];
		
		//curl_close($request);
		
		//echo 'Status: '.$status.'<br>';

		//print_r($result); exit;
		return $this->getVerificationCode2($request, $authorize_request_token_url);
	}

	function getVerificationCode2($request, $url) {
		//die('In verification code 2');
	    curl_setopt($request, CURLOPT_URL, $url);

	    // Don't include the headers in the output.
	    curl_setopt($request, CURLOPT_HEADER, true);
	    
	    //echo 'Cookie: '.$this->cookie_val.'<br><br>'; exit;
	    //curl_setopt($request, CURLOPT_HTTPHEADER, array("Cookie: SMSESSION=".$this->cookie_val));
	    curl_setopt($request, CURLOPT_COOKIE, 'SMSESSION='.$this->cookie_val);

	    // Only set CURLOPT_SSL_VERIFYPEER to FALSE (or 0) if you know
	    // and trust the site to which you are talking.
	    curl_setopt($request, CURLOPT_SSL_VERIFYPEER, 0);
	    curl_setopt($request, CURLOPT_SSL_VERIFYHOST, 0);
		//curl_setopt($request, CURLOPT_FOLLOWLOCATION, true);

	    $result = curl_exec($request);

	    if (!$result) {
	      echo '<br><br>Received error during cURL request: (error number: ' . curl_errno($request) . ') ' . curl_error($request);
	    }

	    $status = curl_getinfo($request, CURLINFO_HTTP_CODE);
		$error = curl_error($request);
		
		curl_close($request);
		
		//echo '<br>Status2: '.$status.'<br>';
		//print_r($result); exit;

		$dom = new DOMDocument;
		libxml_use_internal_errors(true);
		$dom->loadHTML($result);
		//$code = $dom->getElementsByTagName('code');
		$code_str = $dom->getElementsByTagName('code')->item(0);
		//print_r($code_str);
		//echo '<br>'.$code_str->textContent;
		
		return $code_str->textContent;
	}


	public function getApiCatalog($requestToken) {
		$curl_options = array();
		$curl_options[CURLOPT_HTTPHEADER] = array("Accept: application/vnd.deere.axiom.v3+json", "Content-type: application/vnd.deere.axiom.v3+json");
		
		$curl_options[CURLOPT_SSL_VERIFYPEER] = false;
		$curl_options[CURLOPT_AUTOREFERER] = false;
		
		// Check if we have to use a proxy server
		if(isset($_SESSION['config_params']['use_deere_proxy']) && $_SESSION['config_params']['use_deere_proxy'] == 1 && isset($_SESSION['config_params']['deere_proxy']) && $_SESSION['config_params']['deere_proxy'] != '') {
				$curl_options[CURLOPT_PROXY] = $_SESSION['config_params']['deere_proxy'];
		}
		
		$returnArray = array();
		$oauthRequestToken = "";
		$oauthAuthorizeRequestToken = "";
		$oauthAccessToken = "";
		//$request_token_info = $this->oauth->getRequestToken($this->hostPlatformURL);

		$url = $this->hostPlatformURL;
		$method = "GET";
		$params = array();
		if ($requestToken != "") {
			$params['oauth_token'] = $requestToken;
			$params['oauth_signature_method'] = "HMAC-SHA1";
			$params['oauth_consumer_key'] = $this->appID;
			$params['oauth_consumer_secret'] = $this->apiSecret;
			$params['oauth_callback'] = $this->oauth_callback; //'oob'; //"supportLandingPage"; //"OOB";
			$params['realm'] = "";

		}
		//print "\n--=GET API CATALOG=--\n";

		$this->request = new OAuthRequester($url, $method, $params);
		//print_r($this->request);

		$result = $this->request->doRequest(0,$curl_options);
		//print_r($result); exit;
		
		//echo $result['body']; exit;
		//$result_arr = json_decode($result['body'], true);
		$result_arr = json_decode($result, true);
		//print_r($result_arr); exit;

		//print "Response:\n";

		if(!empty($result_arr)) { // if(!empty($result)) {
			//print_r($result);
			//$response = $result['body'];
			//print "THE RESPONSE: $response\n";
			//$jsonObj = json_decode($response,true);
			//print_r($jsonObj); exit;
			//print_r($jsonObj['links']); exit;

			foreach ($result_arr['links'] as $key=>$value) {
				$rel = "";
				$uri = "";

				foreach ($value as $k=>$v) {
					//echo $k.' - '.$v.'<br><br>';
						if (trim($k) == "rel") {
							$rel = trim($v);
						} else if (trim($k) == "uri") {
							$uri = trim($v);
						}
						//print "THE KEY: $rel, THE VALUE: $value<br><br>";
					
					//$$rel = $value;
					$returnArray[$rel] = $uri;
				}
			}
			//print_r($returnArray); exit;
			//print "oauthRequestToken = $oauthRequestToken<br><br>";
			//print "oauthAuthorizeRequestToken = $oauthAuthorizeRequestToken<br><br>";
			//print "oauthAccessToken = $oauthAccessToken<br><br>";

			//$this->getRequestToken($oauthRequestToken);
			return $returnArray;

		} else {
			print "Failed fetching request token, response was: " . $oauth->getLastResponse();
		}
	}
}
?>
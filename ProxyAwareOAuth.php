<?php
// ProxyAwareOAuth.php
//	@author Gordon Wang <WangGordon@JohnDeere.com>
//	@date June 2016
//	A proxy-aware PHP 7 OAuth client

// HTTP method constants
define("OAUTH_HTTP_METHOD_GET", "GET");
define("OAUTH_HTTP_METHOD_POST", "POST");
define("OAUTH_HTTP_METHOD_PUT", "PUT");
define("OAUTH_HTTP_METHOD_DELETE", "DELETE");

class ProxyAwareOAuth
{
	private $ch; // The template curl handle for all requests
	private $consumer_key, $consumer_secret; // OAuth consumer key and secret
	private $token, $token_secret; // Stored OAuth token and token secret
	
	// Constructor
	// Initializes the curl handle template
	// @param $consumer_key -- OAuth app key
	// @param $consumer_secret -- OAuth consumer secret
	// @param $proxy -- proxy server and port
	// @param $proxy_auth -- proxy username and password
	public function __construct(string $consumer_key, string $consumer_secret, string $proxy = "", string $proxy_auth = "")
	{
		$this->ch = curl_init();
		
		$this->consumer_key = $consumer_key;
		$this->consumer_secret = $consumer_secret;

		if($proxy != "")
		{
			curl_setopt($this->ch, CURLOPT_PROXY, $proxy);
			curl_setopt($this->ch, CURLOPT_PROXYUSERPWD, $proxy_auth);
		}

		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false); // If your server has certificates, you may remove this line
		curl_setopt($this->ch, CURLOPT_ENCODING, "gzip"); // Change to match your site's encoding
	}
	
	/* Destructor -- frees the curl handle */
	public function __destruct()
	{
		if($this->ch)
			curl_close($this->ch);
	}
	
	// Get OAuth request tokens
	// @param $request_token_url -- url of request tokens
	// @param $callback_url -- oauth_callback authorization parameter
	// @param $http_method -- GET/POST/PUT/etc.
	// @return array with keys "oauth_token" and "oauth_token_secret"
	// 	 May return garbage on error.
	public function getRequestToken(string $request_token_url, string $callback_url = "oob", string $http_method = OAUTH_HTTP_METHOD_GET) : array
	{
		// Copy the curl handle template
		$ch = curl_copy_handle($this->ch);

		// Generate authorization headers, passing oauth_callback as a "query parameter" so it's included in the signature
		// However, oauth_callback must be in the authorization header
		$headers = ["Authorization: ".$this->generateAuthorizationHeaders($http_method, $request_token_url, ["oauth_callback" => $callback_url])];
		$headers[0] .= ', oauth_callback="'.urlencode($callback_url).'"'; // Add oauth_callback to header

		// Set the URL, headers; perform the HTTP request
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_URL, $request_token_url);
		$response = explode("&", curl_exec($ch));

		if(curl_error($ch))
			throw new Exception(curl_error($ch));
		curl_close($ch);

		// The curl request returns in format "oauth_token=TOKEN&oauth_token_secret=SECRET&oauth_callback_confirmed=true"
		// Convert this to $request_token = TOKEN, $request_token_secret = SECRET
		$request_token = urldecode(explode("=", $response[0])[1]);
		$request_token_secret = urldecode(explode("=", $response[1])[1]);
		return ["oauth_token" => $request_token, "oauth_token_secret" => $request_token_secret];
	}

	// Get OAuth access tokens
	// @param $access_token_url -- url of access tokens
	// @param $auth_verifier -- verifier to exchange for access token
	// @param $http_method -- GET/POST/PUT/etc.
	// @return array with keys "oauth_token" and "oauth_token_secret"
	// 	 May return garbage on error.
	public function getAccessToken(string $access_token_url, string $auth_verifier, string $http_method = OAUTH_HTTP_METHOD_GET) : array
	{
		// Copy the curl handle template
		$ch = curl_copy_handle($this->ch);

		// Generate authorization headers, passing oauth_verifier as a "query parameter" so it's included in the signature
		// However, oauth_verifier must be in the authorization header
		$headers = ["Authorization: ".$this->generateAuthorizationHeaders($http_method, $access_token_url, ["oauth_verifier" => $auth_verifier])];
		$headers[0] .= ', oauth_verifier="'.$auth_verifier.'"'; // Add oauth_verifier to header

		// Set the URL, headers; perform the HTTP request
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_URL, $access_token_url);
		$response = explode("&", curl_exec($ch));
		
		if(curl_error($ch))
			throw new Exception(curl_error($ch));
		curl_close($ch);

		// The curl request returns in format "oauth_token=TOKEN&oauth_token_secret=SECRET"
		// Convert this to $access_token = TOKEN, $access_token_secret = SECRET
		$access_token = urldecode(explode("=", $response[0])[1]);
		$access_token_secret = urldecode(explode("=", $response[1])[1]);
		return ["oauth_token" => $access_token, "oauth_token_secret" => $access_token_secret];
	}

	// Access an OAuth protected resource with GET
	// @param $protected_resource_url -- base url of resource
	// @param $http_headers -- headers for request, excluding Auth
	// @param $extra_parameters -- query parameters
	// @return the server response
	public function get(string $protected_resource_url, array $http_headers = [], bool $return_headers = false, array $extra_parameters = [])
	{
		// Copy the curl handle template
		$ch = curl_copy_handle($this->ch);

		// Build the request headers
		$headers = [];
		foreach($http_headers as $key => $header)
			$headers[] = $key.": ".$header;
		$headers[] = "Authorization: ".$this->generateAuthorizationHeaders(OAUTH_HTTP_METHOD_GET, $protected_resource_url, $extra_parameters);

		// Append query parameters to the url
		if(!empty($extra_parameters))
		{
			$protected_resource_url .= "?";
			foreach($extra_parameters as $key => $parameter)
				$protected_resource_url .= urlencode($key)."=".urlencode($parameter)."&";
			$protected_resource_url = substr($protected_resource_url, 0, -1);
		}

		// Set URL, headers; perform the HTTP request
		if($return_headers)
			curl_setopt($ch, CURLOPT_HEADER, true);

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_URL, $protected_resource_url);
		$response = curl_exec($ch);

		if($return_headers)
		{
			$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
			$header = substr($response, 0, $header_size);
			$body = substr($response, $header_size);
		}

		if(curl_error($ch))
			throw new Exception(curl_error($ch));
		curl_close($ch);

		if($return_headers)
			return ["header" => $header, "body" => $body];
		else
			return $response;
	}

	// POST to an OAuth protected resource
	// @param $uri -- base uri of resource
	// @param $body -- body of POST request
	// @param $http_headers -- headers for request, excluding Auth
	// @return the server response
	public function post(string $uri, string $body, array $http_headers) : string
	{
		// Copy the curl handle template
		$ch = curl_copy_handle($this->ch);

		// Build the request headers
		$headers = [];
		foreach($http_headers as $key => $header)
			$headers[] = $key.": ".$header;
		$headers[] = "Authorization: ".$this->generateAuthorizationHeaders(OAUTH_HTTP_METHOD_POST, $uri);

		// Set headers, url, POST fields; perform request
		curl_setopt($ch, CURLOPT_HEADER, true); // Return the response header too
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		curl_setopt($ch, CURLOPT_URL, $uri);
		$response = curl_exec($ch);
		curl_close($ch);

		if($response === false)
			throw new Exception("POST to $uri failed.");

		return $response;
	}

	// PUT to an OAuth protected resource
	// @param $uri -- base uri of resource
	// @param $body -- body of PUT request
	// @param $http_headers -- headers for request, excluding Auth
	// @return the server response
	public function put(string $uri, string $body, array $http_headers) : string
	{
		// Copy the curl handle template
		$ch = curl_copy_handle($this->ch);

		// Build the request headers
		$headers = [];
		foreach($http_headers as $key => $header)
			$headers[] = $key.": ".$header;
		$headers[] = "Authorization: ".$this->generateAuthorizationHeaders(OAUTH_HTTP_METHOD_PUT, $uri);

		// Set headers, url, PUT body; perform request
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($ch, CURLOPT_HEADER, true); // Return response header too
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		curl_setopt($ch, CURLOPT_URL, $uri);
		$response = curl_exec($ch);
		curl_close($ch);

		if($response === false)
			throw new Exception("PUT to $uri failed.");

		return $response;
	}

	// DELETE an OAuth protected resource
	// @param $uri -- base uri of resource
	// @param $http_headers -- headers for request, excluding Auth
	// @return the server response
	public function delete(string $uri, array $http_headers) : string
	{
		// Copy the curl handle template
		$ch = curl_copy_handle($this->ch);

		// Build the request headers
		$headers = [];
		foreach($http_headers as $key => $header)
			$headers[] = $key.": ".$header;
		$headers[] = "Authorization: ".$this->generateAuthorizationHeaders(OAUTH_HTTP_METHOD_DELETE, $uri);

		// Set headers, url; perform request
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($ch, CURLOPT_HEADER, true); // Return response header too
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		curl_setopt($ch, CURLOPT_URL, $uri);
		$response = curl_exec($ch);
		curl_close($ch);

		if($response === false)
			throw new Exception("Delete $uri failed.");

		return $response;
	}

	// Set the token to use for requests
	// @params token and token secret
	public function setToken(string $token, string $secret)
	{
		$this->token = $token;
		$this->token_secret = $secret;
	}

	// Generates an OAuth HMAC-SHA1 signature
	// @param $url -- the url portion of the signature base string
	// @param $http_method -- the HTTP method in the signature base string
	// @param $params -- parameters to append to the signature base string
	// @return OAuth signature string; always returns successfully
	private function generateSignature(string $url, string $http_method, array $params) : string
	{
		// Sort params alphabetically
		ksort($params);

		// Generate the signature base string
		$baseString = $http_method."&".rawurlencode($url)."&";
		foreach($params as $key => $value)
			$baseString .= rawurlencode($key.'='.$value."&");
		$baseString = rtrim($baseString, "%26"); // Remove extra "&" fromm end

		// Generate the signature signing key
		$signatureKey = rawurlencode($this->consumer_secret)."&";
		if(!empty($this->token_secret))
			$signatureKey .= rawurlencode($this->token_secret);

		// Hash the base string with the signature key, convert to base64
		$signature = base64_encode(hash_hmac("sha1", $baseString, $signatureKey, true));
		return urlencode($signature);
	}

	// Generates an OAuth authorization header for a request
	// @param $http_method -- the HTTP method of the request
	// @param $protected_resource_url -- the url of the request
	// @param $query_parameters -- extra parameters for the signature
	// @return OAuth header string; always returns successfully
	private function generateAuthorizationHeaders(string $http_method, string $protected_resource_url, array $query_parameters = []) : string
	{
		// Create the OAuth authorization header parameters
		$params = [
		"oauth_consumer_key" => $this->consumer_key,
		"oauth_nonce" => $this->generateNonce(),
		"oauth_signature_method" => "HMAC-SHA1",
		"oauth_timestamp" => strval(time()),
		"oauth_version" => "1.0" ];

		if(!empty($this->token))
			$params["oauth_token"] = $this->token;

		// Create the authorization headers from the parameters
		$headers = "OAuth ";
		foreach($params as $key => $value)
			$headers .= $key.'="'.$value.'", ';
		
		// Generate a signature, including any query parameters
		foreach($query_parameters as $key => $value)
			$params[urlencode($key)] = urlencode($value);
		$signature = $this->generateSignature($protected_resource_url, $http_method, $params);

		// Append the signature to authorization headers
		$headers .= 'oauth_signature="'.$signature.'"';
		return $headers;
	}
	
	// Generates a nonce; unix timestamp concatenated with 10 char random string
	// This format gives 107 billion unique nonces per second
	// @return OAuth nonce
	private function generateNonce() : string
	{
		$suffix = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 10);
		return time().$suffix;
	}
}

?>
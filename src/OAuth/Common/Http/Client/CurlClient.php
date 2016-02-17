<?php

namespace OAuth\Common\Http\Client;

use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Http\Uri\UriInterface;

/**
 * Client implementation for cURL
 */
class CurlClient extends AbstractClient
{
    /**
     * If true, explicitly sets cURL to use SSL version 3. Use this if cURL
     * compiles with GnuTLS SSL.
     *
     * @var bool
     */
    private $forceSSL3 = false;

    /**
     * Additional parameters (as `key => value` pairs) to be passed to `curl_setopt`
     *
     * @var array
     */
    private $parameters = array();

    /**
     * Additional `curl_setopt` parameters
     *
     * @param array $parameters
     */
    public function setCurlParameters(array $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @param bool $force
     *
     * @return CurlClient
     */
    public function setForceSSL3($force)
    {
        $this->forceSSL3 = $force;

        return $this;
    }

    /**
     * Any implementing HTTP providers should send a request to the provided endpoint with the parameters.
     * They should return, in string form, the response body and throw an exception on error.
     *
     * @param UriInterface $endpoint
     * @param mixed        $requestBody
     * @param array        $extraHeaders
     * @param string       $method
     *
     * @return string
     *
     * @throws TokenResponseException
     * @throws \InvalidArgumentException
     */
    public function retrieveResponse(
        UriInterface $endpoint,
        $requestBody,
        array $extraHeaders = array(),
        $method = 'POST'
    ) {
		//echo '<br><br>Retrieve Response:<br>';
		//echo $endpoint.'<br>';
		//print_r($requestBody);
		//print_r($extraHeaders);
		//echo '<br><br>';
		//exit;
		
        // Normalize method name
        $method = strtoupper($method);

        $this->normalizeHeaders($extraHeaders);

        if ($method === 'GET' && !empty($requestBody)) {
            throw new \InvalidArgumentException('No body expected for "GET" request.');
        }

        if (!isset($extraHeaders['Content-Type']) && $method === 'POST' && is_array($requestBody)) {
            $extraHeaders['Content-Type'] = 'Content-Type: application/x-www-form-urlencoded';
        }

        $extraHeaders['Host']       = 'Host: '.$endpoint->getHost();
        $extraHeaders['Connection'] = 'Connection: close';
		//print_r($extraHeaders); exit;
		
        $ch = curl_init();
		
		$accessTokenUri = $endpoint->getAbsoluteUri();
		//$accessTokenUri .= '?oauth_token='.$requestBody['oauth_token'].'&oauth_verifier='.$requestBody['oauth_verifier'];
		if(isset($requestBody['oauth_verifier'])) {
			$accessTokenUri .= '?oauth_verifier='.$requestBody['oauth_verifier'];
		}
		
		//echo $accessTokenUri; exit;
		
        //curl_setopt($ch, CURLOPT_URL, $endpoint->getAbsoluteUri());
				curl_setopt($ch, CURLOPT_URL, $accessTokenUri);
				// Check if we have to use a proxy server
			  if(isset($_SESSION['config_params']['use_deere_proxy']) && $_SESSION['config_params']['use_deere_proxy'] == 1 && isset($_SESSION['config_params']['deere_proxy']) && $_SESSION['config_params']['deere_proxy'] != '') {
			    curl_setopt($ch, CURLOPT_PROXY, $_SESSION['config_params']['deere_proxy']);
			  }
		
        if ($method === 'POST' || $method === 'PUT') {
            if ($requestBody && is_array($requestBody)) {
                $requestBody = http_build_query($requestBody, '', '&');
            }

            if ($method === 'PUT') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            } else {
                curl_setopt($ch, CURLOPT_POST, true);
            }

            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        if ($this->maxRedirects > 0) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, $this->maxRedirects);
        }

        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
		curl_setopt($ch, CURLOPT_HEADER, false);
		//curl_setopt($ch, CURLOPT_HEADER, true);
		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_AUTOREFERER, false);
		
		
        curl_setopt($ch, CURLOPT_HTTPHEADER, $extraHeaders);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);

        foreach ($this->parameters as $key => $value) {
            curl_setopt($ch, $key, $value);
        }
		
        if ($this->forceSSL3) {
            curl_setopt($ch, CURLOPT_SSLVERSION, 3);
        }

        $response     = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		//echo 'SEE: '.$responseCode.'<br><br>';
		//echo $response; exit;
		
        if (false === $response) {
            $errNo  = curl_errno($ch);
            $errStr = curl_error($ch);
            curl_close($ch);
            if (empty($errStr)) {
                throw new TokenResponseException('Failed to request resource.', $responseCode);
            }
            throw new TokenResponseException('cURL Error # '.$errNo.': '.$errStr, $responseCode);
        }

        curl_close($ch);

        return $response;
    }
	
	/**
     * Any implementing HTTP providers should send a request to the provided endpoint with the parameters.
     * They should return, in string form, the response body and throw an exception on error.
     *
     * @param UriInterface $endpoint
     * @param mixed        $requestBody
     * @param array        $extraHeaders
     * @param string       $method
     *
     * @return string
     *
     * @throws TokenResponseException
     * @throws \InvalidArgumentException
     */
    public function retrieveResponseWithHeaders(
        UriInterface $endpoint,
        $requestBody,
        array $extraHeaders = array(),
        $method = 'POST'
    ) {
		//echo '<br><br>Retrieve Response:<br>';
		//echo $endpoint.'<br>';
		//print_r($requestBody);
		//print_r($extraHeaders);
		//echo '<br><br>';
		//exit;
		
        // Normalize method name
        $method = strtoupper($method);

        $this->normalizeHeaders($extraHeaders);

        if ($method === 'GET' && !empty($requestBody)) {
            throw new \InvalidArgumentException('No body expected for "GET" request.');
        }

        if (!isset($extraHeaders['Content-Type']) && $method === 'POST' && is_array($requestBody)) {
            $extraHeaders['Content-Type'] = 'Content-Type: application/x-www-form-urlencoded';
        }

        $extraHeaders['Host']       = 'Host: '.$endpoint->getHost();
        $extraHeaders['Connection'] = 'Connection: close';
		//print_r($extraHeaders); exit;
		
        $ch = curl_init();
		
		$accessTokenUri = $endpoint->getAbsoluteUri();
		//$accessTokenUri .= '?oauth_token='.$requestBody['oauth_token'].'&oauth_verifier='.$requestBody['oauth_verifier'];
		if(isset($requestBody['oauth_verifier'])) {
			$accessTokenUri .= '?oauth_verifier='.$requestBody['oauth_verifier'];
		}
		
		//echo $accessTokenUri; exit;
		
        //curl_setopt($ch, CURLOPT_URL, $endpoint->getAbsoluteUri());
				curl_setopt($ch, CURLOPT_URL, $accessTokenUri);
				// Check if we have to use a proxy server
			  if(isset($_SESSION['config_params']['use_deere_proxy']) && $_SESSION['config_params']['use_deere_proxy'] == 1 && isset($_SESSION['config_params']['deere_proxy']) && $_SESSION['config_params']['deere_proxy'] != '') {
			    curl_setopt($ch, CURLOPT_PROXY, $_SESSION['config_params']['deere_proxy']);
			  }
		
        if ($method === 'POST' || $method === 'PUT') {
            if ($requestBody && is_array($requestBody)) {
                $requestBody = http_build_query($requestBody, '', '&');
            }

            if ($method === 'PUT') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            } else {
                curl_setopt($ch, CURLOPT_POST, true);
            }

            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        if ($this->maxRedirects > 0) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, $this->maxRedirects);
        }

        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
		//curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_HEADER, true); // Headers are required in response
		
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_AUTOREFERER, false);
		
        curl_setopt($ch, CURLOPT_HTTPHEADER, $extraHeaders);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);

        foreach ($this->parameters as $key => $value) {
            curl_setopt($ch, $key, $value);
        }
		
        if ($this->forceSSL3) {
            curl_setopt($ch, CURLOPT_SSLVERSION, 3);
        }

        $response     = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		//echo 'SEE: '.$responseCode.'<br><br>';
		//echo $response; exit;
		
		$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$response_headers = substr($response, 0, $header_size);
		$response_body = substr($response, $header_size);
		
		// Consider the json response starting from "{"
		$response_body = substr($response_body, strpos($response_body, "{"));
		
        if (false === $response) {
            $errNo  = curl_errno($ch);
            $errStr = curl_error($ch);
            curl_close($ch);
            if (empty($errStr)) {
                throw new TokenResponseException('Failed to request resource.', $responseCode);
            }
            throw new TokenResponseException('cURL Error # '.$errNo.': '.$errStr, $responseCode);
        }

        curl_close($ch);

        //return $response;
		return array('response' => $response_body, 'response_headers' => $response_headers);
    }
	
	// Custom function by shasi to retrieve response from curl call
	public function retrieve_response(
        //UriInterface $endpoint, // commented by shasi
		$endpoint,
        $requestBody,
        array $extraHeaders = array(),
        $method = 'POST'
    ) {
		//echo '<br><br>Retrieve Response:<br>';
		//echo $endpoint.'<br>';
		//print_r($requestBody);
		//print_r($extraHeaders);
		//echo '<br><br>';
		//exit;
		
        // Normalize method name
        $method = strtoupper($method);

        $this->normalizeHeaders($extraHeaders);

        if ($method === 'GET' && !empty($requestBody)) {
            throw new \InvalidArgumentException('No body expected for "GET" request.');
        }

        if (!isset($extraHeaders['Content-Type']) && $method === 'POST' && is_array($requestBody)) {
            $extraHeaders['Content-Type'] = 'Content-Type: application/x-www-form-urlencoded';
        }

		// Hard coded by shasi
		$endpoint_host = $endpoint->getHost();
		
        $extraHeaders['Host']       = 'Host: '.$endpoint_host; //$endpoint->getHost(); // modified by shasi
        $extraHeaders['Connection'] = 'Connection: close';
		//print_r($extraHeaders); exit;
		
        $ch = curl_init();
		
		$accessTokenUri = $endpoint->getAbsoluteUri();
		//$accessTokenUri .= '?oauth_token='.$requestBody['oauth_token'].'&oauth_verifier='.$requestBody['oauth_verifier'];
		if(isset($requestBody['oauth_verifier'])) {
			$accessTokenUri .= '?oauth_verifier='.$requestBody['oauth_verifier'];
		}
		
		//echo $accessTokenUri; exit;
		
        //curl_setopt($ch, CURLOPT_URL, $endpoint->getAbsoluteUri());
		curl_setopt($ch, CURLOPT_URL, $accessTokenUri);
		
        if ($method === 'POST' || $method === 'PUT') {
            if ($requestBody && is_array($requestBody)) {
                $requestBody = http_build_query($requestBody, '', '&');
            }

            if ($method === 'PUT') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            } else {
                curl_setopt($ch, CURLOPT_POST, true);
            }

            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        if ($this->maxRedirects > 0) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, $this->maxRedirects);
        }

        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
		curl_setopt($ch, CURLOPT_HEADER, false);
		//curl_setopt($ch, CURLOPT_HEADER, true);
		
        curl_setopt($ch, CURLOPT_HTTPHEADER, $extraHeaders);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);

        foreach ($this->parameters as $key => $value) {
            curl_setopt($ch, $key, $value);
        }
		
        if ($this->forceSSL3) {
            curl_setopt($ch, CURLOPT_SSLVERSION, 3);
        }

        $response     = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		//echo 'SEE: '.$responseCode.'<br><br>';
		//echo $response; exit;
		
        if (false === $response) {
            $errNo  = curl_errno($ch);
            $errStr = curl_error($ch);
            curl_close($ch);
            if (empty($errStr)) {
                throw new TokenResponseException('Failed to request resource.', $responseCode);
            }
            throw new TokenResponseException('cURL Error # '.$errNo.': '.$errStr, $responseCode);
        }

        curl_close($ch);

        return $response;
    }
}

<?php

namespace OAuth\OAuth1\Service;

use OAuth\OAuth1\Signature\SignatureInterface;
use OAuth\OAuth1\Token\StdOAuth1Token;
use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Http\Uri\Uri;
use OAuth\Common\Consumer\CredentialsInterface;
use OAuth\Common\Http\Uri\UriInterface;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Http\Client\ClientInterface;

class Deere extends AbstractService
{
    public function __construct(
        CredentialsInterface $credentials,
        ClientInterface $httpClient,
        TokenStorageInterface $storage,
        SignatureInterface $signature,
        UriInterface $baseApiUri = null
		//$authorization_headers = null // Added by shasi
    ) {
        parent::__construct($credentials, $httpClient, $storage, $signature, $baseApiUri);

        if (null === $baseApiUri) {
            $this->baseApiUri = new Uri('https://api.deere.com/platform/');
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getRequestTokenEndpoint()
    {
        return new Uri('https://api.deere.com/platform/oauth/request_token');
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthorizationEndpoint()
    {
        return new Uri('https://my.deere.com/consentToUseOfData');
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenEndpoint()
    {
        return new Uri('https://api.deere.com/platform/oauth/access_token');
    }

    /**
     * {@inheritdoc}
     */
    protected function parseRequestTokenResponse($responseBody)
    {
		parse_str($responseBody, $data);

        if (null === $data || !is_array($data)) {
            throw new TokenResponseException('Unable to parse response.');
        } elseif (!isset($data['oauth_callback_confirmed']) || $data['oauth_callback_confirmed'] !== 'true') {
            throw new TokenResponseException('Error in retrieving token.');
        }

        return $this->parseAccessTokenResponse($responseBody);
    }

    /**
     * {@inheritdoc}
     */
    protected function parseAccessTokenResponse($responseBody)
    {
		parse_str($responseBody, $data);

        if (null === $data || !is_array($data)) {
            throw new TokenResponseException('Unable to parse response.');
        } elseif (isset($data['error'])) {
            throw new TokenResponseException('Error in retrieving token: "' . $data['error'] . '"');
        }

        $token = new StdOAuth1Token();

        $token->setRequestToken($data['oauth_token']);
        $token->setRequestTokenSecret($data['oauth_token_secret']);
        $token->setAccessToken($data['oauth_token']);
        $token->setAccessTokenSecret($data['oauth_token_secret']);

        $token->setEndOfLife(StdOAuth1Token::EOL_NEVER_EXPIRES);
        unset($data['oauth_token'], $data['oauth_token_secret']);
        $token->setExtraParams($data);

        return $token;
    }
	
	public function requestAccessToken($token, $verifier, $tokenSecret = null)
    {
        if (is_null($tokenSecret)) {
            $storedRequestToken = $this->storage->retrieveAccessToken($this->service());
            $tokenSecret = $storedRequestToken->getRequestTokenSecret();
        }
        $this->signature->setTokenSecret($tokenSecret);

        $this->setOauthVerifier($verifier);

        $bodyParams = null;
        $authorizationHeader = array(
            'Authorization' => $this->buildAuthorizationHeaderForAPIRequest(
                'POST',
                $this->getAccessTokenEndpoint(),
                $this->storage->retrieveAccessToken($this->service())
            )
        );
		
        $headers = array_merge($authorizationHeader, $this->getExtraOAuthHeaders());
       // print_r( $headers); exit;
        $responseBody = $this->httpClient->retrieveResponse($this->getAccessTokenEndpoint(), $bodyParams, $headers);

        $this->setOauthVerifier(null);
        $token = $this->parseAccessTokenResponse($responseBody);
        $this->storage->storeAccessToken($this->service(), $token);

        return $token;
    }
	
	// Custom function written for retrieving the request headers
	public function getAuthorizationHeaders()
    {
        $bodyParams = null;
        $authorizationHeader = array(
            'Authorization' => $this->buildAuthorizationHeaderForAPIRequest(
                'POST',
                $this->getAccessTokenEndpoint(),
                $this->storage->retrieveAccessToken($this->service())
            )
        );
		
        $headers = array_merge($authorizationHeader, $this->getExtraOAuthHeaders());
        // Very useful to print and look at later stage
		//print_r( $headers); exit;
		
        return $headers;
    }
	
	// Overriding the request method here, from AbstractService.php
	public function request($path, $method = 'GET', $body = null, array $extraHeaders = array())
    {
		//echo 'Method: '.$method.'<br>Body: '.$body; exit;
		$uri = $this->determineRequestUriFromPath($path, $this->baseApiUri);

        /** @var $token StdOAuth1Token */

        $token = $this->storage->retrieveAccessToken($this->service());
        
		//$extraHeaders = array( "Accept" =>"application/vnd.deere.axiom.v3+json");
		
		// Specify the default headers in the request
		if(count($extraHeaders) == 0) {
			$extraHeaders = array( "Accept" =>"application/vnd.deere.axiom.v3+json");
		}
		/*else {
			//$extraHeaders["Accept"] = "application/octet-stream";
		}*/
		
        $extraHeaders = array_merge($this->getExtraApiHeaders(), $extraHeaders);
        $authorizationHeader = array(
            'Authorization' => $this->buildAuthorizationHeaderForAPIRequest($method, $uri, $token, $body)
        );
        $headers = array_merge($authorizationHeader, $extraHeaders);
		//print_r($headers); exit;
		
		return $this->httpClient->retrieveResponse($uri, $body, $headers, $method);
		
		/*$client = new StreamClient();
		$response = $client->retrieveResponse(
            $uri,
            $body,
            $headers,
            $method
        );*/
		//print_r($response); exit;
		//return $response;
	}
	
	// Overriding the request method here, from AbstractService.php, to retrieve response with response headers
	public function requestResponseWithHeaders($path, $method = 'GET', $body = null, array $extraHeaders = array())
    {
		//echo 'Method: '.$method.'<br>Body: '.$body; exit;
		$uri = $this->determineRequestUriFromPath($path, $this->baseApiUri);

        /** @var $token StdOAuth1Token */

        $token = $this->storage->retrieveAccessToken($this->service());
        
		//$extraHeaders = array( "Accept" =>"application/vnd.deere.axiom.v3+json");
		
		// Specify the default headers in the request
		if(count($extraHeaders) == 0) {
			$extraHeaders = array( "Accept" =>"application/vnd.deere.axiom.v3+json");
		}
		/*else {
			//$extraHeaders["Accept"] = "application/octet-stream";
		}*/
		
        $extraHeaders = array_merge($this->getExtraApiHeaders(), $extraHeaders);
        $authorizationHeader = array(
            'Authorization' => $this->buildAuthorizationHeaderForAPIRequest($method, $uri, $token, $body)
        );
		
		//$this->authorization_headers = $this->buildAuthorizationHeaderForAPIRequest($method, $uri, $token, $body);
		//echo $this->authorization_headers; exit;
		
        $headers = array_merge($authorizationHeader, $extraHeaders);
		//print_r($headers); exit;
		
		//return $this->httpClient->retrieveResponse($uri, $body, $headers, $method);
		
		/*$client = new StreamClient();
		$response = $client->retrieveResponse(
            $uri,
            $body,
            $headers,
            $method
        );*/
		//print_r($response); exit;
		//return $response;
        
		return $this->httpClient->retrieveResponseWithHeaders($uri, $body, $headers, $method);
	}
	
	// Custom function to fetch the Authorization header based on the call
	public function getRequestAuthorizationHeaders($path, $method = 'GET', $body = null) {
		$uri = $this->determineRequestUriFromPath($path, $this->baseApiUri);
		$token = $this->storage->retrieveAccessToken($this->service());
		$authorization_headers = $this->buildAuthorizationHeaderForAPIRequest($method, $uri, $token, $body);
		
		return $authorization_headers;
	}
}
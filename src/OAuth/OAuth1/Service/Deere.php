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
use OAuth\Common\Exception\Exception;

class Deere extends AbstractService
{
	const ENDPOINT_AUTHENTICATE = '';
    const ENDPOINT_AUTHORIZE    = '';

    protected $authorizationEndpoint   = self::ENDPOINT_AUTHORIZE;

    public function __construct(
        CredentialsInterface $credentials,
        ClientInterface $httpClient,
        TokenStorageInterface $storage,
        SignatureInterface $signature,
        UriInterface $baseApiUri = null
    ) {
		parent::__construct($credentials, $httpClient, $storage, $signature, $baseApiUri);

        if (null === $baseApiUri) {
            $this->baseApiUri = new Uri($_SESSION['config_params']['api_catalog_url']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestTokenEndpoint()
    {
        return new Uri($_SESSION['config_params']['oauthRequestToken']);
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthorizationEndpoint()
    {
        if ($this->authorizationEndpoint != self::ENDPOINT_AUTHENTICATE
        && $this->authorizationEndpoint != self::ENDPOINT_AUTHORIZE) {
            $this->authorizationEndpoint = self::ENDPOINT_AUTHENTICATE;
        }
        return new Uri($this->authorizationEndpoint);
    }

    /**
     * @param string $authorizationEndpoint
     *
     * @throws Exception
     */
    public function setAuthorizationEndpoint($endpoint)
    {
        if ($endpoint != self::ENDPOINT_AUTHENTICATE && $endpoint != self::ENDPOINT_AUTHORIZE) {
            throw new Exception(
                sprintf("'%s' is not a correct Deere authorization endpoint.", $endpoint)
            );
        }
        $this->authorizationEndpoint = $endpoint;
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenEndpoint()
    {
		return new Uri($_SESSION['config_params']['oauthAccessToken']);
    }
	
	// Return the access token endpoint from Session, passed as parameter
	public function get_access_token_endpoint($url)
    {
		return new Uri($url);
    }

    /**
     * {@inheritdoc}
     */
    protected function parseRequestTokenResponse($responseBody)
    {
		//print_r($responseBody); exit;
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
		//print_r($responseBody); exit;
		
        if (null === $data || !is_array($data)) {
            throw new TokenResponseException('Unable to parse response: ' . $responseBody);
        } elseif (isset($data['error'])) {
            throw new TokenResponseException('Error in retrieving token: "' . $data['error'] . '"');
        } elseif (!isset($data["oauth_token"]) || !isset($data["oauth_token_secret"])) {
            throw new TokenResponseException('Invalid response. OAuth Token data not set: ' . $responseBody);
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
	
	// Overriding the request method here, from AbstractService.php, to retrieve response with response headers
	public function requestResponseWithHeaders($path, $method = 'GET', $body = null, array $extraHeaders = array())
    {
		//echo 'Method: '.$method.'<br>Body: '.$body; exit;
		$uri = $this->determineRequestUriFromPath($path, $this->baseApiUri);

        /** @var $token StdOAuth1Token */

        //$token = $this->storage->retrieveAccessToken($this->service());
		// Fetch the access token object from session
		$token = $_SESSION['access_token_object'];
        $token = unserialize($token);
		
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
	
	// Custom function to fetch the Authorization header, based on the call
	public function getRequestAuthorizationHeaders($path, $method = 'GET', $body = null) {
		$uri = $this->determineRequestUriFromPath($path, $this->baseApiUri);
		//$token = $this->storage->retrieveAccessToken($this->service());
		// Fetch the access token object from session
		$token = $_SESSION['access_token_object'];
		$token = unserialize($token);
		
		$authorization_headers = $this->buildAuthorizationHeaderForAPIRequest($method, $uri, $token, $body);
		
		return $authorization_headers;
	}
}

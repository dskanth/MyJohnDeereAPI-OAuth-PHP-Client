<?php

namespace OAuth\OAuth1\Service;

use OAuth\Common\Consumer\CredentialsInterface;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Http\Client\ClientInterface;
use OAuth\Common\Http\Uri\UriInterface;
use OAuth\OAuth1\Signature\SignatureInterface;
use OAuth\OAuth1\Token\TokenInterface;
use OAuth\OAuth1\Token\StdOAuth1Token;
use OAuth\Common\Service\AbstractService as BaseAbstractService;

abstract class AbstractService extends BaseAbstractService implements ServiceInterface
{
    /** @const OAUTH_VERSION */
    const OAUTH_VERSION = 1;

    /** @var SignatureInterface */
    protected $signature;

    /** @var UriInterface|null */
    protected $baseApiUri;

    /**
     * {@inheritDoc}
     */
    public function __construct(
        CredentialsInterface $credentials,
        ClientInterface $httpClient,
        TokenStorageInterface $storage,
        SignatureInterface $signature,
        UriInterface $baseApiUri = null
    ) {
        parent::__construct($credentials, $httpClient, $storage);

        $this->signature = $signature;
        $this->baseApiUri = $baseApiUri;

        $this->signature->setHashingAlgorithm($this->getSignatureMethod());
    }

    /**
     * {@inheritDoc}
     */
    public function requestRequestToken()
    {
		$authorizationHeader = array('Authorization' => $this->buildAuthorizationHeaderForTokenRequest());
        $headers = array_merge($authorizationHeader, $this->getExtraOAuthHeaders());

        $responseBody = $this->httpClient->retrieveResponse($this->getRequestTokenEndpoint(), array(), $headers);

        $token = $this->parseRequestTokenResponse($responseBody);
        $this->storage->storeAccessToken($this->service(), $token);
		return $responseBody;
        return $token;
    }
	
	// Custom function by shasi
	public function store_access_token($service, $token) {
		$this->storage->store_access_token($service, $token);
	}
	
	/**
     * {@inheritdoc}
     */
    public function getAuthorizationUri(array $additionalParameters = array())
    {
        // Build the url
        $url = clone $this->getAuthorizationEndpoint();
        foreach ($additionalParameters as $key => $val) {
            $url->addToQuery($key, $val);
        }

        return $url;
    }

    /**
     * {@inheritDoc}
     */
    public function requestAccessToken($token, $verifier, $tokenSecret = null)
    {
		//echo 'Request Access Token'; exit;
		echo '<br>'.$token, ', ', $verifier, ', ', $tokenSecret.'<br><br>'; //exit;
		
        if (is_null($tokenSecret)) {
            $storedRequestToken = $this->storage->retrieveAccessToken($this->service());
			
            $tokenSecret = $storedRequestToken->getRequestTokenSecret();
        }
        $this->signature->setTokenSecret($tokenSecret);
		
        $bodyParams = array(
            'oauth_verifier' => $verifier,
			//'verifier' => $verifier
        );
		
        $authorizationHeader = array(
            'Authorization' => $this->buildAuthorizationHeaderForAPIRequest(
                'POST',
                $this->getAccessTokenEndpoint(),
                $this->storage->retrieveAccessToken($this->service()),
                $bodyParams
            )
        );
		
        $headers = array_merge($authorizationHeader, $this->getExtraOAuthHeaders());
		//print_r($headers); exit;
		
        $responseBody = $this->httpClient->retrieveResponse($this->getAccessTokenEndpoint(), $bodyParams, $headers);
		//print_r($responseBody); exit;
		
        $token = $this->parseAccessTokenResponse($responseBody);
        $this->storage->storeAccessToken($this->service(), $token);

        return $token;
    }
	
	// custom function by shasi to request access token
	public function request_access_token($token, $verifier, $tokenSecret = null, $access_token_url)
    {
		//echo 'Request Access Token'; exit;
		//echo '<br>Oauth Token: '.$token, '<br>Verifier: ', $verifier, '<br>Token Secret: ', $tokenSecret.'<br>'; //exit;
		
        /*if (is_null($tokenSecret)) {
            $storedRequestToken = $this->storage->retrieveAccessToken($this->service());
			
            $tokenSecret = $storedRequestToken->getRequestTokenSecret();
        }*/
        $this->signature->setTokenSecret($tokenSecret);
		
		/*$myFile = "log.txt";
		$fh = fopen($myFile, 'w') or die("can’t open file");
		$stringData = $tokenSecret."\n";
		fwrite($fh, $stringData);
		fclose($fh);*/
		
        $bodyParams = array(
            'oauth_verifier' => $verifier,
			//'verifier' => $verifier
        );
		
		$authorizationHeader = array(
            'Authorization' => $this->build_authorization_header_for_APIRequest(
                'POST',
                //$this->getAccessTokenEndpoint(),
				$this->get_access_token_endpoint($access_token_url),
				$this->storage->retrieve_access_token($this->service()),
                $bodyParams
            )
        );
		
        $headers = array_merge($authorizationHeader, $this->getExtraOAuthHeaders());
		//$headers['Accept'] = 'application/vnd.deere.axiom.v3+json';
		
		$access_token_endpoint = $this->get_access_token_endpoint($access_token_url); //$this->getAccessTokenEndpoint();
		
		// Added by shasi
		//$access_token_endpoint .= '?oauth_token='.$token.'&oauth_verifier='.$verifier;
		
		//echo '<br>Access Token endpoint:<br>'.$access_token_endpoint.'<br>';
		
		//echo '<br>Headers:<br><br>';
		//print_r($headers); //exit;
		
        $responseBody = $this->httpClient->retrieveResponse($access_token_endpoint, $bodyParams, $headers);
		//print_r($responseBody); //exit;
		
        $token = $this->parseAccessTokenResponse($responseBody);
		//print_r($token); exit;
		
		// Store the access token in the session
		$_SESSION['access_token_object'] = serialize($token);
		
		// Store the access token in a text file
		/*$myFile = "access_token.txt";
		$fh = fopen($myFile, 'w') or die("can’t open file");
		fwrite($fh, serialize($token));
		fclose($fh);*/
	
        $this->storage->storeAccessToken($this->service(), $token);

        return $responseBody; //$token;
    }

    /**
     * Refreshes an OAuth1 access token
     * @param  TokenInterface $token
     * @return TokenInterface $token
     */
    public function refreshAccessToken(TokenInterface $token)
    {
    }

    /**
     * Sends an authenticated API request to the path provided.
     * If the path provided is not an absolute URI, the base API Uri (must be passed into constructor) will be used.
     *
     * @param string|UriInterface $path
     * @param string              $method       HTTP method
     * @param array               $body         Request body if applicable (key/value pairs)
     * @param array               $extraHeaders Extra headers if applicable.
     *                                          These will override service-specific any defaults.
     *
     * @return string
     */
    public function request($path, $method = 'GET', $body = null, array $extraHeaders = array())
    {
		$uri = $this->determineRequestUriFromPath($path, $this->baseApiUri);
		
		/** @var $token StdOAuth1Token */
        //$token = $this->storage->retrieveAccessToken($this->service());
		
		// Fetch the access token object from the text file
		//$token = file_get_contents('access_token.txt');
		
		// Fetch the access token object from session
		$token = $_SESSION['access_token_object']; //$_SESSION['config_params']['access_token_object'];
		
		//$token = $config['access_token_object'];
		$token = unserialize($token);
		
		// Added by shasi, to specify the default headers in the request
		if(count($extraHeaders) == 0) {
			$extraHeaders = array( "Accept" =>"application/vnd.deere.axiom.v3+json");
		}
		
        $extraHeaders = array_merge($this->getExtraApiHeaders(), $extraHeaders);
        $authorizationHeader = array(
            'Authorization' => $this->buildAuthorizationHeaderForAPIRequest($method, $uri, $token, $body)
        );
        $headers = array_merge($authorizationHeader, $extraHeaders);

        return $this->httpClient->retrieveResponse($uri, $body, $headers, $method);
    }
	
	public function custom_request($path, $method = 'GET', $body = null, array $extraHeaders = array())
    {
		$uri = $this->determineRequestUriFromPath($path, $this->baseApiUri);

        /** @var $token StdOAuth1Token */
        //$token = $this->storage->retrieveAccessToken($this->service());
		//$token = $this->storage->retrieve_access_token($this->service());
		// Fetch the access token object from session
		$token = $_SESSION['access_token_object'];
		
		// Added by shasi, to specify the default headers in the request
		if(count($extraHeaders) == 0) {
			$extraHeaders = array( "Accept" =>"application/vnd.deere.axiom.v3+json");
		}
		
		//echo 'SEE: '.$this->build_authorization_header_for_APIRequest($method, $uri, $token, $body); exit;
		
        $extraHeaders = array_merge($this->getExtraApiHeaders(), $extraHeaders);
        $authorizationHeader = array(
            //'Authorization' => $this->buildAuthorizationHeaderForAPIRequest($method, $uri, $token, $body)
			'Authorization' => $this->build_authorization_header_for_APIRequest($method, $uri, $token, $body)
        );
        $headers = array_merge($authorizationHeader, $extraHeaders);

        return $this->httpClient->retrieve_response($uri, $body, $headers, $method);
    }

    /**
     * Return any additional headers always needed for this service implementation's OAuth calls.
     *
     * @return array
     */
    protected function getExtraOAuthHeaders()
    {
        return array();
    }

    /**
     * Return any additional headers always needed for this service implementation's API calls.
     *
     * @return array
     */
    protected function getExtraApiHeaders()
    {
        return array();
    }

    /**
     * Builds the authorization header for getting an access or request token.
     *
     * @param array $extraParameters
     *
     * @return string
     */
    protected function buildAuthorizationHeaderForTokenRequest(array $extraParameters = array())
    {
        $parameters = $this->getBasicAuthorizationHeaderInfo();
        $parameters = array_merge($parameters, $extraParameters);
        $parameters['oauth_signature'] = $this->signature->getSignature(
            $this->getRequestTokenEndpoint(),
            $parameters,
            'POST'
        );

        $authorizationHeader = 'OAuth ';
        $delimiter = '';
        foreach ($parameters as $key => $value) {
            $authorizationHeader .= $delimiter . rawurlencode($key) . '="' . rawurlencode($value) . '"';

            $delimiter = ', ';
        }

        return $authorizationHeader;
    }

    /**
     * Builds the authorization header for an authenticated API request
     *
     * @param string         $method
     * @param UriInterface   $uri        The uri the request is headed
     * @param TokenInterface $token
     * @param array          $bodyParams Request body if applicable (key/value pairs)
     *
     * @return string
     */
    protected function buildAuthorizationHeaderForAPIRequest(
        $method,
        UriInterface $uri,
        TokenInterface $token,
        $bodyParams = null
    ) {
		$this->signature->setTokenSecret($token->getAccessTokenSecret());
        $authParameters = $this->getBasicAuthorizationHeaderInfo();
        if (isset($authParameters['oauth_callback'])) {
            unset($authParameters['oauth_callback']);
        }
		
        $authParameters = array_merge($authParameters, array('oauth_token' => $token->getAccessToken()));

        $signatureParams = (is_array($bodyParams)) ? array_merge($authParameters, $bodyParams) : $authParameters;
        $authParameters['oauth_signature'] = $this->signature->getSignature($uri, $signatureParams, $method);

        if (is_array($bodyParams) && isset($bodyParams['oauth_session_handle'])) {
            $authParameters['oauth_session_handle'] = $bodyParams['oauth_session_handle'];
            unset($bodyParams['oauth_session_handle']);
        }
		
		// Added by shasi, to include oauth_verifier in the Authorization header
		if (is_array($bodyParams) && isset($bodyParams['oauth_verifier'])) {
            $authParameters['oauth_verifier'] = $bodyParams['oauth_verifier'];
            //unset($bodyParams['oauth_session_handle']);
        }

        $authorizationHeader = 'OAuth ';
        $delimiter = '';

        foreach ($authParameters as $key => $value) {
            $authorizationHeader .= $delimiter . rawurlencode($key) . '="' . rawurlencode($value) . '"';
            $delimiter = ', ';
        }

        return $authorizationHeader;
    }
	
	// Custom function by shasi to build authorization header
	protected function build_authorization_header_for_APIRequest(
        $method,
        UriInterface $uri,
        $token,
        $bodyParams = null
    ) {
		//$StdOAuth1Token = new StdOAuth1Token();
		//$this->signature->setTokenSecret($StdOAuth1Token->getAccessTokenSecret());
		
        $authParameters = $this->getBasicAuthorizationHeaderInfo();
        if (isset($authParameters['oauth_callback'])) {
            unset($authParameters['oauth_callback']);
        }

        $authParameters = array_merge($authParameters, array('oauth_token' => $token));

        $signatureParams = (is_array($bodyParams)) ? array_merge($authParameters, $bodyParams) : $authParameters;
        $authParameters['oauth_signature'] = $this->signature->getSignature($uri, $signatureParams, $method);
		
		//echo 'Signature1: '.$authParameters['oauth_signature'].'<br>';
		//echo 'Signature2: '.urldecode($authParameters['oauth_signature']).'<br>';
		//echo 'Signature3: '.html_entity_decode($authParameters['oauth_signature']).'<br>';

        if (is_array($bodyParams) && isset($bodyParams['oauth_session_handle'])) {
            $authParameters['oauth_session_handle'] = $bodyParams['oauth_session_handle'];
            unset($bodyParams['oauth_session_handle']);
        }
		
		// Added by shasi, to include oauth_verifier in the Authorization header
		if (is_array($bodyParams) && isset($bodyParams['oauth_verifier'])) {
            $authParameters['oauth_verifier'] = $bodyParams['oauth_verifier'];
            //unset($bodyParams['oauth_session_handle']);
        }

        $authorizationHeader = 'OAuth ';
        $delimiter = '';

        foreach ($authParameters as $key => $value) {
            $authorizationHeader .= $delimiter . rawurlencode($key) . '="' . rawurlencode($value) . '"';
            $delimiter = ', ';
        }

        return $authorizationHeader;
    }

    /**
     * Builds the authorization header array.
     *
     * @return array
     */
    protected function getBasicAuthorizationHeaderInfo()
    {
        $dateTime = new \DateTime();
        $headerParameters = array(
            'oauth_callback'         => $this->credentials->getCallbackUrl(),
            'oauth_consumer_key'     => $this->credentials->getConsumerId(),
            'oauth_nonce'            => $this->generateNonce(),
            'oauth_signature_method' => $this->getSignatureMethod(),
            'oauth_timestamp'        => $dateTime->format('U'),
            'oauth_version'          => $this->getVersion(),
        );

        return $headerParameters;
    }

    /**
     * Pseudo random string generator used to build a unique string to sign each request
     *
     * @param int $length
     *
     * @return string
     */
    protected function generateNonce($length = 32)
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';

        $nonce = '';
        $maxRand = strlen($characters)-1;
        for ($i = 0; $i < $length; $i++) {
            $nonce.= $characters[rand(0, $maxRand)];
        }

        return $nonce;
    }

    /**
     * @return string
     */
    protected function getSignatureMethod()
    {
        return 'HMAC-SHA1';
    }

    /**
     * This returns the version used in the authorization header of the requests
     *
     * @return string
     */
    protected function getVersion()
    {
        return '1.0';
    }

    /**
     * Parses the request token response and returns a TokenInterface.
     * This is only needed to verify the `oauth_callback_confirmed` parameter. The actual
     * parsing logic is contained in the access token parser.
     *
     * @abstract
     *
     * @param string $responseBody
     *
     * @return TokenInterface
     *
     * @throws TokenResponseException
     */
    abstract protected function parseRequestTokenResponse($responseBody);

    /**
     * Parses the access token response and returns a TokenInterface.
     *
     * @abstract
     *
     * @param string $responseBody
     *
     * @return TokenInterface
     *
     * @throws TokenResponseException
     */
    abstract protected function parseAccessTokenResponse($responseBody);
}

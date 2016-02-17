<?php
session_start();

use OAuth\OAuth1\Service\Deere;
use OAuth\Common\Storage\Session;
use OAuth\Common\Consumer\Credentials;

use OAuth\Common\Http\Client\CurlClient;
use OAuth\OAuth1\Token\StdOAuth1Token;
	
if(isset($_POST['request_token_date']) && $_POST['request_token_date'] != '' && isset($_POST['renew_token'])) {
	$config = include_once 'config.php';
	
	include_once(dirname(__FILE__).'/jdClientsClass.php');
	$jdClients = new jdClientsClass($config);
	$jdClients->setConfig($config);
	
	require_once __DIR__ . '/bootstrap.php';
	$storage = new Session();
	
	$credentials = new Credentials(
		$servicesCredentials['deere']['key'],
		$servicesCredentials['deere']['secret'],
		$currentUri->getAbsoluteUri()
	);
	
	$serviceFactory->setHttpClient(new CurlClient);
	$deereService = $serviceFactory->createService('Deere', $credentials, $storage);
	
	// Call API Catalog to fetch API urls, along with access tokens, renew access token if expired
	if(!isset($config['access_token']) || $_POST['renew_token'] == 1) {
		
		$catalog_result = $jdClients->getApiCatalog('');
		//print_r($catalog_result); exit;
		/*$config = array_merge($config, $catalog_result);
		//print_r($config); exit;
		file_put_contents('config.php', '<?php return ' . var_export($config, true) . ';');*/
  
		$requestTokenUri = $catalog_result['oauthRequestToken'];
		$authorize_request_token_url = $catalog_result['oauthAuthorizeRequestToken'];
		$accessTokenUri = $catalog_result['oauthAccessToken'];
		$oauth_token = '';
		$oauth_token_secret = '';
		$oauth_verifier = '';

		$requestToken = $jdClients->getRequestToken($requestTokenUri);
		//print_r($requestToken); exit;
		$oauth_token = $requestToken['oauth_token'];
		$oauth_token_secret = $requestToken['oauth_token_secret'];
		$oauth_token_secret = urldecode($oauth_token_secret);
		
		$jdClients->oauth_token_secret = $oauth_token_secret;
		
		$token = new StdOAuth1Token();
		$token->setRequestToken($oauth_token);
		$token->setRequestTokenSecret($oauth_token_secret);
		$token->setAccessToken($oauth_token);
		$token->setAccessTokenSecret($oauth_token_secret);

		$token->setEndOfLife(StdOAuth1Token::EOL_NEVER_EXPIRES);
		
		$deereService->store_access_token("Deere", $oauth_token);
		$authorize_request_token_url = str_replace("{token}", $oauth_token, $authorize_request_token_url);
		//echo $authorize_request_token_url; exit;
		$oauth_verifier = $jdClients->getVerificationCode($authorize_request_token_url);
		//echo 'Verifier: '.$oauth_verifier.'<br>'; exit;
		
		$access_token_response = $deereService->request_access_token(
			$oauth_token,
			$oauth_verifier,
			$oauth_token_secret,
			$catalog_result['oauthAccessToken']
		);
		
		/*if(isset($_SESSION['access_token_object']) && $_SESSION['access_token_object'] != '') {
			$access_token_object = $_SESSION['access_token_object'];
			$config = array_merge($config, array('access_token_object' => $access_token_object, 'token_issue_date' => date("m/d/Y")));
			//print_r($config); exit;
			file_put_contents('config.php', '<?php return ' . var_export($config, true) . ';');
		}
		else {
			die("Error: Could not fetch Access Token from Session");
		}*/
		
		$access_token_response = explode("&", $access_token_response);
		$access_token_val = $access_token_response[0];
		$access_token_secret_val = $access_token_response[1];
		
		$access_token_val = explode("oauth_token=", $access_token_val);
		$access_token = end($access_token_val);
		
		$access_token_secret_val = explode("oauth_token_secret=", $access_token_secret_val);
		$access_token_secret = end($access_token_secret_val);
		$access_token_secret = urldecode($access_token_secret);
		//$jdClients->set_access_token($access_token);
		//$jdClients->set_access_token_secret($access_token_secret);
		
		$config = array_merge($config, array('access_token' => $access_token, 'access_token_secret' => $access_token_secret, 'token_issue_date' => date("m/d/Y")));
		//print_r($config); //exit;
		file_put_contents('config.php', '<?php return ' . var_export($config, true) . ';');
	}
	
	// Re-initialize the config, to store the access token value in session
	$jdClients->setConfig($config);
	
	echo $config['access_token'];
}
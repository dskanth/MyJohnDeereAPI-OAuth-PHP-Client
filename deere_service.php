<?php
$config = include_once 'config.php';
include_once(dirname(__FILE__).'/jdClientsClass.php');
$jdClients = new jdClientsClass($config);
$jdClients->setConfig($config);

$renew_token = 0;
// If access token exists, check its validity (1 year by default)
if(isset($config['access_token']) && $config['access_token'] != '' && isset($config['token_issue_date']) && $config['token_issue_date'] != '') {
	$token_issue_date = new DateTime($config['token_issue_date']);
	$token_issue_date->add(new DateInterval('P365D'));
	
	$today_date = new DateTime();
	if ( $token_issue_date <= $today_date ) {
	  $renew_token = 1;
	}
}
if(!isset($config['access_token']) || $config['access_token'] == '' || $renew_token == 1) {
	header("Location: index.php");
}

use OAuth\OAuth1\Service\Deere;
use OAuth\Common\Storage\Session;
use OAuth\Common\Consumer\Credentials;

use OAuth\Common\Http\Client\CurlClient;
use OAuth\OAuth1\Token\StdOAuth1Token;

require_once __DIR__ . '/bootstrap.php';
$storage = new Session();

$credentials = new Credentials(
	$servicesCredentials['deere']['key'],
	$servicesCredentials['deere']['secret'],
	$currentUri->getAbsoluteUri()
);

$serviceFactory->setHttpClient(new CurlClient);
$deereService = $serviceFactory->createService('Deere', $credentials, $storage);

if(!isset($_SESSION['access_token_object'])) {
	$token = new StdOAuth1Token();

	$token->setRequestToken($config['access_token']);
	$token->setRequestTokenSecret($config['access_token_secret']);
	$token->setAccessToken($config['access_token']);
	$token->setAccessTokenSecret($config['access_token_secret']);

	$token->setEndOfLife(StdOAuth1Token::EOL_NEVER_EXPIRES);
	$_SESSION['access_token_object'] = serialize($token);
}

// Fetch and return the Location header from the response passed
function getLocationHeader($response_headers) {
	$loc_header = '';
	$header_text = substr($response_headers, 0, strpos($response_headers, "\r\n\r\n"));
	//echo 'SEE: '.print_r($header_text); exit;
	$headers = array();
	$response_headers = explode("\r\n", $response_headers);
	
	/*foreach (explode("\r\n", $response_headers) as $i => $line) {
        if ($i === 0)
            $headers['http_code'] = $line;
        else
        {
            list ($key, $value) = explode(': ', $line);

            $headers[$key] = $value;
        }
	}*/
	//print_r($headers); exit;
	//return $headers['Location'];
	
		foreach($response_headers as $resp_header) {
			if (stripos($resp_header, 'Location:') !== false) {
				$loc_header = $resp_header;
				break;
			}
		}
		return $loc_header;
}
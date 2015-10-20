<?php

class OauthController extends BaseController {

	/*
	|--------------------------------------------------------------------------
	| Oauth Controller
	|--------------------------------------------------------------------------
	|
	| Here we check whether the user is authenticated with Deere and if yes, 
	| we parse the response. Else, we redirect the user to login page
	|
	*/

	public function login()
	{
		// get data from input
		$token = Input::get( 'oauth_token' );
		$verify = Input::get( 'oauth_verifier' );

		// get Deere service
		$serviceFactory = new \OAuth\ServiceFactory();
		$tw = OAuth::consumer( 'Deere' );

		// check if code is valid

		// if code is provided get user data and sign in
		if ( !empty( $token ) && !empty( $verify ) ) {
			// This was a callback request from Deere, get the token
			$token = $tw->requestAccessToken( $token, $verify );

			// Send a request with it
			$result = json_decode( $tw->request( '/users/@currentUser' ), true );

			$message = 'Your unique Deere user id is: ' . $result['accountName'] . ' and your name is ' . $result['givenName']. ' '.$result['familyName'];
			echo $message. "<br><br>";
			$deere_user_id = $result['accountName'];
			$deere_name = $result['givenName']. ' '.$result['familyName'];
			$deere_user_type = $result['userType'];
			
			// Print ID and Name of User
			$user_email_endpoint = '/users/'.$deere_user_id.'/emailAddresses';
			$email_result = json_decode( $tw->request( $user_email_endpoint ), true );
			$user_email = $email_result['values'][0]['value'];
			
			echo 'Email: '.$user_email; 
			exit;

			//print_r($result); exit;
		}
		// if not ask for permission first
		else {
			// get request token
			$reqToken = $tw->requestRequestToken();
			
			// get Authorization Uri sending the request token
			$url = $tw->getAuthorizationUri(array('oauth_token' => $reqToken->getRequestToken()));

			// return to Deere login url
			return Redirect::to( (string)$url );
		}
	}
}

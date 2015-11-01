<?php

class HomeController extends BaseController {

	/*
	|--------------------------------------------------------------------------
	| Default Home Controller
	|--------------------------------------------------------------------------
	|
	| You may wish to use controllers instead of, or in addition to, Closure
	| based routes. That's great! Here is an example controller method to
	| get you started. To route to this controller, just add the route:
	|
	|	Route::get('/', 'HomeController@showWelcome');
	|
	*/

	// Home Page in general
	public function showWelcome() {
		return View::make('home', array('user_name' => Session::get('deere_user_id'), 'name' => Session::get('deere_user_name'), 'email' => Session::get('deere_user_email'), 'deere_user_type' => Session::get('deere_user_type')));
	}
	
	// Home page with Auto Login
	public function showHome()
	{
		//return View::make('home');
		
		// get data from input
		$token = Input::get( 'oauth_token' );
		$verify = Input::get( 'oauth_verifier' );
		
		// get Deere service
		$serviceFactory = new \OAuth\ServiceFactory();
		$tw = Artdarek\OAuth\Facade\OAuth::consumer( 'Deere' );
		
		// check if code is valid

		// if code is provided get user data and sign in
		if ( !empty( $token ) && !empty( $verify ) ) {
			//echo 'here1!'; exit;
			// This was a callback request from Deere, get the token
			$token = $tw->requestAccessToken( $token, $verify );
			
			// First get the Oauth Endpoints
			//$endpoints = json_decode( $tw->request( '/' ), true );
			//print_r($endpoints); exit;
			
			// Send a request with it
			//$result = json_decode( $tw->request( '/users/SD44168/emailAddresses' ), true );
			$result = json_decode( $tw->request( '/users/@currentUser' ), true );
			//print_r($result); exit;
			
			$deere_user_id = $result['accountName'];
			$deere_name = $result['givenName']. ' '.$result['familyName'];
			$deere_user_type = $result['userType'];
			
			//$message = 'Your unique Deere user id is: ' . $deere_user_id . ' and your name is ' . $deere_name;
			
			// Print ID and Name of User
			//echo $message. "<br><br>";
			
			$user_email_endpoint = '/users/'.$deere_user_id.'/emailAddresses';
			//echo 'User Email Endpoint: '.$user_email_endpoint.'<br>';
			
			//$token = $tw->requestAccessToken( $token, $verify );
						
			$email_result = json_decode( $tw->request( $user_email_endpoint ), true );
			//echo 'Response from Email Endpoint: <br>';
			//print_r($email_result); exit;
			$user_email = $email_result['values'][0]['value'];
			// Print Email of User
			//echo 'Email: '.$user_email; exit;
			
			//echo 'Username: '.$deere_user_id.'<br><br>';
			//echo 'Name: '.$deere_name.'<br><br>';
			//echo 'EMAIL: '.$user_email.'<br><br>'; exit;
			
			Session::put('deere_user_id', $deere_user_id);
			//echo 'SEE: '.Session::get('deere_user_id'); exit;
			Session::put('deere_user_name', $deere_name);
			Session::put('deere_user_email', $user_email);
			Session::put('deere_user_type', $deere_user_type);
			//Session::put('deere_user_org_total', $total_orgs);
			//Session::put('deere_user_orgs', $user_orgs);
			
			//header("Location: http://localhost/sdk_duplicator"); exit; // Fails
			return Redirect::to(URL::to('/'));
			
			//return View::make('home', array('user_name' => $deere_user_id, 'name' => $deere_name, 'email' => $user_email, 'deere_user_type' => $deere_user_type, 'org_total' => $org_result['total'], 'user_orgs' => $org_result['values']));
		}
		// if not ask for permission first
		else {
			if(Session::get('deere_user_id') == '') {
				//echo 'here2!'; exit;
				// get request token
				$reqToken = $tw->requestRequestToken();
				//echo $reqToken->getRequestToken();
				// get Authorization Uri sending the request token
				$url = $tw->getAuthorizationUri(array('oauth_token' => $reqToken->getRequestToken()));
				//echo (string)$url;
				// return to Deere login url
				return Redirect::to( (string)$url );
			}
		}
		
		// Check to see if the user is returning from some other page, if yes, then redirect him to that page
		//echo 'Forward to: '.Session::get('deere_user_forward_to'); exit;
		
		if(Session::get('deere_user_forward_to') != '') {
			$user_redirect_url = Session::get('deere_user_forward_to');
			Session::forget('deere_user_forward_to');
			return Redirect::to( $user_redirect_url );
			exit;
		}
		
		//Session::put('deere_user_id', 'shasi');
		$deere_user_session_id = Session::get('deere_user_id');
		//echo 'SEE: '.$deere_user_session_id; //exit;
		$deere_user_name = Session::get('deere_user_name');
		$deere_user_email = Session::get('deere_user_email');
		$deere_user_type = Session::get('deere_user_type');
		
		return View::make('home', array('user_name' => $deere_user_session_id, 'name' => $deere_user_name, 'email' => $deere_user_email, 'deere_user_type' => $deere_user_type));
		
		
		//echo '<div style="margin: 10px auto; width:350px; color:green; font-size: 21px; border: 1px solid red; padding: 10px; text-align: center;">Welcome to the framework!<br>Try <a href="oauth">Oauth Login</a></div>';
	}
}

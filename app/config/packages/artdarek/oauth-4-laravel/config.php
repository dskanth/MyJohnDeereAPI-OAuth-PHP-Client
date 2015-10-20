<?php 

return array( 
	
	/*
	|--------------------------------------------------------------------------
	| oAuth Config
	|--------------------------------------------------------------------------
	*/

	/**
	 * Storage
	 */
	'storage' => 'Session', 

	/**
	 * Consumers
	 */
	'consumers' => array(

		'Deere' => array(
			'client_id'     => 'enter app id here',
			'client_secret' => 'Enter your app shared secret here',
			// No scope - oauth1 doesn't need scope
		)
	)

);
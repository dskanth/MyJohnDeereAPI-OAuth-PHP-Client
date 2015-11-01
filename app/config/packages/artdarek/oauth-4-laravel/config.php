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
			'client_id'     => 'Please enter your appi id from developer.deere.com',
			'client_secret' => 'Please enter your appi shared secret from developer.deere.com',
			// No scope - oauth1 doesn't need scope
		)
	)

);
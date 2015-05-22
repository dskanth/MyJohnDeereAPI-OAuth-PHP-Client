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
			'client_id'     => 'app id here',
			'client_secret' => 'secret here',
			// No scope - oauth1 doesn't need scope
		)
	)

);
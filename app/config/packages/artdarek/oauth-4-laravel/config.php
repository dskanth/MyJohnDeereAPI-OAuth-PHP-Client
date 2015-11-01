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
			'client_id'     => 'johndeere-4vyamQneYTL26o5Ix13tfrAQ',
			'client_secret' => '94f13210b66ba707c594bef5c1c23efba132eca9',
			// No scope - oauth1 doesn't need scope
		)
	)

);
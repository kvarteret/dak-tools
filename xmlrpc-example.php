<?php

/*
	Desc
 */

	$dak_events_xmlrpc_methods = array(
		"dak_events.add_event" => "dak_events_add_event",
	);


	/*
	 *		Implementations:
	 */
	

	function dak_events_sitting_duck($args) {
		global $wp_xmlrpc_server;
    	$wp_xmlrpc_server->escape( $args );
		# xmlrp
		
		$blog_id  = $args[0];
    	$username = $args[1];
    	$password = $args[2];

    	if ( ! $user = $wp_xmlrpc_server->login( $username, $password ) )
        	return $wp_xmlrpc_server->error;

        /*
         *	Functionality if authenticated and authorized:
         */
        
        #Send me the Codez here

	}


?>
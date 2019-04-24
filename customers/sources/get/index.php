<?php

/*
 *
 * -/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/
 * SCRIPT SETUP
 * /-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-
 *
 */
ini_set( 'display_errors', 0 );
ini_set( 'error_reporting', E_ALL );

// Set the timezone
date_default_timezone_set( 'Asia/Kolkata' );

// Do not let this script timeout
set_time_limit( 0 );

// Continue processing this script even if the user closes the tab, or
//  	hits the ESC key
ignore_user_abort( true );

// Allow this script to triggered from another origin
header( 'Access-Control-Allow-Origin: *' );

// Remove / modify certain headers of the response
header_remove( 'X-Powered-By' );
header( 'Content-Type: application/json' );	// JSON format





/*
 *
 * -/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/
 * SCRIPT DEPENDENCIES
 * /-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-
 *
 */
require __DIR__ . '/lib/crm.php';





try {

	$customerSources = CRM\getAllCustomerSources();

	// If no sources were found
	if ( empty( $customerSources ) ) {
		$response[ 'statusCode' ] = 1;
		$response[ 'message' ] = 'No customer sources were found.';
		http_response_code( 200 );
		die( json_encode( $response ) );
	}

	$response[ 'statusCode' ] = 0;
	$response[ 'message' ] = 'Got customer sources.';
	$response[ 'data' ] = $customerSources;

	http_response_code( 200 );

} catch ( \Exception $e ) {

	// Respond with an error
	if ( $e->getCode() > 1 ) {
		$response[ 'statusCode' ] = -1;
		$response[ 'message' ] = $e->getMessage();
	}
	else {
		$response[ 'statusCode' ] = 1;
		$response[ 'message' ] = $e->getMessage();
	}
	http_response_code( 500 );

}
// Finally, respond back to the client
die( json_encode( $response ) );

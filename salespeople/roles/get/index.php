<?php
/*
 *
 * This endpoint returns the "role" of the given backend user.
 *
 */
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

$input = &$_GET;





/*
 *
 * -/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/
 * DEPENDENCIES
 * /-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-
 *
 */
require __DIR__ . '/lib/crm.php';





/*
 *
 * Preliminary input sanitization
 *
 */
foreach ( $input as $key => &$value ) {
	$value = trim( $value );
}





/*
 *
 * -/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/
 * CORE
 * /-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-
 *
 */
/*
 *
 * Check if the required inputs are **present**
 *
 */
if ( empty( $input[ 'roleId' ] ) ) {
	$response[ 'statusCode' ] = 4001;
	$response[ 'message' ] = 'Please provide a Role Id.';
	http_response_code( 400 );
	die( json_encode( $response ) );
}

/*
 *
 * Fetch the user role
 *
 */
try {

	$role = CRM\getUserRole( $input[ 'roleId' ] );

	// If no role was found
	if ( empty( $role ) ) {
		$response[ 'statusCode' ] = 4002;
		$response[ 'message' ] = 'This user does not exist or has no assigned role.';
		http_response_code( 400 );
		die( json_encode( $response ) );
	}

	$response[ 'statusCode' ] = 0;
	$response[ 'message' ] = 'Got role';
	$response[ 'data' ] = $role;

}
catch ( \Exception $e ) {

	// Respond with an error
	if ( $e->getCode() > 5000 )
		$response[ 'statusCode' ] = $e->getCode();
	else
		$response[ 'statusCode' ] = -1;

	$response[ 'message' ] = $e->getMessage();
	http_response_code( 500 );

}
// Finally, respond back to the client
die( json_encode( $response ) );

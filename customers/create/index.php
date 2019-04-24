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

$input = &$_POST;





/*
 *
 * -/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/
 * SCRIPT DEPENDENCIES
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
 * Check if the required inputs are **present**
 *
 */
if ( empty( $input[ 'phoneNumber' ] ) ) {
	$response[ 'statusCode' ] = 4001;
	$response[ 'message' ] = 'Please provide a phone number.';
	http_response_code( 400 );
	die( json_encode( $response ) );
}
if ( empty( $input[ 'project' ] ) ) {
	$response[ 'statusCode' ] = 4002;
	$response[ 'message' ] = 'Please provide a project name.';
	http_response_code( 400 );
	die( json_encode( $response ) );
}
if ( empty( $input[ 'context' ] ) ) {
	$response[ 'statusCode' ] = 4003;
	$response[ 'message' ] = 'Please provide the source or context of the customer.';
	http_response_code( 400 );
	die( json_encode( $response ) );
}

/*
 *
 * Extract the input fields and Check if they are **valid**
 *
 */
$customer = [
	'project' => $input[ 'project' ],
	'context' => $input[ 'context' ],
	'specificContext' => $input[ 'specificContext' ] ?? ''
];
// Phone number(s)
if ( ! preg_match( '/^\+\d+$/', $input[ 'phoneNumber' ] ) ) {
	$response[ 'statusCode' ] = 4004;
	$response[ 'message' ] = 'Please provide a valid primary phone number.';
	http_response_code( 400 );
	die( json_encode( $response ) );
}
$customer[ 'phoneNumber' ] = $input[ 'phoneNumber' ];

foreach ( [ 2, 3, 4, 5 ] as $count ) {
	if ( empty( $input[ 'phoneNumber' . $count ] ) )
		continue;

	if ( preg_match( '/^\+\d+$/', $input[ 'phoneNumber' . $count ] ) ) {
		$customer[ 'phoneNumber' . $count ] = $input[ 'phoneNumber' . $count ];
		continue;
	}

	$response[ 'statusCode' ] = 4005;
	$response[ 'message' ] = 'Phone number #' . $count . ' is not a valid phone number.';
	http_response_code( 400 );
	die( json_encode( $response ) );
}

// Email address
if ( ! empty( $input[ 'email' ] ) ) {
	if ( strpos( $input[ 'email' ], '@' ) !== false )
		$customer[ 'email' ] = $input[ 'email' ];
	else {
		$response[ 'statusCode' ] = 4006;
		$response[ 'message' ] = 'Please provide a valid email address.';
		http_response_code( 400 );
		die( json_encode( $response ) );
	}
}

// Name
if ( ! empty( $input[ 'name' ] ) ) {
	$names = preg_split( '/\s+/', $input[ 'name' ] );
	if ( count( $names ) == 1 )
		$customer[ 'lastName' ] = $names[ 0 ];
	else {
		$customer[ 'firstName' ] = $names[ 0 ];
		$customer[ 'lastName' ] = implode( ' ', array_slice( $names, 1 ) );
	}
}
else {
	$customer[ 'firstName' ] = 'AG' . ' ' . ( $customer[ 'specificContext' ] ?? $customer[ 'context' ] );
	$customer[ 'lastName' ] = CRM\getDateAndTimeStamp();
}

// Record Owner
if ( ! empty( $input[ 'ownerId' ] ) )
	$customer[ 'ownerId' ] = $input[ 'ownerId' ];

/*
 *
 * Create the customer record on the CRM
 *
 */
try {
	$customerIds = CRM\createCustomer( $customer );
	$response[ 'statusCode' ] = 0;
	$response[ 'message' ] = 'Customer added.';
	$response[ 'data' ] = $customerIds;
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

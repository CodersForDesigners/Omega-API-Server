<?php
/*
 *
 * This routes updates a customer record based on a provided id or uid
 *
 */
/*
 *
 * -/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/
 * SCRIPT SETUP
 * /-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-
 *
 */
ini_set( 'display_errors', 1 );
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
if ( empty( $_GET[ '_anId' ] ) ) {
	$response[ 'statusCode' ] = 4001;
	$response[ 'message' ] = 'Please provide a UID or internal record ID.';
	http_response_code( 400 );
	die( json_encode( $response ) );
}

/*
 *
 * Extract the fields that are to be updated
 *
 */
$anId = $_GET[ '_anId' ];
if ( strlen( $anId ) < 19 )
	$uid = $anId;
else
	$internalId = $anId;
// the fields to update
$data = [ ];
foreach ( $input as $attribute => $value )
	$data[ $attribute ] = $value;

/*
 *
 * Update the customer record on the CRM
 *
 */
try {
	$r = CRM\_getRecordById( 'Tasks', '3261944000007179054' );
	// $r = CRM\createTask();
	die( json_encode( $r ) );

	if ( empty( $internalId ) )
		$customer = CRM\getCustomerByUid( $uid );
	else
		$customer = CRM\getCustomerById( $internalId );

	$internalId = $internalId ?? $customer[ 'id' ];
	$recordType = $customer[ 'recordType' ];
	$updateStatus = CRM\updateCustomer( $recordType, $internalId, $data );

	$response[ 'statusCode' ] = 0;
	$response[ 'message' ] = 'Customer updated.';
	$response[ 'data' ] = $updateStatus;
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

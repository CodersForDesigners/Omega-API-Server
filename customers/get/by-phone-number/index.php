<?php
/*
 *
 * This script fetches a user based on a phone number from the system
 *
 */

ini_set( 'display_errors', 0 );
ini_set( 'error_reporting', E_ALL );

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

/*
 *
 * Extract the input fields and Check if they are **valid**
 *
 */
// Phone number(s)
if ( ! preg_match( '/^\+\d+$/', $input[ 'phoneNumber' ] ) ) {
	$response[ 'statusCode' ] = 4003;
	$response[ 'message' ] = 'Please provide a valid primary phone number.';
	http_response_code( 400 );
	die( json_encode( $response ) );
}

$phoneNumber = $input[ 'phoneNumber' ];
$project = $input[ 'project' ];
$client = explode( ' ', $project )[ 0 ];

/*
 *
 * Fetch the customer record from the CRM
 *
 */
try {

	$customer = CRM\getCustomerByPhoneNumber( $phoneNumber, $client );

	// If no customer was found
	if ( empty( $customer ) ) {
		$response[ 'statusCode' ] = 1;
		$response[ 'message' ] = 'No matching customer was found.';
		http_response_code( 404 );
		die( json_encode( $response ) );
	}

	$response[ 'statusCode' ] = 0;
	$response[ 'data' ] = [
		'recordType' => $customer[ 'recordType' ],
		'_id' => $customer[ 'id' ] ?? '',
		'uid' => $customer[ 'UID' ] ?? $customer[ 'Hidden_UID' ] ?? '',
		'owner' => $customer[ 'Owner' ][ 'name' ],
		'ownerId' => $customer[ 'Owner' ][ 'id' ],
		'isProspect' => $customer[ 'isProspect' ] ?? false,
		'project' => $project,
		'name' => $customer[ 'Full_Name' ] ?? '',
		'firstName' => $customer[ 'First_Name' ] ?? '',
		'lastName' => $customer[ 'Last_Name' ] ?? '',
		'phoneNumber' => $phoneNumber,
		'email' => $customer[ 'Email' ] ?? '',
		'_ Special Discount' => $customer[ 'Special_Discount' ] ?? null,
		'_ Discount Valid Till' => $customer[ 'Discount_Valid_Till' ] ?? null
	];

} catch ( \Exception $e ) {

	// Respond with an error
	if ( $e->getCode() > 5000 ) {
		$response[ 'statusCode' ] = -1;
		$response[ 'message' ] = 'Could not determine if the customer already exists on the CRM.';
	}
	else
		$response[ 'statusCode' ] = $e->getCode();

	$response[ 'message' ] = $e->getMessage();

	if ( $e->getCode == 4002 )
		$response[ 'message' ] = 'More than one customer found with the provided details.';

	http_response_code( 500 );

}
// Finally, respond back to the client
die( json_encode( $response ) );

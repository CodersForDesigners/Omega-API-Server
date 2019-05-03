<?php
/*
 *
 * This route creates an activity for the owner of a given customer record
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

$input = &$_POST;





/*
 *
 * -/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/
 * SCRIPT DEPENDENCIES
 * /-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-/-
 *
 */
require __DIR__ . '/lib/mail.php';
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
 * Extract the fields that are to be updated
 *
 */
if ( empty( $input[ 'subject' ] ) ) {
	$response[ 'statusCode' ] = 4001;
	$response[ 'message' ] = 'Please provide a subject for the activity.';
	http_response_code( 400 );
	die( json_encode( $response ) );
}
if ( empty( $input[ 'description' ] ) ) {
	$response[ 'statusCode' ] = 4002;
	$response[ 'message' ] = 'Please provide a description for the activity.';
	http_response_code( 400 );
	die( json_encode( $response ) );
}

/*
 *
 * Extract the input fields and Check if they are **valid**
 *
 */
$customerId = $_GET[ '_id' ];
$subject = $input[ 'subject' ];
$description = $input[ 'description' ];

/*
 *
 * Make the activity for the salesperson
 *
 */
try {

	// Get the customer record
	$customer = CRM\getCustomerById( $customerId );

	// If the customer has not yet been (round-robin) "assigned",
		// then nobody would be notified of the activity, so we wait for a bit
	if ( empty( $customer[ 'UID' ] ) ) {
		sleep( 91 );
		$customer = CRM\getCustomerById( $customerId );
	}

	$mailSubject = 'OMEGA : Customer UID ' . $customer[ 'UID' ] . ' : ' . $subject;

	// If the customer has been archived
		// then also, nobody would be notified of the activity, so we notify ourselves
	if ( $customer[ 'Stage' ] == 'Archived' ) {
		Mailer\send( 'adi@lazaro.in', $mailSubject, $description );
		$response[ 'statusCode' ] = 0;
		$response[ 'message' ] = 'Request for activity issued.';
		die( json_encode( $response ) );
	}

	// Send an email to the saleperson regarding the activity
		// Get the salesperson's record in order to get the email address
	$salespersonId = $customer[ 'Owner' ][ 'id' ];
	$salesperson = CRM\getSalespersonById( $salespersonId );
	Mailer\send( $salesperson[ 'email' ], $mailSubject, $description );

	// Create an activity for the salesperson
	$status = CRM\createTask( $salespersonId, [
		'customerRecordType' => $customer[ 'recordType' ],
		'customerId' => $customerId,
		'subject' => $subject,
		'description' => $description
	] );

	$response[ 'statusCode' ] = 0;
	$response[ 'message' ] = 'Activity created.';

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

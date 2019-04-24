<?php

namespace CRM;

ini_set( "display_errors", 0 );
ini_set( "error_reporting", E_ALL );

// Set the timezone
date_default_timezone_set( 'Asia/Kolkata' );
// Do not let this script timeout
set_time_limit( 0 );




/*
 *
 * Set constant values
 *
 */
class DATA {
	public static $apiUrl = 'https://www.zohoapis.com/crm/v2/';
	// public static $apiUrl = 'https://sandbox.zohoapis.com/crm/v2/';
	public static $authCredentials;
}

/*
 *
 * Get the auth credentials
 *
 */
$authCredentialsFilename = __DIR__ . '/../../../__environment/configuration/zoho.json';
if ( empty( realpath( $authCredentialsFilename ) ) )
	sleep( 1 );
DATA::$authCredentials = json_decode( file_get_contents( $authCredentialsFilename ), true );



/*
 * -----
 * Get the current time and date stamp
 *	in Indian Standard Time
 *
 *	reference
 *		https://stackoverflow.com/questions/22134726/get-ist-time-in-javascript
 *
 * -----
 */
function getDateAndTimeStamp () {

	date_default_timezone_set( 'Asia/Kolkata' );

	// $dateAndTimeStamp = date( 'Y/m/d H:i:s' );
	$dateAndTimeStamp = date( 'Y-m-d H-i-s' );
	$microtime = (string) microtime( true );
	if ( preg_match( '/\.(\d{1,3})/', $microtime, $milliseconds ) )
		$millisecondStamp = str_pad( $milliseconds[ 1 ], 3, '', STR_PAD_RIGHT );
	else
		$millisecondStamp = '000';

	return $dateAndTimeStamp . '-' . $millisecondStamp;

}



/*
 * -----
 * A generic API request function
 * -----
 */
function getAPIResponse ( $endpoint, $method, $data = [ ] ) {

	$accessToken = DATA::$authCredentials[ 'access_token' ];

	$httpRequest = curl_init();
	curl_setopt( $httpRequest, CURLOPT_URL, $endpoint );
	curl_setopt( $httpRequest, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $httpRequest, CURLOPT_USERAGENT, 'Zo Ho Ho' );
	$headers = [
		'Authorization: Zoho-oauthtoken ' . $accessToken,
		'Cache-Control: no-cache, no-store, must-revalidate'
	];
	if ( ! empty( $data ) ) {
		$headers[ ] = 'Content-Type: application/json';
		curl_setopt( $httpRequest, CURLOPT_POSTFIELDS, json_encode( $data ) );
	}
	curl_setopt( $httpRequest, CURLOPT_HTTPHEADER, $headers );
	curl_setopt( $httpRequest, CURLOPT_CUSTOMREQUEST, $method );
	$response = curl_exec( $httpRequest );
	curl_close( $httpRequest );

	$body = json_decode( $response, true );

	if ( empty( $body ) )
		return [ ];
		// throw new \Exception( 'Response is empty.', 10 );

	// If an error occurred
	if ( ! empty( $body[ 'code' ] ) ) {
		if ( $body[ 'code' ] == 'INVALID_TOKEN' )
			throw new \Exception( 'Access token is invalid.', 5001 );
		if ( $body[ 'code' ] == 'AUTHENTICATION_FAILURE' )
			throw new \Exception( 'Failure in authenticating.', 5002 );
	}

	return $body;

}



/*
 * -----
 * Get a customer record with the given id
 * -----
 */
function getCustomerById ( $uid ) {

	$endpoint = DATA::$apiUrl . 'Contacts' . '/' . $uid;

	$responseBody = getAPIResponse( $endpoint, 'GET' );

	if ( ! isset( $responseBody[ 'data' ] ) )
		throw new \Exception( 'Response from update operation was empty.', 13 );

	$customer = $responseBody[ 'data' ][ 0 ];

	return $customer;

}



/*
 * -----
 * Creates a customer record with the given data
 * -----
 */
function createCustomer ( $data ) {

	$endpoint = DATA::$apiUrl . 'Contacts';

	$requestBody = [
		'data' => [
			[
				'Stage' => 'Lead',
				'Project' => [ $data[ 'project' ] ],	// because it's a list
				'Phone' => $data[ 'phoneNumber' ],
				'Email' => $data[ 'email' ] ?? '',
				'First_Name' => $data[ 'firstName' ],
				'Last_Name' => $data[ 'lastName' ],
				'Lead_Source' => $data[ 'context' ],
				'Customer_Status' => 'Fresh',
				'Mobile' => $data[ 'phoneNumber2' ] ?? '',
				'Home_Phone' => $data[ 'phoneNumber3' ] ?? '',
				'Asst_Phone' => $data[ 'phoneNumber4' ] ?? '',
				'Other_Phone' => $data[ 'phoneNumber5' ] ?? ''
			]
		],
		'trigger' => [
			'approval',
			'workflow',
			'blueprint'
		]
	];
	if ( $data[ 'ownerId' ] )
		$requestBody[ 'data' ][ 0 ][ 'Owner' ] = $data[ 'ownerId' ];

	$responseBody = getAPIResponse( $endpoint, 'POST', $requestBody );

	if ( empty( $responseBody ) )
		return [ ];

	// Pull out the (internal) id of the newly created customer
	$responseBody = array_filter( $responseBody[ 'data' ][ 0 ] );
	$recordId = $responseBody[ 'details' ][ 'id' ];

	// Now, get the `Hidden UID` value
	$customer = getCustomerById( $recordId );
	$uid = $customer[ 'Hidden_UID' ];

	// Return the record Id and the UID
	return [
		'_id' => $recordId,	// This now has to be kept for ThinkMobi
		'uid' => $uid
	];

}

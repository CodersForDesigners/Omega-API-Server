<?php

namespace CRM;

ini_set( "display_errors", 1 );
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
$authCredentialsFilename = __DIR__ . '/../../../../__environment/configuration/zoho.json';
if ( empty( realpath( $authCredentialsFilename ) ) )
	sleep( 1 );
DATA::$authCredentials = json_decode( file_get_contents( $authCredentialsFilename ), true );





/*
 * -----
 * Get customer by id
 * -----
 */
function getCustomerById ( $id ) {

	$customer = _getRecordById( 'Leads', $id );
	if ( empty( $customer ) ) {
		$customer = _getRecordById( 'Contacts', $id );
		if ( ! empty( $customer ) )
			if ( $customer[ 'Stage' ] == 'Prospect' )
				$customer[ 'isProspect' ] = true;
	}

	return $customer;

}



/*
 * -----
 * Get salesperson by id
 * -----
 */
function getSalespersonById ( $id ) {

	$endpoint = DATA::$apiUrl . 'users' . '/' . $id;

	$responseBody = _getAPIResponse( $endpoint, 'GET' );

	if ( ! isset( $responseBody[ 'users' ] ) )
		return null;

	$record = $responseBody[ 'users' ][ 0 ];

	return $record;

}



/*
 * -----
 * Creates a task record with the given data
 * -----
 */
function createTask ( $for, $data ) {

	$endpoint = DATA::$apiUrl . 'Tasks';

	$requestBody = [
		'data' => [
			[
				'Subject' => $data[ 'subject' ],
				'Owner' => $for,
				'Description' => $data[ 'description' ],
				'Send_Notification_Email' => true,
				'Due_Date' => _getDatestamp( '+1 day' ),
				// 'Remind_At' => [
					// 'ALARM' => 'FREQ=NONE;ACTION=EMAILANDPOPUP;TRIGGER=DATE-TIME:2019-05-02T11:27:00+05:30'
				// ],
				'Priority' => 'High'
			]
		],
		'trigger' => [
			'approval',
			'workflow',
			'blueprint'
		]
	];
	// If a customer record is to be linked to the activity record
		// Determine if it is a Lead or a Contact record
	if ( $data[ 'customerRecordType' ] == 'Leads' ) {
		$requestBody[ 'data' ][ 0 ][ '$se_module' ] = 'Leads';
		$requestBody[ 'data' ][ 0 ][ 'What_Id' ] = $data[ 'customerId' ];
	}
	else if ( $data[ 'customerRecordType' ] == 'Contacts' )
		$requestBody[ 'data' ][ 0 ][ 'Who_Id' ] = $data[ 'customerId' ];


	$responseRaw = _getAPIResponse( $endpoint, 'POST', $requestBody );

	if ( empty( $responseRaw ) or empty( $responseRaw[ 'data' ] ) )
		return false;

	$response = $responseRaw[ 'data' ][ 0 ];

	if ( strtolower( $response[ 'code' ] ) != 'success' )
		return false;

	return $response[ 'details' ];

}



/*
 * -----
 * Get either the current datestamp or one that is relative to the current
 *	in Indian Standard Time
 * -----
 */
function _getDatestamp ( $relative = null ) {

	date_default_timezone_set( 'Asia/Kolkata' );

	if ( ! empty( $relative ) )
		$dateStamp = date( 'Y-m-d', strtotime( $relative ) );
	else
		$dateStamp = date( 'Y-m-d' );

	return $dateStamp;

}



/*
 * -----
 * Get a record with the given id
 * -----
 */
function _getRecordById ( $type, $id ) {

	$endpoint = DATA::$apiUrl . $type . '/' . $id;

	$responseBody = _getAPIResponse( $endpoint, 'GET' );

	if ( ! isset( $responseBody[ 'data' ] ) )
		return null;

	$record = $responseBody[ 'data' ][ 0 ];
	$record[ 'recordType' ] = $type;

	return $record;

}



/*
 * -----
 * A generic API request function
 * -----
 */
function _getAPIResponse ( $endpoint, $method = 'GET', $data = [ ] ) {

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

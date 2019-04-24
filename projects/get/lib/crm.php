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
 * Returns all the project names listed in the "Project" field
 * 	of the Customers (Contacts) module on the CRM
 * -----
 *
 * Error codes:
 * 	3 -> No "Project" field was found
 *
 */
function getAllClientProjects () {

	$recordType = 'settings/fields';
	$queryParameters = 'module=Contacts';
	$endpoint = DATA::$apiUrl . "${recordType}?${queryParameters}";

	$responseBody = getAPIResponse( $endpoint, 'GET' );

	if ( empty( $responseBody ) )
		return [ ];

	$projectField = array_values(
		array_filter( $responseBody[ 'fields' ], function ( $field ) {
			return $field[ 'field_label' ] == 'Project';
		} )
	);

	// If the "Project" field was not found
	if ( empty( $projectField[ 0 ] ) )
		throw new \Exception( 'No "Project" field was found.', 3 );

	$projectField = $projectField[ 0 ];

	$projects = array_map( function ( $listItem ) {
		return $listItem[ 'display_value' ];
	}, $projectField[ 'pick_list_values' ] );
	$projects = array_filter( $projects, function ( $project ) {
		return strpos( $project, '-' ) !== 0;
	} );
	$projects = array_values( $projects );

	return $projects;

}

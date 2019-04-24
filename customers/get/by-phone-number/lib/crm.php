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
	public static $operatorRelationMap = [
		'=' => 'equals',
		'^=' => 'starts_with'
	];
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
 * A generic API request function
 * -----
 */
function getAPIResponse ( $endpoint, $method = 'GET', $data = [ ] ) {

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
 * Criterion stringifier
 * -----
 */
function getStringifiedCriterion ( $name, $relation__value ) {

	if ( empty( $relation__value ) ) {
		$criteriaString = '';
	}
	else if ( is_array( $relation__value ) ) {
		$operator = $relation__value[ 0 ];
		$value = $relation__value[ 1 ];
		$criteriaString = '(' . $name . ':' . DATA::$operatorRelationMap[ $operator ] . ':' . urlencode( $value ) . ')';
	}
	else {
		$value = $relation__value;
		$criteriaString = '(' . $name . ':equals:' . urlencode( $value ) . ')';
	}

	return $criteriaString;

}
/*
 * -----
 * Criteria resolver
 * -----
 */
function getResolvedCriteria ( $criteria ) {

	$name = array_keys( $criteria )[ 0 ];

	if ( in_array( $name, [ 'or', 'and' ] ) ) {
		$operator = $name;
		$subCriteria = $criteria[ $operator ];
		$subCriteriaStrings = array_map( function ( $name, $value ) {
			return getResolvedCriteria( [ $name => $value ] );
		}, array_keys( $subCriteria ), array_values( $subCriteria ) );
		return '(' . implode( $operator, $subCriteriaStrings ) . ')';
	}
	else {
		return getStringifiedCriterion(
			array_keys( $criteria )[ 0 ],
			array_values( $criteria )[ 0 ]
		);
	}

}



function getRecordWhere ( $recordType, $criteria = [ ] ) {

	$baseURL = DATA::$apiUrl . $recordType . '/search';
	$criteriaString = '?criteria=(' . getResolvedCriteria( $criteria ) . ')';
	$endpoint = $baseURL . $criteriaString;

	$responseBody = getAPIResponse( $endpoint );

	// If no record was found
	if ( empty( $responseBody ) || empty( $responseBody[ 'data' ] ) )
		return null;

	// If more than one record was found
	if ( $responseBody[ 'info' ][ 'count' ] > 1 ) {
		$errorMessage = 'More than one ' . $recordType . ' found with the given criteria; ' . json_encode( $criteria ) . '.';
		throw new \Exception( $errorMessage, 4002 );
	}

	$record = array_filter( $responseBody[ 'data' ][ 0 ] );
	$record[ 'recordType' ] = $recordType;

	return $record;

}



/*
 * -----
 * Get customer by phone number
 * -----
 */
function getCustomerByPhoneNumber ( $phoneNumber, $client ) {

	$customer = getRecordWhere( 'Leads', [
		'and' => [
			'Is_Duplicate' => 'false',
			'Project' => [ '^=', $client ],
			'or' => [
				'Phone' => $phoneNumber,
				'Alt_Mobile' => $phoneNumber
			]
		]
	] );
	if ( empty( $customer ) ) {
		$customer = getRecordWhere( 'Contacts', [
			'and' => [
				'Is_Duplicate' => 'false',
				'Project' => [ '^=', $client ],
				'or' => [
					'Phone' => $phoneNumber,
					'Other_Phone' => $phoneNumber,
					'Mobile' => $phoneNumber,
					'Home_Phone' => $phoneNumber,
					'Asst_Phone' => $phoneNumber
				]
			]
		] );
		if ( ! empty( $customer ) )
			if ( $customer[ 'Stage' ] == 'Prospect' )
				$customer[ 'isProspect' ] = true;
	}

	return $customer;

}


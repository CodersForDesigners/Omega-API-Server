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
$authCredentialsFilename = __DIR__ . '/../../../__environment/configuration/zoho.json';
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
 * Get customer by UID
 * -----
 */
function getCustomerByUid ( $uid ) {

	$customer = _getRecordsWhere( 'Leads', [ 'UID' => $uid ] );
	if ( empty( $customer ) ) {
		$customer = _getRecordsWhere( 'Contacts', [ 'UID' => $uid ] );
		if ( ! empty( $customer ) )
			if ( $customer[ 'Stage' ] == 'Prospect' )
				$customer[ 'isProspect' ] = true;
	}

	return $customer;

}



/*
 * -----
 * Update a customer
 * -----
 */
function updateCustomer ( $recordType, $id, $data ) {
	return _updateRecord( $recordType, $id, $data );
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
 * Criterion stringifier
 * -----
 */
function _getStringifiedCriterion ( $name, $relation__value ) {

	if ( empty( $relation__value ) ) {
		$criteriaString = '';
	}
	else if ( is_array( $relation__value ) ) {
		$operator = $relation__value[ 0 ];
		$value = $relation__value[ 1 ];
		$criteriaString = '(' . $name . ':' . DATA::$operatorRelationMap[ $operator ] . ':';
		// If the value has spaces, then urlencode it, else don't
		if ( preg_match( '/\s/', $value ) === 1 )
			$criteriaString = urlencode( $value ) . ')';
		else
			$criteriaString = $value . ')';
	}
	else {
		$value = $relation__value;
		$criteriaString = '(' . $name . ':equals:';
		// If the value has spaces, then urlencode it, else don't
		if ( preg_match( '/\s/', $value ) === 1 )
			$criteriaString = urlencode( $value ) . ')';
		else
			$criteriaString = $value . ')';
	}

	return $criteriaString;

}
/*
 * -----
 * Criteria resolver
 * -----
 */
function _getResolvedCriteria ( $criteria ) {

	$name = array_keys( $criteria )[ 0 ];

	if ( in_array( $name, [ 'or', 'and' ] ) ) {
		$operator = $name;
		$subCriteria = $criteria[ $operator ];
		$subCriteriaStrings = array_map( function ( $name, $value ) {
			return _getResolvedCriteria( [ $name => $value ] );
		}, array_keys( $subCriteria ), array_values( $subCriteria ) );
		return '(' . implode( $operator, $subCriteriaStrings ) . ')';
	}
	else {
		return _getStringifiedCriterion(
			array_keys( $criteria )[ 0 ],
			array_values( $criteria )[ 0 ]
		);
	}

}



/*
 * -----
 * Get record(s) where the given criteria are met
 * -----
 */
function _getRecordsWhere ( $type, $criteria = [ ] ) {

	$baseURL = DATA::$apiUrl . $type . '/' . 'search';
	$criteriaString = '?criteria=(' . _getResolvedCriteria( $criteria ) . ')';
	$endpoint = $baseURL . $criteriaString;

	$responseBody = _getAPIResponse( $endpoint );

	// If no record was found
	if ( empty( $responseBody ) || empty( $responseBody[ 'data' ] ) )
		return null;

	$record = array_filter( $responseBody[ 'data' ][ 0 ] );
	$record[ 'recordType' ] = $type;

	return $record;

}



/*
 * -----
 * Update a record with a given id
 * -----
 */
function _updateRecord ( $type, $id, $data ) {

	$endpoint = DATA::$apiUrl . $type . '/' . $id;

	$responseRaw = _getAPIResponse( $endpoint, 'PUT', [
		'data' => [ $data ],
		'trigger' => [ 'approval', 'workflow', 'blueprint' ]
	] );

	if ( ! isset( $responseRaw[ 'data' ] ) )
		throw new \Exception( 'Response from update operation was empty.', 4002 );

	$response = $responseRaw[ 'data' ][ 0 ];

	if ( strtolower( $response[ 'code' ] ) != 'success' ) {
		$errorMessage = 'The update operation was not successful.'
						. PHP_EOL . $response[ 'message' ];
		throw new \Exception( $errorMessage, 4003 );
	}

	return $response[ 'details' ];

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

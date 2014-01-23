<?php
require( 'ddns-config.php' );
require( 'Linode.php' );

// Append to the request log
function log_request ( $message ) {
	static $start = true;

	if ( ! $start ) {
		$message = "\t" . $message;
	} else {
		$start = false;
	}

	$message .= "\n";

	error_log( $message, 3, __DIR__ . '/request_log' );
}

// Set the response header and echo the message
function done( $message, $code ) {
	header( 'HTTP/1.1 '.$code );
	die( $message );
}

// Call Linode::request with passed arguments, handle errors
function request() {
	$response = call_user_func_array( array( 'Linode', 'request' ), func_get_args() );
	if ( $response->ERRORARRAY ) {
		$code = $response->ERRORARRAY->ERRORCODE;
		$message = $response->ERRORARRAY->ERRORMESSAGE;
		log_request( "Linode Error #$code: $message" );
		done( 'error', '500 Internal Server Error' );
	}
	return $response;
}

// Get the client IP
$ip = $_SERVER['REMOTE_ADDR'];

// Get the Host
$host = preg_replace( '/^ddns\./', '', $_SERVER['HTTP_HOST'] );

// Get the URI
$uri = trim( str_replace( '?'.$_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI'] ), '/' );

// Begin the log for this request
log_request( sprintf("[%s from %s]: %s/%s", date( 'c' ), $ip, $host, $uri ) );

// Get the subdomain
$name = preg_replace( '/[^\w]+/', '-', $uri );

// Abort if no URI
if ( ! $uri ) {
	log_request( 'No URI specified; aborting.' );
	done( 'invalid', '404 Bad Request' );
}

// Check authentication
if( !isset( $_SERVER['PHP_AUTH_USER'] ) ||
	!isset( $_SERVER['PHP_AUTH_PW'] ) |
	$_SERVER['PHP_AUTH_USER'] !== DDNS_USERNAME ||
	$_SERVER['PHP_AUTH_PW'] !== DDNS_PASSWORD ) {
	log_request( 'Invalid/missing credentials; aborting.' );
	done( 'denied', '403 Forbidden' );
}

// Setup Linode
Linode::init( LINODE_KEY );

// Fetch the Domains
log_request( 'Fetching domains...' );
$domains = request( 'domain.list' );

// Loop through domains...
foreach ( $domains->DATA as $domain ) {
	if ( $domain->DOMAIN == $host ) {
		// Get resources for matched domain
		log_request( "Fetching resources for $host..." );
		$resources = request( 'domain.resource.list', array(
			'DomainID' => $domain->DOMAINID
		));

		// Loop through resources...
		foreach ( $resources->DATA as $resource ) {
			if ( $resource->NAME == $name ) {
				// Update the matched domain
				log_request( "Record for $name already exists; updating..." );
				$update = request( 'domain.resource.update', array(
					'DomainID'   => $domain->DOMAINID,
					'ResourceID' => $resource->RESOURCEID,
					'Target'     => $ip
				));
				done( 'success', '200 OK' );
			}
		}

		// Add the new resource...
		log_request( "No record for $name found; creating..." );
		$create = request( 'domain.resource.create', array(
			'DomainID' => $domain->DOMAINID,
			'Type'     => 'A',
			'Name'     => $name,
			'Target'   => $ip,
			'TTL_sec'  => DDNS_TTL
		));
		done( 'success', '200 OK' );
	}
}

// Somehow, there's no record for $host
log_request( "Error: no record for $host..." );
done( 'missing', '404 Not Found' );
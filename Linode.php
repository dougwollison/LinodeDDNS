<?php
class Linode {
	protected static $api_key;
	protected static $api_url = 'https://api.linode.com/';

	/*
	 * Initialize with API key
	 */
	public static function init( $key, $url = null ) {
		self::$api_key = $key;
		if ( $url ) {
			self::$api_url = $url;
		}
	}

	/*
	 * Send request for desired method with pass arguments
	 */
	public static function request( $action, $args = array() ) {
		$args['api_key'] = self::$api_key;
		$args['api_action'] = $action;

		// Build the URL with the query args
		$url = self::$api_url . '?' . http_build_query( $args );

		// Request the url and return it's response body
		$response = file_get_contents( $url );

		// Return the JSON decoded response
		return json_decode( $response );
	}
}
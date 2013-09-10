<?php
// Make sure the proper header is sent
header('HTTP/1.1 200 OK');

// Load necessary files
require('Linode.php');
require('linode-config.php');
require('ddns-config.php');

// Test if Auth user/password is set and correct, exit if not
if(!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] !== DDNS_USERNAME) exit;
if(!isset($_SERVER['PHP_AUTH_PW']) || $_SERVER['PHP_AUTH_PW'] !== DDNS_PASSWORD) exit;

// Get the desired subdomain from the request URI (/[domain])
$ddnsname = preg_replace('/[^\w]+/', '-', trim(str_replace('?'.$_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']), '/'));

// Get the hostname from the HTTP_HOST var, lopping off the ddns subdomain
$hostname = preg_replace('/^ddns\./', '', $_SERVER['HTTP_HOST']);

/*
 * Error reporting function, checks if ERRORARRAY is present and logs it if so
 */
function check($response){
	if($response->ERRORARRAY){
		print_r($response->ERRORARRAY);
		$code = $response->ERRORARRAY->ERRORCODE;
		$message = $response->ERRORARRAY->ERRORMESSAGE;
		error_log("Linode Error #$code: $message");
		exit;
	}
}

// Setup Linode with the API key
Linode::init(LINODE_KEY);

// Get the list of domains
$domains = Linode::request('domain.list');

// Check for errors
check($domains);

// Loop through the domains
foreach($domains->DATA as $domain){
	// See if the domain matches the $hostname
	if($domain->DOMAIN == $hostname){
		// Get the list of records for the domain
		$resources = Linode::request('domain.resource.list', array('DomainID' => $domain->DOMAINID));
		
		// Check for errors
		check($resources);
		
		// Loop through the records
		foreach($resources as $resource){
			// See if it matches the disired $ddnsname
			if($resource->NAME == $ddnsname){
				// Make the update request
				$update = Linode::request('domain.resource.update', array(
					'DomainID' => $domain->DOMAINID,
					'ResourceID' => $resource->RESOURCEID,
					'Target' => $_SERVER['REMOTE_ADDR']
				));
				
				// Check for errors
				check($update);
				exit;
			}
		}
		
		// Subdomain doens't exist, create it
		$create = Linode::request('domain.resource.create', array(
			'DomainID' => $domain->DOMAINID,
			'Type' => 'A',
			'Name' => $ddnsname,
			'Target' => $_SERVER['REMOTE_ADDR']
		));
		
		// Check for errors
		check($create);
		exit;
	}
}
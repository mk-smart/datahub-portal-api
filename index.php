<?php

namespace DataHub;

error_reporting ( E_ALL );
ini_set ( 'display_errors', 1 );
require_once ('../wp-load.php');
require_once __DIR__ . '/vendor/autoload.php';

use \Symfony\Component\HttpFoundation\Request as Request;
use \Symfony\Component\HttpFoundation\Response as Response;
use \Silex\Application;

// /////////////////////
class Api extends \Silex\Application {
	private $_authenticated = FALSE;
	public function getApiKey() {
		return $this->_authenticated;
	}
	public function setApiKey($key) {
		return $this->_authenticated = $key;
	}
	public function __construct(array $values = []) {
		parent::__construct ( $values );
		$api = $this;
		$this->before ( function (Request $request, Api $api) use($api) {
			// Check user authentication
			$key = $request->getUser ();
			if (! $key) {
				// If in querystring
				$key = $request->query->get ( "api_key", false );
			}
			if (! $key) {
				// If in request body/form
				$request->request->get ( "api_key", false );
			}
			if ($key) {
				// TODO Check authorization here...
				$api->setApiKey ( $key );
			}
		} );
		$this->after ( function (Request $request, Response $response) {
			$response->headers->set ( 'Access-Control-Allow-Origin', '*' );
		});
	}
}
// /////////////////////
$api = new Api ();
$api->GET ( '', function () use($api) {
	return $api->redirect ( 'docs', 303 );
} );
////////////////////////
$api->GET ( '/datasets', function () use($api) {
	if ($api->getApiKey ()) {
		return $api->json ( array (
				'iddio' => 'porchetta' 
		) );
	} else {
		return $api->json ( array (
				'status' => 403,
				'message' => 'Forbidden to ' . $api->getApiKey () 
		), 403, array () );
	}
} );

// /////////////////////
$api ['debug'] = true;
$api->run ();
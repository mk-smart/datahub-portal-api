<?php

namespace DataHub;

require_once __DIR__ . '/Bindings.php';
require_once __DIR__ . '/Impl.php';

use \Symfony\Component\HttpFoundation\Request as Request;
use \Symfony\Component\HttpFoundation\Response as Response;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use \Silex\Application;

// /////////////////////

// /////////////////////
class Api extends \Silex\Application {
	private $_key = FALSE;
	public function isAuthenticated() {
		return $this->_key !== FALSE;
	}
	public function getApiKey() {
		return $this->_key;
	}
	public function setApiKey($key) {
		return $this->_key = $key;
	}
	
	public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true){
		$res = parent::handle($request,$type, $catch);
		if($res->getStatusCode() == 404){
			return $this->json( array (
					'status' => $res->getStatusCode (),
					'message' => "Not Found" 
			), $res->getStatusCode (), array () );
		}
		return $res;
	}
	
	public function __construct(array $values = []) {
		parent::__construct ( $values );
		$api = $this;
		$this->error ( function (GetResponseForExceptionEvent $event) use($api) {
			// You get the exception object from the received event
			$exception = $event->getException ();
			$message = sprintf ( 'My Error says: %s with code: %s', $exception->getMessage (), $exception->getCode () );
			
			// Customize your response object to display the exception details
			$response = new Response ();
			$response->setContent ( $message );
			
			// HttpExceptionInterface is a special type of exception that
			// holds status code and header details
			if ($exception instanceof HttpExceptionInterface) {
				$response->setStatusCode ( $exception->getStatusCode () );
				$response->headers->replace ( $exception->getHeaders () );
			} else {
				$response->setStatusCode ( Response::HTTP_INTERNAL_SERVER_ERROR );
			}
			
			// Send the modified response object to the event
			$event->setResponse ( $response );
			return $api->json ( array (
					'status' => $response->getStatusCode (),
					'message' => $message 
			), $response->getStatusCode (), array () );
		}, 100 );
		$this->before ( function (Request $request, Api $api) use($api) {
			// Check user authentication
			$key = $request->getUser ();
			if (! $key) {
				// If in querystring
				$key = $request->query->get ( "key", false );
			}
			if (! $key) {
				// If in request body/form
				$request->request->get ( "key", false );
			}
			if ($key && $api->checkApiKey ( $key )) {
				$api->setApiKey ( $key );
				return TRUE;
			} else {
				return $api->forbidden();
			}
		}, 100 );
		$this->after ( function (Request $request, Response $response) {
			$response->headers->set ( 'Access-Control-Allow-Origin', '*' );
		} );
	}
	public function checkApiKey($key) {
		// TODO Check authorization here...
		// TODO Not implemented yet
		return true;
	}
	public function forbidden($message = 'Forbidden'){
		return $this->json ( array (
				'status' => 403,
				'message' => $message
		), 403, array () );
	}
	public function notFound($message = 'Not Found'){
		return $this->json ( array (
				'status' => 404,
				'message' => $message
		), 404, array () );
	}
}
// /////////////////////
$api = new Api ();
$api->GET ( '', function () use($api) {
	return $api->redirect ( 'docs', 303 );
} );
// //////////////////////
$api->GET ( '/datasets/', function (Request $request) use($api) {
	$fields = explode ( ',', urldecode($request->query->get ( 'fields', 'id,type,name' )) );
	return $api->json ( Impl\getDatasets ( $api->getApiKey (), $fields ) );
} );
$api->GET ( '/dataset/{id}/', function (Request $request, $id) use($api) {
	$data = Impl\getDataset ( $api->getApiKey (), $id ) ;
	if(!$data){
		return $api->notFound();
	}
	return $api->json ( $data );
} );
$api->GET ( '/dataset/{id}/info/', function (Request $request, $id) use($api) {
	$data = Impl\getDatasetInfo ( $api->getApiKey (), $id ) ;
	if(!$data){
		return $api->notFound();
	}
	return $api->json ( $data );
} );
$api->GET ( '/dataset/{id}/access/', function (Request $request, $id) use($api) {
	$data = Impl\getDatasetAccess ( $api->getApiKey (), $id ) ;
	if(!$data){
		return $api->forbidden();
	}
	return $api->json ( $data );
} );
$api->GET ( '/dataset/{id}/feed/', function (Request $request, $id) use($api) {
	$data = Bindings\getDatasetFeed ( $api->getApiKey (), $id ) ;
	if(!$data){
		return $api->notFound();
	}
	return $api->json ( $data );
} );
	
// /////////////////////
$api ['debug'] = true;
$api->run ();
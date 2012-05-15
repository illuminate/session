<?php namespace Illuminate\Session;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class Store {

	/**
	 * Load the session for the request.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @return void
	 */
	public function start(Request $request)
	{

	}

	/**
	 * Finish the session handling for the request.
	 *
	 * @param  Symfony\Component\HttpFoundation\Response  $response
	 * @return void
	 */
	public function finish(Response $response)
	{

	}

}
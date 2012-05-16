<?php namespace Illuminate\Session;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CookieStore extends Store {

	/**
	 * The Illuminate encrypter instance.
	 *
	 * @var Illuminate\Encrypter
	 */
	protected $encrypter;

	/**
	 * The name of the session payload cookie.
	 *
	 * @var string
	 */
	protected $payload = 'illuminate_payload';

	/**
	 * Create a new Cookie based session store.
	 *
	 * @param  Illuminate\Encrypter  $encrypter
	 * @return void
	 */
	public function __construct(Encrypter $encrypter)
	{
		$this->encrypter = $encrypter;
	}

	/**
	 * Retrieve a session payload from storage.
	 *
	 * @param  string  $id
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @return array|null
	 */
	protected function retrieveSession($id, Request $request)
	{
		$value = $request->cookies->get($this->payload);

		if ( ! is_null($value))
		{
			return unserialize($this->encrypter->decrypt($value));
		}
	}

	/**
	 * Create a new session in storage.
	 *
	 * @param  string  $id
	 * @param  array   $session
	 * @param  Symfony\Component\HttpFoundation\Response  $response
	 * @return void
	 */
	protected function createSession($id, array $session, Response $response)
	{
		$value = $this->encrypter->encrypt(serialize($session));

		$response->headers->setCookie($this->createCookie($this->payload, $value));
	}

	/**
	 * Update an existing session in storage.
	 *
	 * @param  string  $id
	 * @param  array   $session
	 * @param  Symfony\Component\HttpFoundation\Response  $response
	 * @return void
	 */
	protected function updateSession($id, array $session, Response $response)
	{
		return $this->createSession($id, $session, $response);
	}

	/**
	 * Set the name of the sessoin payload cookie.
	 *
	 * @param  string  $name
	 * @return void
	 */
	public function setPayloadName($name)
	{
		$this->paylaod = $name;
	}

}
<?php namespace Illuminate\Session;

use Illuminate\Encrypter;
use Illuminate\CookieJar;
use Symfony\Component\HttpFoundation\Response;

class CookieStore extends Store {

	/**
	 * The Illuminate encrypter instance.
	 *
	 * @var Illuminate\Encrypter
	 */
	protected $encrypter;

	/**
	 * The Illuminate cookie creator.
	 *
	 * @var Illuminate\CookieJar
	 */
	protected $cookies;

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
	 * @param  Illuminate\CookieJar  $cookies
	 * @return void
	 */
	public function __construct(Encrypter $encrypter, CookieJar $cookies)
	{
		$this->cookies = $cookies;
		$this->encrypter = $encrypter;
	}

	/**
	 * Retrieve a session payload from storage.
	 *
	 * @param  string  $id
	 * @return array|null
	 */
	public function retrieveSession($id)
	{
		$value = $this->cookies->get($this->payload);

		if ( ! is_null($value))
		{
			return @unserialize($this->encrypter->decrypt($value));
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
	public function createSession($id, array $session, Response $response)
	{
		$value = $this->encrypter->encrypt(serialize($session));

		$response->headers->setCookie($this->cookies->make($this->payload, $value));
	}

	/**
	 * Update an existing session in storage.
	 *
	 * @param  string  $id
	 * @param  array   $session
	 * @param  Symfony\Component\HttpFoundation\Response  $response
	 * @return void
	 */
	public function updateSession($id, array $session, Response $response)
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

	/**
	 * Get the cookie jar instance.
	 *
	 * @return Illuminate\CookieJar
	 */
	public function getCookieJar()
	{
		return $this->cookies;
	}

}
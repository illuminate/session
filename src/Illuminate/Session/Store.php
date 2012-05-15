<?php namespace Illuminate\Session;

use ArrayAccess;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class Store {

	/**
	 * The current session payload.
	 *
	 * @var array
	 */
	protected $session;

	/**
	 * Indicates if the session already existed.
	 *
	 * @var bool
	 */
	protected $exists = true;

	/**
	 * The session lifetime in minutes.
	 *
	 * @var int
	 */
	protected $lifetime = 120;

	/**
	 * The session cookie options array.
	 *
	 * @var array
	 */
	protected $cookie = array('name' => 'illuminate_session');

	/**
	 * Load the session for the request.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @return void
	 */
	public function start(Request $request)
	{
		$id = $request->cookies->get($this->getCookieOption('name'));

		if ( ! is_null($id))
		{
			$session = $this->retrieveSession($id);
		}

		if (is_null($session) or $this->isExpired($session))
		{
			$this->exists = false;

			$session = $this->createSession();
		}

		$this->session = $session;
	}

	/**
	 * Create a fresh session payload.
	 *
	 * @return array
	 */
	protected function createSession()
	{
		$flash = array(':old:' => array(), ':new:' => array());

		return array('id' => $this->createSessionID(), 'data' => $flash);
	}

	/**
	 * Generate a new, random session ID.
	 *
	 * @return string
	 */
	protected function createSessionID()
	{
		$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

		$value = substr(str_shuffle(str_repeat($pool, 5)), 0, 40);

		return sha1($value.time());
	}

	/**
	 * Determine if the given session is expired.
	 *
	 * @param  array  $session
	 * @return bool
	 */
	protected function isExpired(array $session)
	{
		return (time() - $session['last_activity']) > ($this->lifetime * 60);
	}

	/**
	 * Finish the session handling for the request.
	 *
	 * @param  Symfony\Component\HttpFoundation\Response  $response
	 * @return void
	 */
	public function finish(Response $response)
	{
		$this->session['last_activity'] = time();

		$id = $this->getSessionID();

		// Age flash data...

		if ($this->exists)
		{
			$this->updateSession($id, $this->session);
		}
		else
		{
			$this->createSession($id, $this->session);
		}

		// Sweep...

		$response->headers->setCookie($this->createCookie());
	}

	/**
	 * Get the current session ID.
	 *
	 * @return string
	 */
	public function getSessionID()
	{
		if (isset($this->session['id'])) return $this->session['id'];
	}

	/**
	 * Generate a new session identifier.
	 *
	 * @return string
	 */
	public function regenerateSession()
	{
		$this->exists = false;

		return $this->session['id'] = $this->createSessionID();
	}

	/**
	 * Get the given cookie option.
	 *
	 * @param  string  $option
	 * @return mixed
	 */
	public function getCookieOption($option)
	{
		return $this->cookie[$option];
	}

	/**
	 * Set the given cookie option.
	 *
	 * @param  string  $option
	 * @param  mixed   $value
	 * @return void
	 */
	public function setCookieOption($option, $value)
	{
		$this->cookie[$option] = $value;
	}

	/**
	 * Set the session lifetime.
	 *
	 * @param  int   $minutes
	 * @return void
	 */
	public function setLifetime($minutes)
	{
		$this->lifetime = $minutes;
	}

}
<?php namespace Illuminate\Session;

use Closure;
use ArrayAccess;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class Store implements ArrayAccess {

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
	 * The chances of hitting the sweeper lottery.
	 *
	 * @var array
	 */
	protected $sweep = array(2, 100);

	/**
	 * The session cookie options array.
	 *
	 * @var array
	 */
	protected $cookie = array(
		'name'      => 'illuminate_session',
		'path'      => '/',
		'domain'    => null,
		'secure'    => false,
		'http_only' => true,
	);

	/**
	 * Retrieve a session payload from storage.
	 *
	 * @param  string      $id
	 * @return array|null
	 */
	abstract protected function retrieveSession($id);

	/**
	 * Create a new session in storage.
	 *
	 * @param  string  $id
	 * @param  array   $session
	 * @return void
	 */
	abstract protected function createSession($id, array $session);

	/**
	 * Update an existing session in storage.
	 *
	 * @param  string  $id
	 * @param  array   $session
	 * @return void
	 */
	abstract protected function updateSession($id, array $session);

	/**
	 * Load the session for the request.
	 *
	 * @param  Symfony\Component\HttpFoundation\Request  $request
	 * @return void
	 */
	public function start(Request $request)
	{
		$id = $request->cookies->get($this->getCookieName());

		// If the session ID was available via the request cookies, we'll just
		// retrieve the session payload from the driver and check the given
		// session on validity. All data fetching is driver implemented.
		if ( ! is_null($id))
		{
			$session = $this->retrieveSession($id);
		}

		// If the session is not valid, we will create a new payload and will
		// indicate that the session has not yet been created. The freshly
		// created session payload will be assigned a fresh session ID.
		if ($this->isInvalid($session))
		{
			$this->exists = false;

			$session = $this->createFreshSession();
		}

		// Once the session payload has been created or loaded we will set it
		// to an internal value that is managed by the driver. The values
		// are not persisted back into storage until session closing.
		$this->session = $session;
	}

	/**
	 * Create a fresh session payload.
	 *
	 * @return array
	 */
	protected function createFreshSession()
	{
		$flash = $this->createData();

		return array('id' => $this->createSessionID(), 'data' => $flash);
	}

	/**
	 * Create a new, empty session data array.
	 *
	 * @return array
	 */
	protected function createData()
	{
		return array(':old:' => array(), ':new:' => array());
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
	protected function isInvalid($session)
	{
		if ( ! is_array($session)) return true;

		return (time() - $session['last_activity']) > ($this->lifetime * 60);
	}

	/**
	 * Determine if the session contains a given item.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function has($key)
	{
		return ! is_null($this->get($key));
	}

	/**
	 * Get the requested item from the session.
	 *
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	public function get($key, $default = null)
	{
		$data = $this->session['data'];

		// First we will check for the value in the general session data and if it
		// is not present in that array we'll check the session flash datas to
		// get the data from there. If netiher is there we give the default.
		if (isset($data[$key]))
		{
			return $data[$key];
		}

		// Session flash data is only persisted for the next request into the app
		// which makes it convenient for temporary status messages or various
		// other strings. We'll check all of the flash data for the item.
		elseif (isset($data[':new:'][$key]))
		{
			return $data[':new:'][$key];
		}

		// The "old" flash data are the data flashed during the previous request
		// while the "new" data is the data flashed during the course of this
		// current request. Typically developers will be asking for "old".
		elseif (isset($data[':old:'][$key]))
		{
			return $data[':old:'][$key];
		}

		return $default instanceof Closure ? $default() : $default;
	}

	/**
	 * Put a key / value pair in the session.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function put($key, $value)
	{
		$this->session['data'][$key] = $value;
	}

	/**
	 * Flash a key / value pair to the session.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function flash($key, $value)
	{
		$this->session['data'][':new:'][$key] = $value;
	}

	/**
	 * Keep all of the session flash data from expiring.
	 *
	 * @return void
	 */
	public function reflash()
	{
		$old = $this->session['data'][':old:'];

		$this->session['data'][':new:'] = array_merge($this->session['data'][':new:'], $old);
	}

	/**
	 * Keep a session flash item from expiring.
	 *
	 * @param  string|array  $keys
	 * @return void
	 */
	public function keep($keys)
	{
		foreach ((array) $keys as $key)
		{
			$this->flash($key, $this->get($key));
		}
	}

	/**
	 * Remove an item from the session.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function forget($key)
	{
		unset($this->session['data'][$key]);
	}

	/**
	 * Remove all of the items from the session.
	 *
	 * @return void
	 */
	public function flush()
	{
		$this->session['data'] = $this->createData();
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
	 * Finish the session handling for the request.
	 *
	 * @param  Symfony\Component\HttpFoundation\Response  $response
	 * @return void
	 */
	public function finish(Response $response)
	{
		// First we will set the last activity timestamp on the session and age
		// the session flash data so the old flash data is gone on following
		// requests. Then we'll call the driver methods to store the data.
		$this->session['last_activity'] = time();

		$id = $this->getSessionID();

		$this->ageFlashData();

		// We'll distinguish between updating and creating sessions in case it
		// matters to the driver. Most drivers will probably be able to use
		// the same code regardless of whether the session is new or not.
		if ($this->exists)
		{
			$this->updateSession($id, $this->session);
		}
		else
		{
			$this->createSession($id, $this->session);
		}

		// If the driver implements the Sweeper interface and hits the sweeper
		// lottery, we will sweep sessoins from storage that are expired so
		// the storage spot does not get junked up with expired sessions.
		if ($this instanceof Sweeper and $this->lottery())
		{
			$this->sweep(time() - ($this->lifetime * 60));
		}

		$response->headers->setCookie($this->createCookie());
	}

	/**
	 * Age the session flash data.
	 *
	 * @return void
	 */
	protected function ageFlashData()
	{
		$this->session['data'][':old:'] = $this->session['data'][':new:'];

		$this->session['data'][':new:'] = array();
	}

	/**
	 * Determine if the request hits the sweeper lottery.
	 *
	 * @return bool
	 */
	protected function lottery()
	{
		return mt_rand(1, $this->sweep[1]) <= $this->sweep[0];
	}

	/**
	 * Create a cookie instance for the session.
	 *
	 * @return Symfony\Component\HttpFoundation\Cookie
	 */
	protected function createCookie()
	{
		$expiration = time() + ($this->lifetime * 60);

		// The value of the cookie will be set to the current session ID as
		// that will allow us identify the session on subsequent requests
		// to the application since we can look-up the payloads by IDs.
		$value = $this->getSessionID();

		return $this->buildCookie($value, $expiration);
	}

	/**
	 * Create a new Symfony cookie instance.
	 *
	 * @param  string   $value
	 * @param  int      $expiratoin
	 * @return Symfony\Component\HttpFoundation\Cookie
	 */
	protected function buildCookie($value, $expiration)
	{
		extract($this->cookie);

		return new Cookie($name, $value, $expire, $path, $domain, $secure, $http_only);
	}

	/**
	 * Get the session payload.
	 *
	 * @var array
	 */
	public function getSession()
	{
		return $this->session;
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
	 * Get the session's last activity UNIX timestamp.
	 *
	 * @return int
	 */
	public function getLastActivity()
	{
		if (isset($this->session['last_activity'])) return $this->session['last_activity'];
	}

	/**
	 * Determine if the session exists in storage.
	 *
	 * @return bool
	 */
	public function sessionExists()
	{
		return $this->exists;
	}

	/**
	 * Get the session cookie name.
	 *
	 * @return string
	 */
	public function getCookieName()
	{
		return $this->getCookieOption('name');
	}

	/**
	 * Set the session cookie name.
	 *
	 * @param  string  $name
	 * @return void
	 */
	public function setCookieName($name)
	{
		return $this->setCookieOption('name', $name);
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

	/**
	 * Set the chances of hitting the Sweeper lottery.
	 *
	 * @param  int   $chance
	 * @param  int   $out_of
	 * @return void
	 */
	public function setSweepLottery($chance, $out_of)
	{
		$this->sweep = array($chance, $out_of);
	}

	/**
	 * Determine if the given offset exists.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function offsetExists($key)
	{
		return $this->has($key);
	}

	/**
	 * Get the value at a given offset.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function offsetGet($key)
	{
		return $this->get($key);
	}

	/**
	 * Store a value at the given offset.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function offsetSet($key, $value)
	{
		return $this->put($key, $value);
	}

	/**
	 * Remove the value at a given offset.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function offsetUnset($key)
	{
		$this->forget($key);
	}

}
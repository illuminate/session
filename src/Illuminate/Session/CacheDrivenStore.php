<?php namespace Illuminate\Session;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheDrivenStore extends Store {

	/**
	 * The cache store instance.
	 *
	 * @var Illuminate\Cache\Store
	 */
	protected $cache;

	/**
	 * Create a new Memcache session instance.
	 *
	 * @param  Illuminate\Cache\Store  $cache
	 * @return void
	 */
	public function __construct(\Illuminate\Cache\Store $cache)
	{
		$this->cache = $cache;
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
		return $this->cache->get($id);
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
		$this->cache->forever($id, serialize($session));
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
		return $this->createSession($id, $session);
	}

}
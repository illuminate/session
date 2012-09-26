<?php

use Mockery as m;
use Illuminate\Encrypter;
use Illuminate\CookieJar;
use Illuminate\Session\CookieStore;
use Symfony\Component\HttpFoundation\Request;

class CookieStoreTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testRetrieveSessionProperlyDecryptsCookie()
	{
		$encrypter = new Encrypter('key', MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
		$store = new CookieStore($encrypter, $cookies = m::mock('Illuminate\CookieJar'));
		$session = $encrypter->encrypt(serialize($expect = array('id' => '1', 'data' => array('foo' => 'bar'), 'last_activity' => '9999999999')));
		$cookies->shouldReceive('get')->once()->with('illuminate_session')->andReturn(1);
		$cookies->shouldReceive('get')->once()->with('illuminate_payload')->andReturn($session);
		$store->start($cookies);
		$this->assertEquals($expect, $store->getSession());
	}


	public function testCreateSessionStoresCookiePayload()
	{
		$encrypter = new Encrypter('key', MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
		$cookie = $this->getCookieJar();
		$store = new Illuminate\Session\CookieStore($encrypter, $cookie);
		$session = array('id' => '1', 'data' => array(':old:' => array(), ':new:' => array()));
		$store->setSession($session);
		$store->setExists(false);
		$response = new Symfony\Component\HttpFoundation\Response;
		$store->finish($response, $cookie);

		$this->assertTrue(count($response->headers->getCookies()) == 2);
		$cookies = $response->headers->getCookies();
		$value = unserialize($encrypter->decrypt($cookie->parse($cookies[0]->getValue())));
	}


	public function testUpdateSessionCallsCreateSession()
	{
		$encrypter = new Encrypter(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC, 'key');
		$store = $this->storeMock(array('createSession'), 'Illuminate\Session\CookieStore', array($encrypter, $this->getCookieJar()));
		$session = array('id' => '1', 'data' => array(':old:' => array(), ':new:' => array()));
		$store->setSession($session);
		$store->expects($this->once())->method('createSession');
		$store->setExists(true);
		$store->finish($response = new Symfony\Component\HttpFoundation\Response, $this->getCookieJar());
	}


	protected function dummySession()
	{
		return array('id' => '123', 'data' => array(':old:' => array(), ':new:' => array()), 'last_activity' => '9999999999');
	}


	protected function storeMock($stub = array(), $class = 'Illuminate\Session\Store', $constructor = null)
	{
		return $this->getMock($class, $stub, $constructor);
	}


	protected function getCookieJar()
	{
		return new Illuminate\CookieJar(Request::create('/foo', 'GET'), 'foo-bar', array('path' => '/', 'domain' => 'foo.com', 'secure' => true, 'httpOnly' => true));
	}

}
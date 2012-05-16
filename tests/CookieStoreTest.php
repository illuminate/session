<?php

use Illuminate\Encrypter;
use Illuminate\Session\CookieStore;
use Symfony\Component\HttpFoundation\Request;

class CookieStoreTest extends PHPUnit_Framework_TestCase {

	public function testRetrieveSessionProperlyDecryptsCookie()
	{
		$encrypter = new Encrypter(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC, 'key');
		$store = new CookieStore($encrypter);
		$session = $encrypter->encrypt(serialize($expect = array('id' => '1', 'data' => array('foo' => 'bar'), 'last_activity' => '9999999999')));
		$request = Request::create('/', 'GET', array(), array('illuminate_session' => 1, 'illuminate_payload' => $session));
		$store->start($request);
		$this->assertEquals($expect, $store->getSession());
	}


	public function testCreateSessionStoresCookiePayload()
	{
		$encrypter = new Encrypter(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC, 'key');
		$store = $this->storeMock(array('getCurrentTime'), 'Illuminate\Session\CookieStore', array($encrypter));
		$session = array('id' => '1', 'data' => array(':old:' => array(), ':new:' => array()));
		$store->setSession($session);
		$store->expects($this->any())->method('getCurrentTime')->will($this->returnValue(1));
		$store->setExists(false);
		$store->finish($response = new Symfony\Component\HttpFoundation\Response);

		$this->assertTrue(count($response->headers->getCookies()) == 2);
		$cookies = $response->headers->getCookies();
		$value = unserialize($encrypter->decrypt($cookies[0]->getValue()));
		$session['last_activity'] = 1;
		$this->assertEquals($session, $value);
	}


	public function testUpdateSessionCallsCreateSession()
	{
		$encrypter = new Encrypter(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC, 'key');
		$store = $this->storeMock(array('createSession'), 'Illuminate\Session\CookieStore', array($encrypter));
		$session = array('id' => '1', 'data' => array(':old:' => array(), ':new:' => array()));
		$store->setSession($session);
		$store->expects($this->once())->method('createSession');
		$store->setExists(true);
		$store->finish($response = new Symfony\Component\HttpFoundation\Response);
	}


	protected function dummySession()
	{
		return array('id' => '123', 'data' => array(':old:' => array(), ':new:' => array()), 'last_activity' => '9999999999');
	}


	protected function storeMock($stub = array(), $class = 'Illuminate\Session\Store', $constructor = null)
	{
		return $this->getMock($class, $stub, $constructor);
	}

}
<?php

use Symfony\Component\HttpFoundation\Request;

class StoreTest extends PHPUnit_Framework_TestCase {

	public function testValidSessionIsSet()
	{
		$store = $this->storeMock('isInvalid');
		$session = $this->dummySession();
		$request = Request::create('/', 'GET', array(), array('illuminate_session' => 'foo'));
		$store->expects($this->once())->method('retrieveSession')->with($this->equalTo('foo'))->will($this->returnValue($session));
		$store->expects($this->once())->method('isInvalid')->with($this->equalTo($session))->will($this->returnValue(false));
		$store->start($request);
		$this->assertEquals($session, $store->getSession());
	}


	public function testInvalidSessionCreatesFresh()
	{
		$store = $this->storeMock('isInvalid');
		$session = $this->dummySession();
		$request = Request::create('/', 'GET', array(), array('illuminate_session' => 'foo'));
		$store->expects($this->once())->method('retrieveSession')->with($this->equalTo('foo'))->will($this->returnValue($session));
		$store->expects($this->once())->method('isInvalid')->with($this->equalTo($session))->will($this->returnValue(true));
		$store->start($request);

		$session = $store->getSession();
		$this->assertFalse($store->sessionExists());
		$this->assertTrue(strlen($session['id']) == 40);
		$this->assertFalse(isset($session['last_activity']));
	}


	public function testOldSessionsAreConsideredInvalid()
	{
		$store = $this->storeMock('createFreshSession');
		$request = Request::create('/', 'GET', array(), array('illuminate_session' => 'foo'));
		$session = $this->dummySession();
		$session['last_activity'] = '1111111111';
		$store->expects($this->once())->method('retrieveSession')->with($this->equalTo('foo'))->will($this->returnValue($session));
		$store->expects($this->once())->method('createFreshSession');
		$store->start($request);
	}


	public function testNullSessionsAreConsideredInvalid()
	{
		$store = $this->storeMock('createFreshSession');
		$request = Request::create('/', 'GET', array(), array('illuminate_session' => 'foo'));
		$store->expects($this->once())->method('retrieveSession')->with($this->equalTo('foo'))->will($this->returnValue(null));
		$store->expects($this->once())->method('createFreshSession');
		$store->start($request);
	}


	protected function dummySession()
	{
		return array('id' => '123', 'data' => array(':old:' => array(), ':new:' => array()), 'last_activity' => '9999999999');
	}


	protected function storeMock($stub = array())
	{
		$stub = array_merge((array) $stub, array('retrieveSession', 'createSession', 'updateSession'));
		return $this->getMock('Illuminate\Session\Store', $stub);
	}

}
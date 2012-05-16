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
<?php

use Mockery as m;

class FileStoreTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testStoreRequiresFileStoreCache()
	{
		$cache = new Illuminate\Cache\ArrayStore;
		$files = new Illuminate\Session\FileStore($cache);
	}


	public function testSweepCleansDirectory()
	{
		$mock = m::mock('Illuminate\Filesystem');

		$cache = new Illuminate\Cache\FileStore($mock, __DIR__);
		$store = new Illuminate\Session\FileStore($cache);

		$files = array(__DIR__.'/foo.txt', __DIR__.'/bar.txt');

		$mock->shouldReceive('files')->with(__DIR__)->andReturn($files);
		$mock->shouldReceive('lastModified')->with(__DIR__.'/foo.txt')->andReturn(1);
		$mock->shouldReceive('lastModified')->with(__DIR__.'/bar.txt')->andReturn(9999999999);
		$mock->shouldReceive('delete')->with(__DIR__.'/foo.txt');

		$store->sweep(500);
	}

}
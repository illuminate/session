<?php namespace Illuminate\Session;

class FileStore extends CacheDrivenStore implements Sweeper {

	/**
	 * Create a new File based session store.
	 *
	 * @param  Illuminate\Cache\Store  $cache
	 * @return void
	 */
	public function __construct(\Illuminate\Cache\Store $cache)
	{
		if ( ! $cache instanceof \Illuminate\Cache\FileStore)
		{
			throw new \InvalidArgumentException("File session driver requires file cache.");
		}

		parent::__construct($cache);
	}

	/**
	 * Remove session records older than a given expiration.
	 *
	 * @param  int   $expiration
	 * @return void
	 */
	public function sweep($expiration)
	{
		$files = $this->cache->getFilesystem();

		foreach ($files->files($this->cache->getDirectory()) as $file)
		{
			// If the last modification timestamp is less than the given UNIX
			// expiration timestamp, it indicates the session has expired
			// and should be removed off disk to de-clutter the files.
			if ($files->lastModified($file) < $expiration)
			{
				$files->delete($file);
			}
		}
	}

}
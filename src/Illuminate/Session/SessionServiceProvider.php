<?php namespace Illuminate\Session;

use Illuminate\Support\ServiceProvider;

class SessionServiceProvider extends ServiceProvider {

	/**
	 * Bootstrap the application events.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	public function boot($app)
	{
		$this->registerSessionEvents($app);
	}

	/**
	 * Register the service provider.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	public function register($app)
	{
		$app['session'] = $app->share(function($app)
		{
			$manager = new SessionManager($app);

			$driver = $manager->driver();

			// Once we get an instance of the session driver, we need to set a few of
			// the session options based on the application configuration, such as
			// the session lifetime and the sweeper lottery configuration value.
			$driver->setLifetime($app['config']['session.lifetime']);

			$driver->setSweepLottery($app['config']['session.lottery']);

			return $driver;
		});

		$this->addSessionFilter($app);
	}

	/**
	 * Register the events needed for session management.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	protected function registerSessionEvents($app)
	{
		// The session needs to be started and closed, so we will register a before
		// and after event to do all that for us. This will manage the loading
		// the session payloads as well as writing them after each request.
		$app->before(function($request) use ($app)
		{
			$app['session']->start($app['cookie']);
		});

		$app->close(function($request, $response) use ($app)
		{
			$app['session']->finish($response, $app['cookie']);
		});
	}

	/**
	 * Register the CSRF filter for the application.
	 *
	 * @param  Illuminate\Foundation\Application  $app
	 * @return void
	 */
	protected function addSessionFilter($app)
	{
		$app->addFilter('csrf', function() use ($app)
		{
			// The "csrf" middleware provides a simple middleware for checking that a
			// CSRF token in the request inputs matches the CSRF token stored for
			// the user in the session data. If it doesn't, we will bail out.
			$token = $app['session']->getToken();

			if ($token !== $app['request']->get('csrf_token'))
			{
				throw new TokenMismatchException;
			}
		});
	}

}
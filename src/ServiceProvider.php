<?php namespace AndrewLrrr\LaravelProjectBuilder;

use AndrewLrrr\LaravelProjectBuilder\Commands\BuildCommand;
use AndrewLrrr\LaravelProjectBuilder\Utils\Git;
use AndrewLrrr\LaravelProjectBuilder\Utils\Shell;
use Symfony\Component\Console\Output\BufferedOutput;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$configPath = __DIR__ . '/../config/builder.php';
		$this->mergeConfigFrom($configPath, 'builder');

		$this->app->singleton('project.builder', function ($app) {
			$basePath       = function_exists('base_path') ? base_path() : __DIR__ . '/../';
			$shell          = new Shell(new BufferedOutput(), $basePath);
			$git            = new Git($shell);
			$vscManager     = new VSCManager($git);
			$releaseManager = new ReleaseManager($shell, $vscManager);

			$releaseManager->register('down', function () {
				return $this->shell->execArtisan('down')->toString();
			}, 'Putting the application into maintenance mode...');

			$releaseManager->register('set_last_commit_hash', function () {
				$this->vscManager->setLastCommitHash();
			}, 'Fixing current git commit before pull...');

			$releaseManager->register('git_clean', function () {
				return $this->vscManager->clean();
			}, 'Removing untracked files...');

			$releaseManager->register('git_reset', function () {
				return $this->vscManager->reset();
			}, 'Resetting git local changes...');

			$releaseManager->register('git_checkout', function () {
				return $this->vscManager->checkout();
			}, 'Check outing to git master branch...');

			$releaseManager->register('git_pull', function () {
				return $this->vscManager->pull();
			}, 'Pulling latest changes...');

			$releaseManager->register('migrations', function () {
				return $this->shell->execArtisan('migrate', ['--force' => true])->toString();
			}, 'Running migrations...');

			$releaseManager->register('composer_update', function () {
				$force = (bool) $this->option('composer-update');
				if ($force || $this->vscManager->findBy('composer update')) {
					return $this->shell->execCommand('composer update')->toString();
				}
			}, 'Defining if composer needs to be updated...');

			$releaseManager->register('up', function () {
				return $this->shell->execArtisan('up')->toString();
			}, 'Bringing the application out of maintenance mode!');

			return $releaseManager;
		});

		$this->app->alias('project.builder', 'AndrewLrrr\LaravelProjectBuilder\ReleaseManager');

		$this->app->singleton('command.project.builder', function ($app) {
			return new BuildCommand($app['project.builder']);
		});

		$this->commands(['command.project.builder']);
	}

	/**
	 * Perform post-registration booting of services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->publishes([
			__DIR__.'/../config/builder.php' => config_path('builder.php')
		], 'config');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return ['project.builder'];
	}
}

<?php namespace AndrewLrrr\LaravelProjectBuilder;

use AndrewLrrr\LaravelProjectBuilder\Commands\ProjectRelease;
use AndrewLrrr\LaravelProjectBuilder\Utils\Git;
use AndrewLrrr\LaravelProjectBuilder\Utils\Shell;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->singleton('project.builder', function ($app) {
			$basePath       = function_exists('base_path') ? base_path() : __DIR__ . '/../';
			$shell          = new Shell(new BufferedOutput(), $basePath);
			$git            = new Git($shell);
			$vscManager     = new VSCManager($git);
			$releaseManager = new ReleaseManager($shell, $vscManager);

			$releaseManager->register('down', function () {
				return $this->shell->execArtisan('down');
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
				Artisan::call('migrate', ['--force' => true]);
			}, 'Running migrations...');

			$releaseManager->register('composer_update', function () {
				$force = (bool) $this->option('cu');
				if ($force || $this->vscManager->findBy(['(composer updated)', '(composer update)'])) {
					return $this->shell->execCommand('composer update');
				}
			}, 'Defining if composer needs to be updated...');

			$releaseManager->register('npm_install', function () {
				$force = (bool) $this->option('ni');
				if ($force || $this->vscManager->findBy(['(npm installed)', '(npm install)'])) {
					return $this->shell->execCommand('npm install');
				}
			}, 'Defining if npm needs to be installed...');

			$releaseManager->register('npm_update', function () {
				$force = (bool) $this->option('nu');
				if ($force || $this->vscManager->findBy(['(npm updated)', '(npm update)'])) {
					return $this->shell->execCommand('npm update');
				}
			}, 'Defining if npm needs to be updated...');

			$releaseManager->register('optimize', function () {
				if (function_exists('app') && app()->environment('production')) {
					return $this->shell->execArtisan('optimize');
				}
			}, 'Optimizing the framework for better performance...');

			$releaseManager->register('config_cache', function () {
				if (function_exists('app') && app()->environment('production')) {
					return $this->shell->execArtisan('config:cache');
				}
			}, 'Creating a cache file for faster configuration loading...');

			$releaseManager->register('route_cache', function () {
				if (function_exists('app') && app()->environment('production')) {
					return $this->shell->execArtisan('route:cache');
				}
			}, 'Creating a route cache file for faster route registration...');

			$releaseManager->register('laravel-mix', function () {
				if (function_exists('app')) {
					return $this->shell->execCommand('npm run ' . (app()->environment('production') ? 'production' : 'dev'));
				}
			}, 'Building frontend with Laravel Mix...');

			$releaseManager->register('dump-autoload', function () {
				return $this->shell->execCommand('composer dump-autoload');
			}, 'Performing composer dump-autoload...');

			$releaseManager->register('up', function () {
				return $this->shell->execArtisan('up');
			}, 'Bringing the application out of maintenance mode...');
		});

		$this->app->alias('project.builder', 'AndrewLrrr\LaravelProjectBuilder\ReleaseManager');

		$this->app->singleton('command.project.builder', function ($app) {
			return new ProjectRelease($app['project.builder']);
		});

		$this->commands(['command.project.builder']);
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

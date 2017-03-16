<?php namespace AndrewLrrr\LaravelProjectBuilder;

use AndrewLrrr\LaravelProjectBuilder\Commands\ClearCommand;
use AndrewLrrr\LaravelProjectBuilder\Commands\ProjectRelease;
use AndrewLrrr\LaravelProjectBuilder\Utils\Git;
use AndrewLrrr\LaravelProjectBuilder\Utils\Shell;
use Illuminate\Support\Facades\Artisan;

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
			$shell          = new Shell($basePath);
			$git            = new Git($shell);
			$vscManager     = new VSCManager($git);
			$releaseManager = new ReleaseManager($shell);

			$releaseManager->register('down', function () {
				Artisan::call('down');
			}, 'Put the application into maintenance mode');

			$releaseManager->register('git_clean', function () use ($git) {
				return $git->clean();
			}, 'Removing untracked files');

			$releaseManager->register('git_reset', function () use ($git) {
				return $git->reset();
			}, 'Resetting git local changes');

			$releaseManager->register('git_checkout', function () use ($git) {
				return $git->checkout();
			}, 'Check outing to git master branch');

			$releaseManager->register('git_pull', function () use ($git) {
				return $git->pull();
			}, 'Pulling latest changes');

			$releaseManager->register('migrations', function () {
				Artisan::call('migrate', ['--force' => true]);
			}, 'Running migrations');

			$releaseManager->register('composer_update', function ($force) use ($vscManager) {
				if ($force || $vscManager->needUpdateComposer()) {
					return $this->shell->execCommand('composer update');
				}
			}, 'Defining if composer needs to be updated...');

			$releaseManager->register('npm_install', function ($force) use ($vscManager) {
				if ($force || $vscManager->needInstallNpm()) {
					return $this->shell->execCommand('npm update');
				}
			}, 'Defining if npm needs to be installed...');

			$releaseManager->register('npm_update', function ($force) use ($vscManager) {
				if ($force || $vscManager->needUpdateNpm()) {
					return $this->shell->execCommand('npm install');
				}
			}, 'Defining if npm needs to be updated...');

			$releaseManager->register('optimize', function () {
				if (function_exists('app') && app()->environment('production')) {
					Artisan::call('optimize');
				}
			}, 'Optimizing the framework for better performance');

			$releaseManager->register('cache_config', function () {
				if (function_exists('app') && app()->environment('production')) {
					Artisan::call('config:cache');
				}
			}, 'Optimizing the framework for better performance');

			$releaseManager->register('laravel-mix', function () {
				if (function_exists('app')) {
					return $this->shell->execCommand('npm run ' . (app()->environment('production') ? 'production' : 'dev'));
				}
			}, 'Build frontend');

			$releaseManager->register('dump-autoload', function () {
				return $this->shell->execCommand('composer dump-autoload');
			}, 'Performing composer dump-autoload');

			$releaseManager->register('cache_route', function () {
				Artisan::call('up');
			}, 'Bring the application out of maintenance mode');
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
		return ['project.builder', 'command.debugbar.clear'];
	}
}

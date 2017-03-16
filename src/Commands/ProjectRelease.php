<?php

namespace AndrewLrrr\LaravelProjectBuilder\Commands;

use AndrewLrrr\LaravelProjectBuilder\Exceptions\ShellException;
use AndrewLrrr\LaravelProjectBuilder\ReleaseManager;
use Illuminate\Console\Command;

class ProjectRelease extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'project:release {-cu} {-nu} {-ni}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Build release project';

	/**
	 * @var ProjectRelease
	 */
	protected $releaseManager;

	/**
	 * ProjectRelease constructor.
	 *
	 * @param ReleaseManager $releaseManager
	 */
	public function __construct(ReleaseManager $releaseManager)
	{
		parent::__construct();
		$this->releaseManager = $releaseManager;
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{
		$doComposerUpdate = (bool) $this->option('cu');
		$doNpmUpdate      = (bool) $this->option('nu');
		$doNpmInstall     = (bool) $this->option('ni');
		
		foreach ($this->releaseManager->getActions() as $action) {
			try {
				$this->info($this->releaseManager->getActionMessage($action));
				if ($action === 'composer_update') {
					$this->info($this->releaseManager->$action($doComposerUpdate));
				}
				if ($action === 'npm_install') {
					$this->info($this->releaseManager->$action($doNpmInstall));
				}
				if ($action === 'npm_update') {
					$this->info($this->releaseManager->$action($doNpmUpdate));
				}
				$this->info($this->releaseManager->$action());
			} catch (ShellException $se) {
				$this->error($se);
			} catch (\BadFunctionCallException $be) {
				$this->error($be);
			}
		}
	}
}

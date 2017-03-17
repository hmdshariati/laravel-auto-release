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
	protected $signature = 'project:build {--cu} {--nu} {--ni}';

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
		$this->releaseManager->setOptions($this->input->getOptions());

		foreach ($this->releaseManager->getActions() as $action) {
			try {
				$message = $this->releaseManager->getActionMessage($action);
				$result  = $this->releaseManager->$action();
				if (! empty($message)) {
					$this->info($message);
				}
				if (! empty($result)) {
					$this->line($result);
				} elseif (! empty($message)) {
					$this->line('');
				}
			} catch (ShellException $se) {
				$this->error($se);
			} catch (\BadFunctionCallException $be) {
				$this->error($be);
			}
		}
	}
}
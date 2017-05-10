<?php

namespace AndrewLrrr\LaravelAutoRelease\Commands;

use AndrewLrrr\LaravelAutoRelease\Exceptions\ShellException;
use AndrewLrrr\LaravelAutoRelease\ReleaseManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class ReleaseCommand extends Command
{
	/**
	 * @var ReleaseManager
	 */
	protected $releaseManager;

	/**
	 * ProjectRelease constructor.
	 *
	 * @param ReleaseManager $releaseManager
	 */
	public function __construct(ReleaseManager $releaseManager)
	{
		$this->signature   = Config::get('release.command.signature');
		$this->description = Config::get('release.command.description');
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

		foreach ($this->releaseManager->getCommands() as $action) {
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
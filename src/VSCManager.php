<?php

namespace AndrewLrrr\LaravelProjectBuilder;

use AndrewLrrr\LaravelProjectBuilder\Utils\Git;

class VSCManager
{
	const COMMITS_HISTORY_DEPTH = 10;

	/**
	 * @var Git
	 */
	protected $git;

	/**
	 * @var string|null
	 */
	protected $lastCommitHash = null;

	/**
	 * VSCManager constructor.
	 *
	 * @param Git $git
	 */
	public function __construct(Git $git)
	{
		$this->git = $git;
	}

	/**
	 * @return void
	 */
	public function setLastCommitHash()
	{
		$this->lastCommitHash = $this->git->log(1)->first()->hash;
	}

	/**
	 * @return string
	 */
	public function getLastCommitHash()
	{
		return $this->lastCommitHash;
	}

	/**
	 * @return bool
	 */
	public function needUpdateComposer()
	{
		return ($this->compareCommits('(composer updated)') || $this->compareCommits('(composer update)'));
	}

	/**
	 * @return bool
	 */
	public function needUpdateNpm()
	{
		return ($this->compareCommits('(npm updated)') || $this->compareCommits('(npm update)'));
	}

	/**
	 * @return bool
	 */
	public function needInstallNpm()
	{
		return ($this->compareCommits('(npm installed)') || $this->compareCommits('(npm install)'));
	}

	/**
	 * @param string $message
	 *
	 * @return bool
	 */
	protected function compareCommits($message)
	{
		$commits = $this->git->log(self::COMMITS_HISTORY_DEPTH, ['message']);

		foreach ($commits as $commit) {
			if ($commit->hash === $this->lastCommitHash) {
				return false;
			}

			if (str_contains(strtolower($commit->message), $message)) {
				return true;
			}
		}

		return false;
	}
}
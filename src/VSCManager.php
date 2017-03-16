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
	 * @return string
	 */
	public function clean()
	{
		return $this->git->clean();
	}

	/**
	 * @param string $branch
	 *
	 * @return string
	 */
	public function checkout($branch = 'master')
	{
		return $this->git->checkout($branch);
	}

	/**
	 * @param string $remote
	 * @param string $branch
	 *
	 * @return string
	 */
	public function pull($remote = 'origin', $branch = 'master')
	{
		return $this->git->pull($remote, $branch);
	}

	/**
	 * @return string
	 */
	public function reset()
	{
		return $this->git->reset();
	}

	/**
	 * @param mixed $needles
	 *
	 * @return bool
	 */
	public function findBy($needles)
	{
		if (! is_array($needles)) {
			$needles = [(string) $needles];
		}

		return $this->compareCommits($needles);
	}

	/**
	 * @param array $needles
	 *
	 * @return bool
	 */
	protected function compareCommits(array $needles)
	{
		$commits = $this->git->log(self::COMMITS_HISTORY_DEPTH, ['message']);

		foreach ($needles as $needle) {
			foreach ($commits as $commit) {
				if ($commit->hash === $this->lastCommitHash) {
					break;
				}

				if (str_contains(strtolower($commit->message), $needle)) {
					return true;
				}
			}
		}

		return false;
	}
}
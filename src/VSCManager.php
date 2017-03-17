<?php

namespace AndrewLrrr\LaravelProjectBuilder;

use AndrewLrrr\LaravelProjectBuilder\Traits\ConfigHelper;
use AndrewLrrr\LaravelProjectBuilder\Utils\Git;

class VSCManager
{
	use ConfigHelper;

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
		return $this->git->clean()->toString();
	}

	/**
	 * @return string
	 */
	public function checkout()
	{
		return $this->git->checkout(
			$this->findConfigOrDefault('builder.vsc.branch', 'master')
		)->toString();
	}

	/**
	 * @return string
	 */
	public function pull()
	{
		return $this->git->pull(
			$this->findConfigOrDefault('builder.vsc.branch', 'master'),
			$this->findConfigOrDefault('builder.vsc.remote', 'origin')
		)->toString();
	}

	/**
	 * @return string
	 */
	public function reset()
	{
		return $this->git->reset()->toString();
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
		$commits = $this->git->log(
			$this->findConfigOrDefault('builder.vsc.log_depth', 10),
			['message']
		);

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
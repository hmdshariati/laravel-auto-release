<?php

namespace AndrewLrrr\LaravelAutomateRelease\Utils;

class Git
{
	/**
	 * @var Shell
	 */
	protected $shell;

	/**
	 * Git constructor.
	 *
	 * @param Shell $shell
	 */
	public function __construct(Shell $shell)
	{
		$this->shell = $shell;
	}

	/**
	 * @param int $historyDepth
	 * @param array $fields
	 *
	 * @return array
	 */
	public function log($historyDepth = 1, array $fields = [])
	{
		$commits = $this->execShell($this->buildCommand($historyDepth, $fields), true);

		$commits = array_reduce($commits, function ($carry, $commit) {
			$commitData = array_reduce(explode('#', $commit), function ($carry, $item) {
				list($key, $value) = explode('=', $item);
				$carry[$key] = $value;
				return $carry;
			}, []);

			$commitHash = null;
			$commitInfo = [];

			foreach ($commitData as $key => $value) {
				if ($key === 'hash') {
					$commitHash = $value;
				}

				$commitInfo[$key] = $value;
			}

			$carry[$commitHash] = (object) $commitInfo;
			return $carry;
		}, []);

		return $commits;
	}

	/**
	 * @param string $fromCommitHah
	 * @param string $toCommitHash
	 *
	 * @return array
	 */
	public function diff($fromCommitHah, $toCommitHash)
	{
		return $this->execShell(sprintf('git diff --name-status %s %s', $fromCommitHah, $toCommitHash), true);
	}

	/**
	 * @return string
	 */
	public function clean()
	{
		return $this->execShell('git clean -f');
	}

	/**
	 * @param string $branch
	 *
	 * @return string
	 */
	public function checkout($branch = 'master')
	{
		return $this->execShell('git checkout ' . $branch);
	}

	/**
	 * @param string $branch
	 * @param string $remote
	 *
	 * @return string
	 */
	public function pull($branch = 'master', $remote = 'origin')
	{
		return $this->execShell(sprintf('git pull %s %s', $remote, $branch));
	}

	/**
	 * @return string
	 */
	public function reset()
	{
		return $this->execShell('git reset --hard');
	}

	/**
	 * @param int $historyDepth
	 * @param array $fields
	 *
	 * @return string
	 */
	protected function buildCommand($historyDepth, array $fields)
	{
		$format = 'hash=%H';

		foreach ($fields as $field) {
			switch ($field) {
				case 'message':
					$format .= '#message=%B';
					break;
				case 'author':
					$format .= '#author=%an';
					break;
				case 'email':
					$format .= '#email=%ae';
					break;
				case 'date':
					$format .= '#date=%ad';
					break;
			}
		}

		if ($historyDepth < 1) {
			$historyDepth = 1;
		}

		return 'git log -' . $historyDepth . ' --pretty=format:"' . $format . '"';
	}

	/**
	 * @param string $command
	 * @param bool $toArray
	 *
	 * @return string|array
	 */
	protected function execShell($command, $toArray = false)
	{
		$result = $this->shell->execCommand($command);

		if ($toArray) {
			return $result->toArray();
		}

		return $result->toString();
	}
}
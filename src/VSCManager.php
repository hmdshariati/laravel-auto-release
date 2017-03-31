<?php

namespace AndrewLrrr\LaravelAutoRelease;

use AndrewLrrr\LaravelAutoRelease\Traits\ConfigHelper;
use AndrewLrrr\LaravelAutoRelease\Utils\Git;
use Illuminate\Support\Arr;

class VSCManager
{
	use ConfigHelper;

	const MODIFIED = 'M';

	const DELETED = 'D';

	const ADDED = 'A';

	/**
	 * @var Git
	 */
	protected $git;

	/**
	 * @var string|null
	 */
	protected $lastCommitHash = null;

	/**
	 * @var null|array
	 */
	protected $files;

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
		$this->lastCommitHash = Arr::first($this->git->log(1))->hash;
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
	 * @return string
	 */
	public function checkout()
	{
		return $this->git->checkout(
			$this->findConfigOrDefault('release.vsc.branch', 'master')
		);
	}

	/**
	 * @return string
	 */
	public function pull()
	{
		return $this->git->pull(
			$this->findConfigOrDefault('release.vsc.branch', 'master'),
			$this->findConfigOrDefault('release.vsc.remote', 'origin')
		);
	}

	/**
	 * @return string
	 */
	public function reset()
	{
		return $this->git->reset();
	}

	/**
	 * @return array
	 */
	public function files()
	{
		return $this->cleanFileNames($this->getFiles());
	}

	/**
	 * @return array
	 */
	public function modifiedFiles()
	{
		return $this->getFilesByKey(self::MODIFIED);
	}

	/**
	 * @return array
	 */
	public function addedFiles()
	{
		return $this->getFilesByKey(self::ADDED);
	}

	/**
	 * @return array
	 */
	public function deletedFiles()
	{
		return $this->getFilesByKey(self::DELETED);
	}

	/**
	 * @return array
	 */
	protected function getFiles()
	{
		if (is_null($this->lastCommitHash)) {
			return [];
		}

		if (is_null($this->files)) {
			$currentCommitHash = Arr::first($this->git->log(1))->hash;
			$this->files = $this->git->diff($this->lastCommitHash, $currentCommitHash);
		}

		return $this->files;
	}

	/**
	 * @param array $files
	 *
	 * @return array
	 */
	protected function cleanFileNames(array $files)
	{
		return array_map(function ($file) {
			return trim(preg_replace('/[\w]+\s+?/', '', $file));
		}, $files);
	}

	/**
	 * @param $key
	 *
	 * @return array
	 */
	protected function getFilesByKey($key)
	{
		$files = $this->getFiles();

		if (! empty($files)) {
			$files = array_filter($files, function ($file) use ($key) {
				return starts_with($file, $key);
			});

			return array_values($this->cleanFileNames($files));
		}

		return [];
	}
}
<?php

namespace AndrewLrrr\LaravelProjectBuilder\Utils;

use AndrewLrrr\LaravelProjectBuilder\Exceptions\ShellException;

class Shell
{
	/**
	 * @var array
	 */
	protected $out = [];

	/**
	 * @var string
	 */
	protected $path = null;

	/**
	 * Shell constructor.
	 *
	 * @param string $path
	 */
	public function __construct($path = null)
	{
		$this->path = $path;
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return implode("\n", $this->out);
	}

	/**
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * @param string $path
	 */
	public function setPath($path)
	{
		$this->path = $path;
	}

	/**
	 * @return array
	 */
	public function getOut()
	{
		return $this->out;
	}

	/**
	 * @param string $command
	 *
	 * @return Shell
	 * @throws ShellException
	 */
	public function execCommand($command)
	{
		if ($this->path) {
			$command = 'cd ' . $this->path . '; ' . $command;
		}

		exec($command . ' 2>&1', $this->out, $error);

		if ($error) {
			throw new ShellException($error);
		}

		return $this;
	}
}
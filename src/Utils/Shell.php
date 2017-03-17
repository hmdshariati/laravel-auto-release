<?php

namespace AndrewLrrr\LaravelProjectBuilder\Utils;

use AndrewLrrr\LaravelProjectBuilder\Exceptions\ShellException;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

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
	 * @var BufferedOutput
	 */
	protected $buffer;

	/**
	 * Shell constructor.
	 *
	 * @param BufferedOutput $buffer
	 * @param string $path
	 */
	public function __construct(BufferedOutput $buffer, $path = null)
	{
		$this->buffer = $buffer;
		$this->path   = $path;
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		return $this->toString();
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
	public function toArray()
	{
		return array_filter($this->out, function ($out) {
			return ! empty($out);
		});
	}

	/**
	 * @return string
	 */
	public function toString()
	{
		return rtrim(implode("\n", $this->out), "\n") . "\n";
	}

	/**
	 * @param string $command
	 *
	 * @return Shell
	 * @throws ShellException
	 */
	public function execCommand($command)
	{
		$this->out = [];

		if ($this->path) {
			$command = 'cd ' . $this->path . '; ' . $command;
		}

		exec($command . ' 2>&1', $this->out, $error);

		if ($error) {
			throw new ShellException($error);
		}

		return $this;
	}

	/**
	 * @param string $command
	 * @param array $parameters
	 *
	 * @return Shell
	 */
	public function execArtisan($command, array $parameters = [])
	{
		$this->out = [];

		Artisan::call($command, $parameters, $this->buffer);
		$this->out[] = $this->buffer->fetch();

		return $this;
	}
}
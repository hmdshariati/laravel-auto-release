<?php

namespace AndrewLrrr\LaravelAutoRelease\Utils;

use AndrewLrrr\LaravelAutoRelease\Exceptions\ShellException;
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
		if (! empty($this->out)) {
			return rtrim(implode("\n", $this->out), "\n") . "\n";
		}

		return '';
	}

	/**
	 * @param string $command
	 * @param string $path
	 *
	 * @return Shell
	 */
	public function execCommand($command, $path = null)
	{
		$this->out = [];

		$path = $path ? $path : $this->path;

		if ($path) {
			$command = 'cd ' . $path . ' && ' . $command;
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
		$out = $this->buffer->fetch();

		if (! empty($out)) {
			$this->out[] = $out;
		}

		return $this;
	}
}
<?php

namespace AndrewLrrr\LaravelProjectBuilder;

use AndrewLrrr\LaravelProjectBuilder\Exceptions\ReleaseManagerException;
use AndrewLrrr\LaravelProjectBuilder\Utils\Shell;
use BadMethodCallException;
use Closure;
use Illuminate\Support\Facades\Config;

class ReleaseManager
{
	/**
	 * @var Shell
	 */
	protected $shell;

	/**
	 * @var VSCManager
	 */
	protected $vscManager;

	/**
	 * @var string|null
	 */
	protected $after = null;

	/**
	 * @var string|null
	 */
	protected $before = null;

	/**
	 * @var array
	 */
	protected $methods = [];

	/**
	 * @var array
	 */
	protected $actions = [];

	/**
	 * @var array
	 */
	protected $options = [];

	/**
	 * @var array
	 */
	protected $watch = [];

	/**
	 * @var array
	 */
	protected $actionsMessages = [];

	/**
	 * BuildManager constructor.
	 *
	 * @param Shell $shell
	 * @param VSCManager $vscManager
	 */
	public function __construct(Shell $shell, VSCManager $vscManager)
	{
		$this->shell      = $shell;
		$this->vscManager = $vscManager;
	}

	/**
	 * @param string $name
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function __call($name, array $args)
	{
		$needExecute = true;

		if (array_key_exists($name, $this->watch) && ! $this->needExecute($this->watch[$name])) {
			$needExecute = false;
		}

		if ($needExecute) {
			if (isset($this->methods[$name])) {
				return call_user_func_array($this->methods[$name], $args);
			}

			throw new BadMethodCallException("Method '${name}' does not exist.");
		}

		return false;
	}

	/**
	 * @return array
	 */
	public function getWatch()
	{
		return $this->watch;
	}

	/**
	 * @param array $watch
	 */
	public function setWatch(array $watch)
	{
		$this->watch = $watch;
	}

	/**
	 * @return array
	 */
	public function getOptions()
	{
		return $this->options;
	}

	/**
	 * @param array $options
	 */
	public function setOptions($options)
	{
		$this->options = $options;
	}

	/**
	 * @param string $name
	 *
	 * @return mixed|null
	 */
	public function option($name)
	{
		if (array_key_exists($name, $this->options)) {
			return $this->options[$name];
		}

		return null;
	}

	/**
	 * @return Shell
	 */
	public function shell()
	{
		return $this->shell;
	}

	/**
	 * @return VSCManager
	 */
	public function vsc()
	{
		return $this->vscManager;
	}

	/**
	 * @param string $name
	 *
	 * @return $this
	 */
	public function after($name)
	{
		if (in_array($name, $this->actions)) {
			$this->after = (string) $name;
		}

		return $this;
	}

	/**
	 * @param string $name
	 *
	 * @return $this
	 */
	public function before($name)
	{
		if (in_array($name, $this->actions)) {
			$this->before = (string) $name;
		}

		return $this;
	}

	/**
	 * @param string $name
	 * @param Closure $closure
	 * @param string $message
	 */
	public function register($name, Closure $closure, $message = '')
	{
		$name    = (string) $name;
		$message = (string) $message;

		if (in_array($name, $this->actions)) {
			throw new ReleaseManagerException("Action '${name}' already exists");
		}

		if (empty($name)) {
			throw new ReleaseManagerException("Action can't be empty");
		}

		if (! empty($message)) {
			$this->actionsMessages[$this->getActionMessageKey($name)] = $message;
		}

		$this->insertAction($name);

		$this->methods[$name] = $closure;
	}

	/**
	 * @param string $name
	 */
	public function delete($name)
	{
		if (in_array($name, $this->actions)) {
			unset($this->actions[$name]);
			unset($this->methods[$name]);
			$messageKey = $this->getActionMessageKey($name);
			if (array_key_exists($messageKey, $this->actionsMessages)) {
				unset($this->actionsMessages[$messageKey]);
			}
		}
	}

	/**
	 * @return array
	 */
	public function getActions()
	{
		return array_values($this->actions);
	}

	/**
	 * @param string $actionName
	 *
	 * @return string
	 */
	public function getActionMessage($actionName)
	{
		$actionMessageKey = $this->getActionMessageKey($actionName);

		if (array_key_exists($actionMessageKey, $this->actionsMessages)) {
			return $this->actionsMessages[$actionMessageKey];
		}

		return '';
	}

	/**
	 * @param mixed $watchedFiles
	 *
	 * @return bool
	 */
	protected function needExecute($watchedFiles)
	{
		if (! is_array($watchedFiles)) {
			$watchedFiles = [$watchedFiles];
		}

		$watchedFiles = array_map(function ($file) {
			return trim(trim($file), DIRECTORY_SEPARATOR);
		}, $watchedFiles);

		if (! empty($this->compareModifierFiles($watchedFiles))) {
			return true;
		} elseif (! empty($this->compareModifierDirectories($watchedFiles))) {
			return true;
		}

		return false;
	}

	/**
	 * @param array $watchedFiles
	 *
	 * @return array
	 */
	protected function compareModifierFiles(array $watchedFiles)
	{
		$changedFiles = $this->vscManager->modifiedFiles();

		return array_intersect($watchedFiles, $changedFiles);
	}

	/**
	 * @param array $watchedFiles
	 *
	 * @return array
	 */
	protected function compareModifierDirectories(array $watchedFiles)
	{
		$removeFileFromPath = function ($directory) {
			$directory = rtrim($directory, basename($directory));
			return trim(trim($directory), DIRECTORY_SEPARATOR);
		};

		$changedDirectories = array_map($removeFileFromPath, $this->vscManager->files());

		return array_intersect($watchedFiles, $changedDirectories);
	}

	/**
	 * @param string $name
	 */
	protected function insertAction($name)
	{
		$getIndex = function () {
			$counter = 0;
			foreach ($this->actions as $action) {
				if ($action === $this->after) {
					break;
				}
				$counter++;
			}
			return $counter;
		};

		$sliceAndInsert = function ($index) use ($name) {
			$a = array_slice($this->actions, 0, $index, true);
			$b = array_slice($this->actions, $index, true);

			$this->actions = $a + [$name => $name] + $b;
		};

		if (! empty($this->after)) {
			$sliceAndInsert($getIndex() + 1);
		} elseif (! empty($this->before)) {
			$sliceAndInsert($getIndex() - 1);
		} else {
			$this->actions[$name] = $name;
		}

		$this->after  = null;
		$this->before = null;
	}

	/**
	 * @param string $actionName
	 *
	 * @return string
	 */
	protected function getActionMessageKey($actionName)
	{
		return "${actionName}_message";
	}
}
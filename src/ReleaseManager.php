<?php

namespace AndrewLrrr\LaravelProjectBuilder;

use AndrewLrrr\LaravelProjectBuilder\Exceptions\ReleaseManagerException;
use AndrewLrrr\LaravelProjectBuilder\Utils\Shell;
use BadMethodCallException;
use Closure;

class ReleaseManager
{
	/**
	 * @var Shell
	 */
	protected $shell;

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
	protected $actionsMessages = [];

	/**
	 * BuildManager constructor.
	 *
	 * @param Shell $shell
	 */
	public function __construct(Shell $shell)
	{
		$this->shell = $shell;
	}

	/**
	 * @param string $name
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function __call($name, array $args)
	{
		if (isset($this->methods[$name])) {
			return call_user_func_array($this->methods[$name], $args);
		}

		throw new BadMethodCallException("Method '${name}' does not exist.");
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
			throw new ReleaseManagerException('Action can\'t be empty');
		}

		if (! empty($message)) {
			$this->actionsMessages[$this->getActionMessageKey($name)] = $message;
		}

		$this->insertAction($name);

		$this->methods[$name] = $closure->bindTo($this, get_class());
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
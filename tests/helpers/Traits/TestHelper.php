<?php

namespace Tests\Helpers\Traits;

use Illuminate\Support\Collection;

trait TestHelper
{
	/**
	 * @param string $class
	 * @param string $name
	 *
	 * @return \ReflectionMethod
	 */
	public function getProtectedMethod($class, $name) {
		$class  = new \ReflectionClass($class);
		$method = $class->getMethod($name);
		$method->setAccessible(true);

		return $method;
	}

	/**
	 * @param string $class
	 * @param string $name
	 *
	 * @return \ReflectionProperty
	 */
	public function getProtectedProperty($class, $name) {
		$class  = new \ReflectionClass($class);
		$property = $class->getProperty($name);
		$property->setAccessible(true);

		return $property;
	}

	/**
	 * @param array $values
	 *
	 * @return Collection
	 */
	public function convertArrayToCollectionOfObjects(array $values)
	{
		return collect($this->convertArrayItemsToObjects($values));
	}

	/**
	 * @param array $values
	 *
	 * @return array
	 */
	public function convertArrayItemsToObjects(array $values)
	{
		return array_map(function ($item) {
			return (object) $item;
		}, $values);
	}
}
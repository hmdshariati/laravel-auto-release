<?php

namespace AndrewLrrr\LaravelAutomateRelease\Traits;

use Illuminate\Support\Facades\Config;

trait ConfigHelper
{
	/**
	 * @param string $search
	 * @param mixed $default
	 * 
	 * @return mixed
	 */
	public function findConfigOrDefault($search, $default)
	{
		if (Config::has($search)) {
			return Config::get($search);
		}

		return $default;
	}
}
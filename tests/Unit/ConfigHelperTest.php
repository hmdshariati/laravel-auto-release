<?php

namespace Tests\Unit;

use AndrewLrrr\LaravelProjectBuilder\Traits\ConfigHelper;
use Illuminate\Support\Facades\Config;

class ConfigHelperFake
{
	use ConfigHelper;
}

class ConfigHelperTest extends TestCase
{
	/**
	 * @test
	 */
	public function can_correctly_read_config_and_returns_default_value_if_config_does_not_exist()
	{
		$helper = new ConfigHelperFake();

		Config::shouldReceive('has')->once()->andReturn(true);
		Config::shouldReceive('get')->once()->andReturn('This is string');

		$this->assertSame('This is string', $helper->findConfigOrDefault('some.config.string', 'Default string'));

		Config::shouldReceive('has')->once()->andReturn(true);
		Config::shouldReceive('get')->once()->andReturn(['This is array']);

		$this->assertSame(['This is array'], $helper->findConfigOrDefault('some.config.array', ['Default array']));

		Config::shouldReceive('has')->once()->andReturn(false);

		$this->assertSame('This default if null', $helper->findConfigOrDefault('some.config.null', 'This default if null'));
	}
}
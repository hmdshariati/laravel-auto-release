<?php

namespace AndrewLrrr\LaravelAutoRelease\Tests\Unit;

class TestCase extends \PHPUnit_Framework_TestCase
{
	public function tearDown()
	{
		if (class_exists('Mockery')) {
			\Mockery::close();
		}
	}
}
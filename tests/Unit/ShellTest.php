<?php

namespace Tests\Unit\Utils;

use AndrewLrrr\LaravelProjectBuilder\Utils\Shell;

class ShellTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var \AndrewLrrr\LaravelProjectBuilder\Utils\Shell
	 */
	protected $shell;

	/**
	 * @return void
	 */
	public function setUp()
	{
		parent::setUp();

		$this->shell = new Shell(__DIR__ . '/../files');
	}

	/**
	 * @test
	 */
	public function can_execute_command_and_returns_array()
	{
		$expected = [
			'file1.txt',
			'file2.txt',
			'file3.txt',
		];

		$command = 'find -type f -printf "%f\n"';

		$this->assertEquals($expected, $this->shell->execCommand($command)->getOut());
	}

	/**
	 * @test
	 */
	public function can_execute_command_and_returns_string()
	{
		$expected = "file1.txt\nfile2.txt\nfile3.txt";

		$command = 'find -type f -printf "%f\n"';

		$this->assertEquals($expected, $this->shell->execCommand($command));
	}

	/**
	 * @test
	 * @expectedException \AndrewLrrr\LaravelProjectBuilder\Exceptions\ShellException
	 * @expectExceptionMessage 127
	 */
	public function throws_error_if_command_is_incorrect()
	{
		$command = 'wtf';

		$this->shell->execCommand($command);
	}
}

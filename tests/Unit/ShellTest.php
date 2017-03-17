<?php

namespace Tests\Unit\Utils;

use AndrewLrrr\LaravelProjectBuilder\Utils\Shell;
use Illuminate\Support\Facades\Artisan;
use Tests\Helpers\Traits\TestHelper;

class ShellTest extends \PHPUnit_Framework_TestCase
{
	use TestHelper;

	/**
	 * @var \Mockery\MockInterface
	 */
	protected $bufferMock;

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

		$this->bufferMock = \Mockery::mock('\Symfony\Component\Console\Output\BufferedOutput');

		$this->shell = new Shell($this->bufferMock, __DIR__ . '/../files');
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

		$this->assertEquals($expected, $this->shell->execCommand($command)->toArray());
	}

	/**
	 * @test
	 */
	public function can_execute_artisan_command()
	{
		Artisan::shouldReceive('call')->once()->andReturn(1);

		$this->bufferMock->shouldReceive('fetch')->andReturn("Some artisan command output\n");

		$expected = "Some artisan command output\n";

		$command = 'artisan:command';

		$this->assertEquals([$expected], $this->shell->execArtisan($command)->toArray());

		$this->assertSame($expected, $this->shell->execArtisan($command)->toString());

		$this->assertEquals($expected, $this->shell->execArtisan($command));
	}

	/**
	 * @test
	 */
	public function has_correct_output_after_multiple_invokes()
	{
		$expected = [
			'file1.txt',
			'file2.txt',
			'file3.txt',
		];

		$command = 'find -type f -printf "%f\n"';

		$this->assertEquals($expected, $this->shell->execCommand($command)->toArray());

		$this->assertEquals($expected, $this->shell->execCommand($command)->toArray());

		$this->assertEquals($expected, $this->shell->execCommand($command)->toArray());
	}

	/**
	 * @test
	 */
	public function can_returns_correct_output_for_outstream_with_empty_lines()
	{
		$expected = [
			'file1.txt',
			'file2.txt',
			'file3.txt',
		];

		$command = 'find -type f -printf "%f\n"';

		$shell = $this->shell->execCommand($command);

		$protectedOut = $this->getProtectedProperty('\AndrewLrrr\LaravelProjectBuilder\Utils\Shell', 'out');

		$protectedOut->setValue($shell, [
			'file1.txt',
			'',
			'',
			'file2.txt',
			'',
			'',
			'file3.txt',
			'',
			'',
		]);

		$this->assertEquals($expected, array_values($shell->toArray()));
	}

	/**
	 * @test
	 */
	public function can_execute_command_and_returns_string()
	{
		$expected = "file1.txt\nfile2.txt\nfile3.txt";

		$command = 'find -type f -printf "%f\n"';

		$this->assertEquals($expected, $this->shell->execCommand($command)->toString());

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

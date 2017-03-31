<?php

namespace Tests\Unit;

use AndrewLrrr\LaravelAutomateRelease\Utils\Shell;
use Illuminate\Support\Facades\Artisan;
use Tests\Helpers\Traits\TestHelper;

class ShellTest extends TestCase
{
	use TestHelper;

	/**
	 * @var \Mockery\MockInterface
	 */
	protected $bufferMock;

	/**
	 * @var \AndrewLrrr\LaravelAutomateRelease\Utils\Shell
	 */
	protected $shell;

	/**
	 * @return void
	 */
	public function setUp()
	{
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
	public function can_optionally_change_base_path_and_execute_command()
	{
		$basePath = __DIR__;
		$newPath  = __DIR__ . '/../files';

		$shell = new Shell($this->bufferMock, $basePath);

		$expected = [
				'file1.txt',
				'file2.txt',
				'file3.txt',
		];

		$command = 'find -type f -printf "%f\n"';

		$this->assertEquals($expected, $shell->execCommand($command, $newPath)->toArray());
	}

	/**
	 * @test
	 */
	public function can_execute_artisan_command()
	{
		Artisan::shouldReceive('call')->times(3)->andReturn(1);

		$this->bufferMock->shouldReceive('fetch')->times(3)->andReturn("Some artisan command output\n");

		$expected = "Some artisan command output\n";

		$command = 'artisan:command';

		$this->assertEquals([$expected], $this->shell->execArtisan($command)->toArray());

		$this->assertSame($expected, $this->shell->execArtisan($command)->toString());

		$this->assertEquals($expected, $this->shell->execArtisan($command));
	}


	/**
	 * @test
	 */
	public function can_correctly_add_newline_to_string_output()
	{
		$expected = "Some command output\n";

		Artisan::shouldReceive('call')->times(6)->andReturn(1);

		$this->bufferMock->shouldReceive('fetch')->twice()->andReturn("Some command output");

		$command = 'artisan:command';

		$this->assertSame($expected, $this->shell->execArtisan($command)->toString());
		$this->assertEquals($expected, $this->shell->execArtisan($command));

		$this->bufferMock->shouldReceive('fetch')->twice()->andReturn("Some command output\n");

		$this->assertSame($expected, $this->shell->execArtisan($command)->toString());
		$this->assertEquals($expected, $this->shell->execArtisan($command));

		$this->bufferMock->shouldReceive('fetch')->twice()->andReturn("Some command output\n\n\n");

		$this->assertSame($expected, $this->shell->execArtisan($command)->toString());
		$this->assertEquals($expected, $this->shell->execArtisan($command));
	}

	/**
	 * @test
	 */
	public function returns_empty_string_if_output_is_empty()
	{
		Artisan::shouldReceive('call')->once()->andReturn('');

		$this->bufferMock->shouldReceive('fetch')->once()->andReturn('');

		$command = 'artisan:command';

		$this->assertSame('', $this->shell->execArtisan($command)->toString());
	}

	/**
	 * @test
	 */
	public function has_correctly_output_after_multiple_invokes()
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

		$protectedOut = $this->getProtectedProperty('\AndrewLrrr\LaravelAutomateRelease\Utils\Shell', 'out');

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
		$expected = "file1.txt\nfile2.txt\nfile3.txt\n";

		$command = 'find -type f -printf "%f\n"';

		$this->assertEquals($expected, $this->shell->execCommand($command)->toString());

		$this->assertEquals($expected, $this->shell->execCommand($command));
	}

	/**
	 * @test
	 * @expectedException \AndrewLrrr\LaravelAutomateRelease\Exceptions\ShellException
	 * @expectExceptionMessage 127
	 */
	public function throws_error_if_command_is_incorrect()
	{
		$command = 'wtf';

		$this->shell->execCommand($command);
	}
}

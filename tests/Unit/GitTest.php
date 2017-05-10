<?php

namespace AndrewLrrr\LaravelAutomateRelease\Tests\Unit;

use AndrewLrrr\LaravelAutomateRelease\Utils\Git;
use AndrewLrrr\LaravelAutomateRelease\Tests\Helpers\Traits\TestHelper;

class GitTest extends TestCase
{
	use TestHelper;

	/**
	 * @var \AndrewLrrr\LaravelAutomateRelease\Utils\Git
	 */
	protected $git;

	/**
	 * @return void
	 */
	public function setUp()
	{
		$this->git = \Mockery::mock('\AndrewLrrr\LaravelAutomateRelease\Utils\Git')
			->shouldAllowMockingProtectedMethods()
			->makePartial();
	}

	/**
	 * @test
	 */
	public function can_get_only_commits_hashes()
	{
		$this->git->shouldReceive('execShell')->once()->andReturn([
			'hash=cd4415946fe4b67c6cccc303e4091fb39c30ff5a',
			'hash=a6b9472d98f135008279ad9ce32f77a7793d8325',
			'hash=68e9c7e4fa4465aa40db723d0a96dcda842e8532',
			'hash=7bf4079224bbd4b328e9aaef2fec9cf5505e886f',
		]);

		$expected = $this->convertArrayItemsToObjects([
			'cd4415946fe4b67c6cccc303e4091fb39c30ff5a' => [
				'hash' => 'cd4415946fe4b67c6cccc303e4091fb39c30ff5a',
			],
			'a6b9472d98f135008279ad9ce32f77a7793d8325' => [
				'hash' => 'a6b9472d98f135008279ad9ce32f77a7793d8325',
			],
			'68e9c7e4fa4465aa40db723d0a96dcda842e8532' => [
				'hash' => '68e9c7e4fa4465aa40db723d0a96dcda842e8532',
			],
			'7bf4079224bbd4b328e9aaef2fec9cf5505e886f' => [
				'hash' => '7bf4079224bbd4b328e9aaef2fec9cf5505e886f',
			],
		]);

		$this->assertEquals($expected, $this->git->log(4));
	}

	/**
	 * @test
	 */
	public function can_make_correct_pull_to_origin_master()
	{
		$expectedCommand = 'git pull origin master';
		$actualCommand = '';

		$this->git->shouldReceive('execShell')->once()->andReturnUsing(function ($command) use (&$actualCommand) {
			$actualCommand = $command;
		});

		$this->git->pull();

		$this->assertSame($expectedCommand, $actualCommand);
	}

	/**
	 * @test
	 */
	public function can_make_correct_pull_to_custom_remote_and_branch()
	{
		$expectedCommand = 'git pull custom_remote custom_branch';
		$actualCommand = '';

		$this->git->shouldReceive('execShell')->once()->andReturnUsing(function ($command) use (&$actualCommand) {
			$actualCommand = $command;
		});

		$this->git->pull('custom_branch', 'custom_remote');

		$this->assertSame($expectedCommand, $actualCommand);
	}

	/**
	 * @test
	 */
	public function can_make_correct_reset()
	{
		$expectedCommand = 'git reset --hard';
		$actualCommand = '';

		$this->git->shouldReceive('execShell')->once()->andReturnUsing(function ($command) use (&$actualCommand) {
			$actualCommand = $command;
		});

		$this->git->reset();

		$this->assertSame($expectedCommand, $actualCommand);
	}

	/**
	 * @test
	 */
	public function can_make_correct_clean()
	{
		$expectedCommand = 'git clean -f';
		$actualCommand = '';

		$this->git->shouldReceive('execShell')->once()->andReturnUsing(function ($command) use (&$actualCommand) {
			$actualCommand = $command;
		});

		$this->git->clean();

		$this->assertSame($expectedCommand, $actualCommand);
	}

	/**
	 * @test
	 */
	public function can_make_correct_checkout_to_master_branch()
	{
		$expectedCommand = 'git checkout master';
		$actualCommand = '';

		$this->git->shouldReceive('execShell')->once()->andReturnUsing(function ($command) use (&$actualCommand) {
			$actualCommand = $command;
		});

		$this->git->checkout();

		$this->assertSame($expectedCommand, $actualCommand);
	}

	/**
	 * @test
	 */
	public function can_make_correct_checkout_to_custom_branch()
	{
		$expectedCommand = 'git checkout custom_branch';
		$actualCommand = '';

		$this->git->shouldReceive('execShell')->once()->andReturnUsing(function ($command) use (&$actualCommand) {
			$actualCommand = $command;
		});

		$this->git->checkout('custom_branch');

		$this->assertSame($expectedCommand, $actualCommand);
	}

	/**
	 * @test
	 */
	public function can_make_correct_diff()
	{
		$expectedCommand = 'git diff --name-status hash1 hash2';
		$actualCommand = '';

		$this->git->shouldReceive('execShell')->once()->andReturnUsing(function ($command) use (&$actualCommand) {
			$actualCommand = $command;
		});

		$this->git->diff('hash1', 'hash2');

		$this->assertSame($expectedCommand, $actualCommand);
	}

	/**
	 * @test
	 */
	public function can_parse_additional_commits_data_and_add_it_to_return()
	{
		$this->git->shouldReceive('execShell')->andReturn([
			'hash=cd4415946fe4b67c6cccc303e4091fb39c30ff5a#message=Added target blank to remaining detail page links at the catalog#author=Andrew Larin',
			'hash=a6b9472d98f135008279ad9ce32f77a7793d8325#message=Fixed catalog items teaser section styles. And added target blank to detail page links#author=Andrew Larin',
			'hash=68e9c7e4fa4465aa40db723d0a96dcda842e8532#message=Added favicon and new tags to black list#author=Andrew Larin',
			'hash=7bf4079224bbd4b328e9aaef2fec9cf5505e886f#message=A little style fix#author=Andrew Larin',
		]);

		$expected = $this->convertArrayItemsToObjects([
			'cd4415946fe4b67c6cccc303e4091fb39c30ff5a' => [
				'hash'    => 'cd4415946fe4b67c6cccc303e4091fb39c30ff5a',
				'message' => 'Added target blank to remaining detail page links at the catalog',
				'author'  => 'Andrew Larin',
			],
			'a6b9472d98f135008279ad9ce32f77a7793d8325' => [
				'hash'    => 'a6b9472d98f135008279ad9ce32f77a7793d8325',
				'message' => 'Fixed catalog items teaser section styles. And added target blank to detail page links',
				'author'  => 'Andrew Larin',
			],
			'68e9c7e4fa4465aa40db723d0a96dcda842e8532' => [
				'hash'    => '68e9c7e4fa4465aa40db723d0a96dcda842e8532',
				'message' => 'Added favicon and new tags to black list',
				'author'  => 'Andrew Larin',
			],
			'7bf4079224bbd4b328e9aaef2fec9cf5505e886f' => [
				'hash'    => '7bf4079224bbd4b328e9aaef2fec9cf5505e886f',
				'message' => 'A little style fix',
				'author'  => 'Andrew Larin',
			],
		]);

		$this->assertEquals($expected, $this->git->log(4, ['message', 'author']));
	}

	/**
	 * @test
	 */
	public function can_build_correct_command()
	{
		$expectedCommand = 'git log -10 --pretty=format:"hash=%H#message=%B#author=%an#date=%ad#email=%ae"';

		$buildCommand = $this->getProtectedMethod('\AndrewLrrr\LaravelAutomateRelease\Utils\Git', 'buildCommand');

		$shellMock = \Mockery::mock('\AndrewLrrr\LaravelAutomateRelease\Utils\Shell');

		$actualCommand = $buildCommand->invokeArgs(new Git($shellMock), [10, ['message', 'author', 'date', 'email']]);

		$this->assertSame($expectedCommand, $actualCommand);
	}
}

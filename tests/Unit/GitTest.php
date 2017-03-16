<?php

namespace Tests\Unit;

use AndrewLrrr\LaravelProjectBuilder\Utils\Git;
use AndrewLrrr\LaravelProjectBuilder\Utils\Shell;
use Tests\Helpers\Traits\TestHelper;

class GitTest extends \PHPUnit_Framework_TestCase
{
	use TestHelper;

	/**
	 * @var \AndrewLrrr\LaravelProjectBuilder\Utils\Git
	 */
	protected $git;

	/**
	 * @return void
	 */
	public function setUp()
	{
		parent::setUp();

		$this->git = \Mockery::mock('\AndrewLrrr\LaravelProjectBuilder\Utils\Git')
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

		$expected = $this->convertArrayToCollectionOfObjects([
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
	public function can_parse_additional_commits_data_and_add_it_to_return()
	{
		$this->git->shouldReceive('execShell')->andReturn([
			'hash=cd4415946fe4b67c6cccc303e4091fb39c30ff5a#message=Added target blank to remaining detail page links at the catalog#author=Andrew Larin',
			'hash=a6b9472d98f135008279ad9ce32f77a7793d8325#message=Fixed catalog items teaser section styles. And added target blank to detail page links#author=Andrew Larin',
			'hash=68e9c7e4fa4465aa40db723d0a96dcda842e8532#message=Added favicon and new tags to black list#author=Andrew Larin',
			'hash=7bf4079224bbd4b328e9aaef2fec9cf5505e886f#message=A little style fix#author=Andrew Larin',
		]);

		$expected = $this->convertArrayToCollectionOfObjects([
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

		$buildCommand = $this->getProtectedMethod('\AndrewLrrr\LaravelProjectBuilder\Utils\Git', 'buildCommand');

		$actualCommand = $buildCommand->invokeArgs(new Git(new Shell()), [10, ['message', 'author', 'date', 'email']]);

		$this->assertSame($expectedCommand, $actualCommand);
	}

	/**
	 * @test
	 */
	public function can_get_last_commit_hash()
	{
		$this->git->shouldReceive('execShell')->once()->andReturn([
			'hash=cd4415946fe4b67c6cccc303e4091fb39c30ff5a',
		]);

		$expected = 'cd4415946fe4b67c6cccc303e4091fb39c30ff5a';

		$this->assertEquals($expected, $this->git->log(4)->first()->hash);
	}
}

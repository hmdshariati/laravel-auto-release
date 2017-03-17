<?php

namespace Tests\Unit;

use AndrewLrrr\LaravelProjectBuilder\VSCManager;
use Tests\Helpers\Traits\TestHelper;

class VSCManagerTest extends \PHPUnit_Framework_TestCase
{
	use TestHelper;

	/**
	 * @var \Mockery\MockInterface
	 */
	protected $gitMock;

	/**
	 * @var \Mockery\MockInterface
	 */
	protected $vscManager;

	/**
	 * @return void
	 */
	public function setUp()
	{
		parent::setUp();

		$this->gitMock = \Mockery::mock('\AndrewLrrr\LaravelProjectBuilder\Utils\Git');
		$this->vscManager = \Mockery::mock('\AndrewLrrr\LaravelProjectBuilder\VSCManager', [$this->gitMock])
			->shouldAllowMockingProtectedMethods()
			->makePartial();

		$this->vscManager->shouldReceive('findConfigOrDefault')->andReturnNull();
	}

	/**
	 * @test
	 */
	public function can_set_last_commit_hash()
	{
		$this->gitMock->shouldReceive('log')->once()->andReturn($this->convertArrayToCollectionOfObjects([
			'cd4415946fe4b67c6cccc303e4091fb39c30ff5a' => [
				'hash' => 'cd4415946fe4b67c6cccc303e4091fb39c30ff5a',
			]
		]));

		$this->vscManager->setLastCommitHash();

		$this->assertSame('cd4415946fe4b67c6cccc303e4091fb39c30ff5a', $this->vscManager->getLastCommitHash());
	}

	/**
	 * @test
	 */
	public function can_find_commit_by_substring_if_passed_string_as_argument()
	{
		$this->assertNeedUpdate('composer updated', 'composer updated');
	}

	/**
	 * @test
	 */
	public function can_find_commit_by_substring_if_passed_array_as_argument_for_first_array_element()
	{
		$this->assertNeedUpdate('(composer update)', ['(composer update)', '(composer updated)']);
	}

	/**
	 * @test
	 */
	public function can_find_commit_by_substring_if_passed_array_as_argument_for_last_array_element()
	{
		$this->assertNeedUpdate('(composer updated)', ['(composer update)', '(composer updated)']);
	}

	/**
	 * @test
	 */
	public function return_false_if_commits_contain_similar_but_not_same_substring()
	{
		$this->assertNeedUpdate('(composer updated)', ['(composer update)'], false);
	}

	/**
	 * @test
	 */
	public function return_false_if_trying_to_find_by_empty_substring()
	{
		$this->assertNeedUpdate('', '', false);
	}

	/**
	 * @test
	 */
	public function return_false_if_trying_to_find_by_array_with_empty_substring()
	{
		$this->assertNeedUpdate('', ['', ''], false);
	}

	/**
	 * @test
	 */
	public function return_false_if_commits_contain_substring_but_needed_commit_is_same_with_last_commit()
	{
		$this->assertHasBeenUpdated('(composer update)', ['(composer update)', '(composer updated)']);
	}

	/**
	 * @test
	 */
	public function return_false_if_commits_not_contain_needed_substring()
	{
		$this->assertNotNeedUpdate(['(composer update)', '(composer updated)']);
	}

	protected function assertNeedUpdate($commitSubString, $needles, $expected = true)
	{
		$this->gitMock->shouldReceive('log')->once()->andReturn($this->convertArrayToCollectionOfObjects([
			'68e9c7e4fa4465aa40db723d0a96dcda842e8532' => [
				'hash' => '68e9c7e4fa4465aa40db723d0a96dcda842e8532',
			]
		]));

		$this->gitMock->shouldReceive('log')->zeroOrMoreTimes()->andReturn($this->convertArrayToCollectionOfObjects([
			'cd4415946fe4b67c6cccc303e4091fb39c30ff5a' => [
				'hash'    => 'cd4415946fe4b67c6cccc303e4091fb39c30ff5a',
				'message' => 'Added target blank to remaining detail page links at the catalog',
			],
			'a6b9472d98f135008279ad9ce32f77a7793d8325' => [
				'hash'    => 'a6b9472d98f135008279ad9ce32f77a7793d8325',
				'message' => 'Fixed catalog items teaser section styles.' . $commitSubString,
			],
			'68e9c7e4fa4465aa40db723d0a96dcda842e8532' => [
				'hash'    => '68e9c7e4fa4465aa40db723d0a96dcda842e8532',
				'message' => 'Added favicon and new tags to black list',
			],
			'7bf4079224bbd4b328e9aaef2fec9cf5505e886f' => [
				'hash'    => '7bf4079224bbd4b328e9aaef2fec9cf5505e886f',
				'message' => 'A little style fix',
			],
		]));

		$this->vscManager->setLastCommitHash();

		$this->assertSame($expected, $this->vscManager->findBy($needles));
	}

	protected function assertHasBeenUpdated($commitSubString, $needles)
	{
		$this->gitMock->shouldReceive('log')->once()->andReturn($this->convertArrayToCollectionOfObjects([
			'68e9c7e4fa4465aa40db723d0a96dcda842e8532' => [
				'hash' => '68e9c7e4fa4465aa40db723d0a96dcda842e8532',
			]
		]));

		$this->gitMock->shouldReceive('log')->zeroOrMoreTimes()->andReturn($this->convertArrayToCollectionOfObjects([
			'cd4415946fe4b67c6cccc303e4091fb39c30ff5a' => [
				'hash'    => 'cd4415946fe4b67c6cccc303e4091fb39c30ff5a',
				'message' => 'Added target blank to remaining detail page links at the catalog',
			],
			'a6b9472d98f135008279ad9ce32f77a7793d8325' => [
				'hash'    => 'a6b9472d98f135008279ad9ce32f77a7793d8325',
				'message' => 'Fixed catalog items teaser section styles.',
			],
			'68e9c7e4fa4465aa40db723d0a96dcda842e8532' => [
				'hash'    => '68e9c7e4fa4465aa40db723d0a96dcda842e8532',
				'message' => 'Added favicon. ' . $commitSubString . '. And added new tags to black list',
			],
			'7bf4079224bbd4b328e9aaef2fec9cf5505e886f' => [
				'hash'    => '7bf4079224bbd4b328e9aaef2fec9cf5505e886f',
				'message' => 'A little style fix',
			],
		]));

		$this->vscManager->setLastCommitHash();

		$this->assertFalse($this->vscManager->findBy($needles));
	}

	protected function assertNotNeedUpdate($needles)
	{
		$this->gitMock->shouldReceive('log')->once()->andReturn($this->convertArrayToCollectionOfObjects([
			'68e9c7e4fa4465aa40db723d0a96dcda842e8532' => [
				'hash' => '68e9c7e4fa4465aa40db723d0a96dcda842e8532',
			]
		]));

		$this->gitMock->shouldReceive('log')->zeroOrMoreTimes()->andReturn($this->convertArrayToCollectionOfObjects([
			'cd4415946fe4b67c6cccc303e4091fb39c30ff5a' => [
				'hash'    => 'cd4415946fe4b67c6cccc303e4091fb39c30ff5a',
				'message' => 'Added target blank to remaining detail page links at the catalog',
			],
			'a6b9472d98f135008279ad9ce32f77a7793d8325' => [
				'hash'    => 'a6b9472d98f135008279ad9ce32f77a7793d8325',
				'message' => 'Fixed catalog items teaser section styles.',
			],
			'68e9c7e4fa4465aa40db723d0a96dcda842e8532' => [
				'hash'    => '68e9c7e4fa4465aa40db723d0a96dcda842e8532',
				'message' => 'Added favicon and new tags to black list',
			],
			'7bf4079224bbd4b328e9aaef2fec9cf5505e886f' => [
				'hash'    => '7bf4079224bbd4b328e9aaef2fec9cf5505e886f',
				'message' => 'A little style fix',
			],
		]));

		$this->vscManager->setLastCommitHash();

		$this->assertFalse($this->vscManager->findBy($needles));
	}
}
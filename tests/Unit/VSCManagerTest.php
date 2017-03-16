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
	 * @return void
	 */
	public function setUp()
	{
		parent::setUp();

		$this->gitMock = \Mockery::mock('\AndrewLrrr\LaravelProjectBuilder\Utils\Git');
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

		$manager = new VSCManager($this->gitMock);

		$manager->setLastCommitHash();

		$this->assertSame('cd4415946fe4b67c6cccc303e4091fb39c30ff5a', $manager->getLastCommitHash());
	}

	/**
	 * @test
	 */
	public function can_determine_case_when_composer_need_update_and_when_not_in_and_commit_can_contains_composer_updated()
	{
		$this->assertNeedUpdate('(composer updated)', 'needUpdateComposer');
		$this->assertHasBeenUpdated('(composer updated)', 'needUpdateComposer');
		$this->assertNotNeedUpdate('needUpdateComposer');
	}

	/**
	 * @test
	 */
	public function can_determine_case_when_composer_need_update_and_when_not_in_and_commit_can_contains_composer_update()
	{
		$this->assertNeedUpdate('(composer update)', 'needUpdateComposer');
		$this->assertHasBeenUpdated('(composer update)', 'needUpdateComposer');
	}

	/**
	 * @test
	 */
	public function can_determine_case_when_npm_need_update_and_when_not_in_and_commit_can_contains_npm_updated()
	{
		$this->assertNeedUpdate('(npm updated)', 'needUpdateNpm');
		$this->assertHasBeenUpdated('(npm updated)', 'needUpdateNpm');
		$this->assertNotNeedUpdate('needUpdateNpm');
	}

	/**
	 * @test
	 */
	public function can_determine_case_when_npm_need_update_and_when_not_in_and_commit_can_contains_npm_update()
	{
		$this->assertNeedUpdate('(npm update)', 'needUpdateNpm');
		$this->assertHasBeenUpdated('(npm update)', 'needUpdateNpm');
	}

	/**
	 * @test
	 */
	public function can_determine_case_when_npm_need_install_and_when_not_in_and_commit_can_contains_npm_installed()
	{
		$this->assertNeedUpdate('(npm installed)', 'needInstallNpm');
		$this->assertHasBeenUpdated('(npm installed)', 'needInstallNpm');
		$this->assertNotNeedUpdate('needUpdateNpm');
	}

	/**
	 * @test
	 */
	public function can_determine_case_when_npm_need_install_and_when_not_in_and_commit_can_contains_npm_install()
	{
		$this->assertNeedUpdate('(npm install)', 'needInstallNpm');
		$this->assertHasBeenUpdated('(npm install)', 'needInstallNpm');
	}

	protected function assertNeedUpdate($search, $method)
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
				'message' => 'Fixed catalog items teaser section styles.' . $search,
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

		$manager = new VSCManager($this->gitMock);

		$manager->setLastCommitHash();

		$this->assertTrue($manager->$method());
	}

	protected function assertHasBeenUpdated($search, $method)
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
				'message' => 'Added favicon. ' . $search . '. And added new tags to black list',
			],
			'7bf4079224bbd4b328e9aaef2fec9cf5505e886f' => [
				'hash'    => '7bf4079224bbd4b328e9aaef2fec9cf5505e886f',
				'message' => 'A little style fix',
			],
		]));

		$manager = new VSCManager($this->gitMock);

		$manager->setLastCommitHash();

		$this->assertFalse($manager->$method());
	}

	protected function assertNotNeedUpdate($method)
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

		$manager = new VSCManager($this->gitMock);

		$manager->setLastCommitHash();

		$this->assertFalse($manager->$method());
	}
}
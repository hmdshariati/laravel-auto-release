<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Config;
use Tests\Helpers\Traits\TestHelper;

class VSCManagerTest extends TestCase
{
	use TestHelper;

	/**
	 * @var \Mockery\MockInterface
	 */
	protected $gitMock;

	/**
	 * @var \AndrewLrrr\LaravelProjectBuilder\VSCManager
	 */
	protected $vscManager;

	/**
	 * @return void
	 */
	public function setUp()
	{
		$this->gitMock = \Mockery::mock('\AndrewLrrr\LaravelProjectBuilder\Utils\Git')
			->shouldAllowMockingProtectedMethods()
			->makePartial();

		$this->vscManager = \Mockery::mock('\AndrewLrrr\LaravelProjectBuilder\VSCManager', [$this->gitMock])
			->shouldAllowMockingProtectedMethods()
			->makePartial();
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
	public function can_checkout_to_set_branch()
	{
		Config::shouldReceive('has')->once()->andReturn(true);
		Config::shouldReceive('get')->once()->andReturn('tested_branch');

		$this->gitMock->shouldReceive('checkout')->once()->andReturnUsing(function ($branch) {
			return 'Checkout to branch ' . $branch;
		});

		$this->assertSame('Checkout to branch tested_branch', $this->vscManager->checkout());
	}

	/**
	 * @test
	 */
	public function can_checkout_to_default_branch()
	{
		Config::shouldReceive('has')->once()->andReturn(false);

		$this->gitMock->shouldReceive('checkout')->once()->andReturnUsing(function ($branch) {
			return 'Checkout to branch ' . $branch;
		});

		$this->assertSame('Checkout to branch master', $this->vscManager->checkout());
	}

	/**
	 * @test
	 */
	public function can_pull_from_set_remote_and_branch()
	{
		Config::shouldReceive('has')->once()->andReturn(true);
		Config::shouldReceive('get')->once()->andReturn('tested_branch');
		Config::shouldReceive('has')->once()->andReturn(true);
		Config::shouldReceive('get')->once()->andReturn('tested_remote');

		$this->gitMock->shouldReceive('pull')->once()->andReturnUsing(function ($branch, $remote) {
			return 'Pulling from remote ' . $remote . ' and branch ' . $branch;
		});

		$this->assertSame('Pulling from remote tested_remote and branch tested_branch', $this->vscManager->pull());
	}

	/**
	 * @test
	 */
	public function can_pull_from_default_remote_and_branch()
	{
		Config::shouldReceive('has')->once()->andReturn(false);
		Config::shouldReceive('has')->once()->andReturn(false);

		$this->gitMock->shouldReceive('pull')->once()->andReturnUsing(function ($branch, $remote) {
			return 'Pulling from remote ' . $remote . ' and branch ' . $branch;
		});

		$this->assertSame('Pulling from remote origin and branch master', $this->vscManager->pull());
	}

	/**
	 * @test
	 */
	public function can_reset_local_changes()
	{
		$this->gitMock->shouldReceive('reset')->once()->andReturnUsing(function () {
			return 'HEAD is now at test';
		});

		$this->assertSame('HEAD is now at test', $this->vscManager->reset());
	}

	/**
	 * @test
	 */
	public function can_clean_untracked_files()
	{
		$this->gitMock->shouldReceive('clean')->once()->andReturnUsing(function () {
			return 'Cleaning untracked files';
		});

		$this->assertSame('Cleaning untracked files', $this->vscManager->clean());
	}

	/**
	 * @test
	 */
	public function can_select_all_changed_files()
	{
		$protectedProperty = $this->getProtectedProperty($this->vscManager, 'lastCommitHash');
		$protectedProperty->setValue($this->vscManager, '7bf4079224bbd4b328e9aaef2fec9cf5505e886f');

		$this->gitMock->shouldReceive('log')->once()->andReturn($this->convertArrayItemsToObjects([
			'cd4415946fe4b67c6cccc303e4091fb39c30ff5a' => [
				'hash' => 'cd4415946fe4b67c6cccc303e4091fb39c30ff5a',
			],
		]));

		$this->gitMock->shouldReceive('diff')->once()->andReturn([
			'D   app/Presenters/Presenter.php',
			'A   app/Presenters/BasePresenter.php',
			'A   app/Providers/SeoTextServiceProvider.php',
			'M  app/Utils/Str.php',
			'D   config/acme.php',
			'M    config/app.php',
			'A    npm-shrinkwrap.json',
			'M    package.json',
		]);

		$expected = [
			'app/Presenters/Presenter.php',
			'app/Presenters/BasePresenter.php',
			'app/Providers/SeoTextServiceProvider.php',
			'app/Utils/Str.php',
			'config/acme.php',
			'config/app.php',
			'npm-shrinkwrap.json',
			'package.json',
		];

		$this->assertEquals($expected, $this->vscManager->files());
	}

	/**
	 * @test
	 */
	public function can_select_all_modified_files()
	{
		$protectedProperty = $this->getProtectedProperty($this->vscManager, 'lastCommitHash');
		$protectedProperty->setValue($this->vscManager, '7bf4079224bbd4b328e9aaef2fec9cf5505e886f');

		$this->gitMock->shouldReceive('log')->once()->andReturn($this->convertArrayItemsToObjects([
			'cd4415946fe4b67c6cccc303e4091fb39c30ff5a' => [
				'hash' => 'cd4415946fe4b67c6cccc303e4091fb39c30ff5a',
			],
		]));

		$this->gitMock->shouldReceive('diff')->once()->andReturn([
			'D   app/Presenters/Presenter.php',
			'A   app/Presenters/BasePresenter.php',
			'A   app/Providers/SeoTextServiceProvider.php',
			'M  app/Utils/Str.php',
			'D   config/acme.php',
			'M    config/app.php',
			'A    npm-shrinkwrap.json',
			'M    package.json',
		]);

		$expected = [
			'app/Utils/Str.php',
			'config/app.php',
			'package.json',
		];

		$this->assertEquals($expected, $this->vscManager->modifiedFiles());
	}

	/**
	 * @test
	 */
	public function can_select_all_added_files()
	{
		$protectedProperty = $this->getProtectedProperty($this->vscManager, 'lastCommitHash');
		$protectedProperty->setValue($this->vscManager, '7bf4079224bbd4b328e9aaef2fec9cf5505e886f');

		$this->gitMock->shouldReceive('log')->once()->andReturn($this->convertArrayItemsToObjects([
			'cd4415946fe4b67c6cccc303e4091fb39c30ff5a' => [
				'hash' => 'cd4415946fe4b67c6cccc303e4091fb39c30ff5a',
			],
		]));

		$this->gitMock->shouldReceive('diff')->once()->andReturn([
			'D   app/Presenters/Presenter.php',
			'A   app/Presenters/BasePresenter.php',
			'A   app/Providers/SeoTextServiceProvider.php',
			'M  app/Utils/Str.php',
			'D   config/acme.php',
			'M    config/app.php',
			'A    npm-shrinkwrap.json',
			'M    package.json',
		]);

		$expected = [
			'app/Presenters/BasePresenter.php',
			'app/Providers/SeoTextServiceProvider.php',
			'npm-shrinkwrap.json',
		];

		$this->assertEquals($expected, $this->vscManager->addedFiles());
	}

	/**
	 * @test
	 */
	public function can_select_all_deleted_files()
	{
		$protectedProperty = $this->getProtectedProperty($this->vscManager, 'lastCommitHash');
		$protectedProperty->setValue($this->vscManager, '7bf4079224bbd4b328e9aaef2fec9cf5505e886f');

		$this->gitMock->shouldReceive('log')->once()->andReturn($this->convertArrayItemsToObjects([
			'cd4415946fe4b67c6cccc303e4091fb39c30ff5a' => [
				'hash' => 'cd4415946fe4b67c6cccc303e4091fb39c30ff5a',
			],
		]));

		$this->gitMock->shouldReceive('diff')->once()->andReturn([
			'D   app/Presenters/Presenter.php',
			'A   app/Presenters/BasePresenter.php',
			'A   app/Providers/SeoTextServiceProvider.php',
			'M  app/Utils/Str.php',
			'D   config/acme.php',
			'M    config/app.php',
			'A    npm-shrinkwrap.json',
			'M    package.json',
		]);

		$expected = [
			'app/Presenters/Presenter.php',
			'config/acme.php',
		];

		$this->assertEquals($expected, $this->vscManager->deletedFiles());
	}
}
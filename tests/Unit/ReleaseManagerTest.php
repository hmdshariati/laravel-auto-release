<?php

namespace Tests\Unit;

use AndrewLrrr\LaravelAutoRelease\ReleaseManager;

class ReleaseManagerTest extends TestCase
{
	/**
	 * @var \Mockery\MockInterface
	 */
	protected $shellMock;

	/**
	 * @var \Mockery\MockInterface
	 */
	protected $vscManagerMock;

	/**
	 * @var ReleaseManager
	 */
	protected $manager;

	/**
	 * @return void
	 */
	public function setUp()
	{
		$this->shellMock      = \Mockery::mock('\AndrewLrrr\LaravelAutoRelease\Utils\Shell');
		$this->vscManagerMock = \Mockery::mock('\AndrewLrrr\LaravelAutoRelease\VSCManager');

		$this->manager = new ReleaseManager($this->shellMock, $this->vscManagerMock, []);
	}

	/**
	 * @test
	 */
	public function can_register_new_methods()
	{
		$this->manager->register('action1', function () {
			return null;
		});

		$this->manager->register('action2', function () {
			return null;
		});

		$this->assertEquals(['action1', 'action2'], $this->manager->getCommands());
	}

	/**
	 * @test
	 */
	public function can_set_and_get_release_options()
	{
		$this->manager->setOptions([
			'op1' => true,
			'op2' => 'Hello',
			'op3' => 4,
		]);

		$this->assertCount(3, $this->manager->getOptions());

		$this->assertTrue($this->manager->option('op1'));

		$this->assertSame(4, $this->manager->option('op3'));

		$this->assertSame('Hello', $this->manager->option('op2'));
	}

	/**
	 * @test
	 */
	public function can_set_and_returns_null_value_if_option_does_not_exist()
	{
		$this->manager->setOptions([
			'op1' => true,
			'op2' => 'Hello',
		]);

		$this->assertCount(2, $this->manager->getOptions());

		$this->assertSame('Hello', $this->manager->option('op2'));

		$this->assertNull($this->manager->option('op3'));
	}

	/**
	 * @test
	 */
	public function can_insert_new_methods_after()
	{
		$this->manager->register('action1', function () {
			return null;
		});

		$this->manager->register('action2', function () {
			return null;
		});

		$this->manager->register('action4', function () {
			return null;
		});

		$this->assertEquals(['action1', 'action2', 'action4'], $this->manager->getCommands());

		$this->manager->after('action2')->register('action3', function () {
			return 'test3';
		});

		$this->assertEquals(['action1', 'action2', 'action3', 'action4'], $this->manager->getCommands());

		$this->assertSame('test3', call_user_func([$this->manager, 'action3']));

		$this->manager->register('action5', function () {
			return 'test5';
		});

		$this->assertEquals(['action1', 'action2', 'action3', 'action4', 'action5'], $this->manager->getCommands());

		$this->assertSame('test5', call_user_func([$this->manager, 'action5']));
	}

	/**
	 * @test
	 */
	public function can_insert_new_methods_before()
	{
		$this->manager->register('action1', function () {
			return null;
		});

		$this->manager->register('action2', function () {
			return null;
		});

		$this->manager->register('action4', function () {
			return null;
		});

		$this->assertEquals(['action1', 'action2', 'action4'], $this->manager->getCommands());

		$this->manager->before('action4')->register('action3', function () {
			return 'test3';
		});

		$this->assertEquals(['action1', 'action2', 'action3', 'action4'], $this->manager->getCommands());

		$this->assertSame('test3', call_user_func([$this->manager, 'action3']));

		$this->manager->register('action5', function () {
			return 'test5';
		});

		$this->assertEquals(['action1', 'action2', 'action3', 'action4', 'action5'], $this->manager->getCommands());

		$this->assertSame('test5', call_user_func([$this->manager, 'action5']));
	}

	/**
	 * @test
	 */
	public function can_register_new_methods_and_invoke_them()
	{
		$this->manager->register('action1', function () {
			return 'test1';
		});

		$this->manager->register('action2', function () {
			return 'test2';
		});

		foreach ($this->manager->getCommands() as $key => $action) {
			$this->assertEquals('test' . ($key + 1), $this->manager->$action());
		}
	}

	/**
	 * @test
	 */
	public function can_register_new_methods_and_invoke_them_with_parameters()
	{
		$this->manager->register('action1', function ($shell, $one, $two) {
			return 'param1 - ' . $one  . ' param2 - ' . $two;
		});

		$this->manager->register('action2', function ($shell, $one, $two, $three) {
			return 'param1 - ' . $one  . ' param2 - ' . $two . ' param3 - ' . $three;
		});

		$this->assertEquals(['action1', 'action2'], $this->manager->getCommands());

		foreach ($this->manager->getCommands() as $key => $action) {
			if ($action === 'action1') {
				$this->assertEquals('param1 - one param2 - two', call_user_func_array([$this->manager, 'action1'], ['one', 'two']));
			}
			if ($action === 'action2') {
				$this->assertEquals('param1 - one param2 - two param3 - surprise', call_user_func_array([$this->manager, 'action2'], ['one', 'two', 'surprise']));
			}
		}
	}

	/**
	 * @test
	 */
	public function can_register_new_methods_with_messages_and_invoke_them()
	{
		$this->manager->register('action1', function () {
			return 'test1';
		}, 'This is method 1');

		$this->manager->register('action2', function () {
			return 'test2';
		}, 'This is method 2');

		$this->manager->register('action3', function () {
			return 'test3';
		});

		foreach ($this->manager->getCommands() as $key => $action) {
			if ($action !== 'action3') {
				$this->assertEquals('This is method ' . ($key + 1), $this->manager->getActionMessage($action));
			} else {
				$this->assertEquals('', $this->manager->getActionMessage($action));
			}
			$this->assertEquals('test' . ($key + 1), $this->manager->$action());
		}
	}

	/**
	 * @test
	 * @expectedException \BadFunctionCallException
	 * @expectedExceptionMessage Method 'action2' does not exist
	 */
	public function can_register_new_methods_and_after_delete_them()
	{
		$this->manager->register('action1', function () {
			return null;
		});

		$this->manager->register('action2', function () {
			return null;
		});

		$this->assertEquals(['action1', 'action2'], $this->manager->getCommands());

		$this->manager->delete('action1');

		$this->assertEquals(['action2'], $this->manager->getCommands());

		$this->manager->delete('action2');

		$this->assertEquals([], $this->manager->getCommands());

		call_user_func([$this->manager, 'action2']);
	}

	/**
	 * @test
	 * @expectedException \BadMethodCallException
	 * @expectedExceptionMessage Method 'action2' does not exist
	 */
	public function can_register_new_methods_with_messages_and_after_delete_them()
	{
		$this->manager->register('action1', function () {
			return null;
		}, 'Message one');

		$this->manager->register('action2', function () {
			return null;
		}, 'Message two');

		$this->assertEquals('Message one', $this->manager->getActionMessage('action1'));

		$this->manager->delete('action1');

		$this->assertEquals('', $this->manager->getActionMessage('action1'));

		$this->assertEquals('Message two', $this->manager->getActionMessage('action2'));

		$this->manager->delete('action2');

		$this->assertEquals('', $this->manager->getActionMessage('action2'));

		call_user_func([$this->manager, 'action2']);
	}

	/**
	 * @test
	 */
	public function can_register_new_methods_and_execute_shell_command()
	{
		$expected1 = ['file1', 'file2'];

		$expected2 = ['Some file content'];

		$this->shellMock->shouldReceive('execCommand')->once()->andReturn($expected1);

		$this->shellMock->shouldReceive('execCommand')->once()->andReturn($expected2);

		$manager = new ReleaseManager($this->shellMock, $this->vscManagerMock, []);

		$manager->register('action1', function ($shell) {
			return $shell->execCommand('ls -l');
		});

		$manager->register('action2', function ($shell) {
			return $shell->execCommand('cat file1');
		});

		$this->assertEquals($expected1, call_user_func([$manager, 'action1']));

		$this->assertEquals($expected2, call_user_func([$manager, 'action2']));
	}

	/**
	 * @test
	 */
	public function can_skip_execute_method_if_file_has_not_been_changed()
	{
		$this->vscManagerMock->shouldReceive('modifiedFiles')->times(2)->andReturn([
			'config/acme.php',
			'config/app.php',
			'npm-shrinkwrap.json',
			'package.json',
		]);

		$this->vscManagerMock->shouldReceive('files')->times(2)->andReturn([
			'config/acme.php',
			'config/app.php',
			'npm-shrinkwrap.json',
			'package.json',
		]);

		$this->manager->setWatch([
			'action1' => ['composer.json', 'composer.lock'],
			'action3' => 'MainClass.php',
		]);

		$this->manager->register('action1', function () {
			return 'action1';
		});

		$this->manager->register('action2', function () {
			return 'action2';
		});

		$this->manager->register('action3', function () {
			return 'action3';
		});

		$this->assertFalse(call_user_func([$this->manager, 'action1']));
		$this->assertSame('action2', call_user_func([$this->manager, 'action2']));
		$this->assertFalse(call_user_func([$this->manager, 'action3']));
	}

	/**
	 * @test
	 */
	public function can_correctly_compare_files_and_execute_method_if_file_has_been_changed()
	{
		$this->vscManagerMock->shouldReceive('modifiedFiles')->times(2)->andReturn([
			'composer.json',
			'npm-shrinkwrap.json',
			'package.json',
			'config/app.php',
		]);

		$this->vscManagerMock->shouldReceive('files')->never();

		$this->manager->setWatch([
			'action1' => ['composer.json', 'composer.lock'],
			'action3' => '/config/app.php',
		]);

		$this->manager->register('action1', function () {
			return 'action1';
		});

		$this->manager->register('action2', function () {
			return 'action2';
		});

		$this->manager->register('action3', function () {
			return 'action3';
		});

		$this->assertSame('action1', call_user_func([$this->manager, 'action1']));
		$this->assertSame('action2', call_user_func([$this->manager, 'action2']));
		$this->assertSame('action3', call_user_func([$this->manager, 'action3']));
	}

	/**
	 * @test
	 */
	public function can_toggle_method_execution_if_file_has_changed_or_not()
	{
		$this->vscManagerMock->shouldReceive('modifiedFiles')->times(3)->andReturn([
			'composer.json',
			'npm-shrinkwrap.json',
			'package.json',
			'config/app.php',
		]);

		$this->vscManagerMock->shouldReceive('files')->once()->andReturn([
			'composer.json',
			'npm-shrinkwrap.json',
			'package.json',
			'config/app.php',
		]);

		$this->manager->setWatch([
			'action1' => ['composer.json', 'composer.lock'],
			'action3' => 'config/app.php',
			'action4' => ['public/assets/app.js', 'public/assets/style.sass'],
		]);

		$this->manager->register('action1', function () {
			return 'action1';
		});

		$this->manager->register('action2', function () {
			return 'action2';
		});

		$this->manager->register('action3', function () {
			return 'action3';
		});

		$this->manager->register('action4', function () {
			return 'action4';
		});

		$this->assertSame('action1', call_user_func([$this->manager, 'action1']));
		$this->assertSame('action2', call_user_func([$this->manager, 'action2']));
		$this->assertSame('action3', call_user_func([$this->manager, 'action3']));
		$this->assertFalse(call_user_func([$this->manager, 'action4']));
	}

	/**
	 * @test
	 */
	public function can_skip_execute_method_if_files_in_directory_have_not_been_changed()
	{
		$this->vscManagerMock->shouldReceive('modifiedFiles')->times(2)->andReturn([
				'config/acme.php',
				'config/app.php',
				'npm-shrinkwrap.json',
				'package.json',
		]);

		$this->vscManagerMock->shouldReceive('files')->times(2)->andReturn([
				'config/acme.php',
				'config/app.php',
				'npm-shrinkwrap.json',
				'package.json',
		]);

		$this->manager->setWatch([
				'action1' => ['composer.json', 'composer.lock'],
				'action3' => 'public',
		]);

		$this->manager->register('action1', function () {
			return 'action1';
		});

		$this->manager->register('action2', function () {
			return 'action2';
		});

		$this->manager->register('action3', function () {
			return 'action3';
		});

		$this->assertFalse(call_user_func([$this->manager, 'action1']));
		$this->assertSame('action2', call_user_func([$this->manager, 'action2']));
		$this->assertFalse(call_user_func([$this->manager, 'action3']));
	}

	/**
	 * @test
	 */
	public function can_correctly_compare_files_and_execute_method_if_files_in_directory_have_been_changed()
	{
		$this->vscManagerMock->shouldReceive('modifiedFiles')->times(3)->andReturn([
				'config/acme.php',
				'config/app.php',
		]);

		$this->vscManagerMock->shouldReceive('files')->times(3)->andReturn([
				'config/acme.php',
				'config/app.php',
				'/package.json',
				'resources/assets/js/app.js'
		]);

		$this->manager->setWatch([
				'action1' => '/config',
				'action3' => '',
				'action4' => [
					'webpack.mix.js',
					'resources/assets/js',
				],
		]);

		$this->manager->register('action1', function () {
			return 'action1';
		});

		$this->manager->register('action2', function () {
			return 'action2';
		});

		$this->manager->register('action3', function () {
			return 'action3';
		});

		$this->manager->register('action4', function () {
			return 'action4';
		});

		$this->manager->register('action5', function () {
			return 'action5';
		});

		$this->assertSame('action1', call_user_func([$this->manager, 'action1']));
		$this->assertSame('action2', call_user_func([$this->manager, 'action2']));
		$this->assertSame('action3', call_user_func([$this->manager, 'action3']));
		$this->assertSame('action4', call_user_func([$this->manager, 'action4']));
	}

	/**
	 * @test
	 */
	public function can_toggle_method_execution_if_file_has_been_modified_or_files_in_directory_have_been_changed_or_not()
	{
		$this->vscManagerMock->shouldReceive('modifiedFiles')->times(4)->andReturn([
				'webpack.mix.js',
				'composer.json',
				'composer.lock',
		]);

		$this->vscManagerMock->shouldReceive('files')->times(2)->andReturn([
				'webpack.mix.js',
				'composer.json',
				'composer.lock',
				'config/acme.php',
				'resources/assets/js/app.js'
		]);

		$this->manager->setWatch([
				'action1' => ['storage/cache/config.php', '/config'],
				'action2' => ['package.json', 'npm-shrinkwrap.json'],
				'action3' => ['composer.json', 'composer.lock'],
				'action4' => [
					'webpack.mix.js',
					'resources/assets/js',
				],
		]);

		$this->manager->register('action1', function () {
			return 'action1';
		});

		$this->manager->register('action2', function () {
			return 'action2';
		});

		$this->manager->register('action3', function () {
			return 'action3';
		});

		$this->manager->register('action4', function () {
			return 'action4';
		});

		$this->assertSame('action1', call_user_func([$this->manager, 'action1']));
		$this->assertFalse(call_user_func([$this->manager, 'action2']));
		$this->assertSame('action3', call_user_func([$this->manager, 'action3']));
		$this->assertSame('action4', call_user_func([$this->manager, 'action4']));
	}

	/**
	 * @test
	 * @expectedException \AndrewLrrr\LaravelAutoRelease\Exceptions\ReleaseManagerException
	 * @expectedExceptionMessage Action can't be empty
	 */
	public function throws_exception_if_command_name_is_empty()
	{
		$this->manager->register('', function () {
			return null;
		}, 'Message one');
	}

	/**
	 * @test
	 * @expectedException \AndrewLrrr\LaravelAutoRelease\Exceptions\ReleaseManagerException
	 * @expectedExceptionMessage Action 'action' already exists
	 */
	public function throws_exception_if_command_already_exists()
	{
		$this->manager->register('action', function () {
			return null;
		}, 'Message one');

		$this->manager->register('action', function () {
			return null;
		}, 'Message two');
	}
}
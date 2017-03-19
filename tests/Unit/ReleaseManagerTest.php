<?php

namespace Tests\Unit;

use AndrewLrrr\LaravelProjectBuilder\ReleaseManager;

class ReleaseManagerTest extends \PHPUnit_Framework_TestCase
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
	 * @return void
	 */
	public function setUp()
	{
		parent::setUp();

		$this->shellMock      = \Mockery::mock('\AndrewLrrr\LaravelProjectBuilder\Utils\Shell');
		$this->vscManagerMock = \Mockery::mock('\AndrewLrrr\LaravelProjectBuilder\VSCManager');
	}

	/**
	 * @test
	 */
	public function can_register_new_methods()
	{
		$manager = new ReleaseManager($this->shellMock, $this->vscManagerMock);

		$manager->register('action1', function () {
			return null;
		});

		$manager->register('action2', function () {
			return null;
		});

		$this->assertEquals(['action1', 'action2'], $manager->getActions());
	}

	/**
	 * @test
	 */
	public function can_set_and_get_release_options()
	{
		$manager = new ReleaseManager($this->shellMock, $this->vscManagerMock);

		$manager->setOptions([
			'op1' => true,
			'op2' => 'Hello',
			'op3' => 4,
		]);

		$this->assertCount(3, $manager->getOptions());

		$this->assertTrue($manager->option('op1'));

		$this->assertSame(4, $manager->option('op3'));

		$this->assertSame('Hello', $manager->option('op2'));
	}

	/**
	 * @test
	 */
	public function can_set_and_returns_null_value_if_option_does_not_exist()
	{
		$manager = new ReleaseManager($this->shellMock, $this->vscManagerMock);

		$manager->setOptions([
			'op1' => true,
			'op2' => 'Hello',
		]);

		$this->assertCount(2, $manager->getOptions());

		$this->assertSame('Hello', $manager->option('op2'));

		$this->assertNull($manager->option('op3'));
	}

	/**
	 * @test
	 */
	public function can_insert_new_methods_after()
	{
		$manager = new ReleaseManager($this->shellMock, $this->vscManagerMock);

		$manager->register('action1', function () {
			return null;
		});

		$manager->register('action2', function () {
			return null;
		});

		$manager->register('action4', function () {
			return null;
		});

		$this->assertEquals(['action1', 'action2', 'action4'], $manager->getActions());

		$manager->after('action2')->register('action3', function () {
			return 'test3';
		});

		$this->assertEquals(['action1', 'action2', 'action3', 'action4'], $manager->getActions());

		$this->assertSame('test3', call_user_func([$manager, 'action3']));

		$manager->register('action5', function () {
			return 'test5';
		});

		$this->assertEquals(['action1', 'action2', 'action3', 'action4', 'action5'], $manager->getActions());

		$this->assertSame('test5', call_user_func([$manager, 'action5']));
	}

	/**
	 * @test
	 */
	public function can_insert_new_methods_before()
	{
		$manager = new ReleaseManager($this->shellMock, $this->vscManagerMock);

		$manager->register('action1', function () {
			return null;
		});

		$manager->register('action2', function () {
			return null;
		});

		$manager->register('action4', function () {
			return null;
		});

		$this->assertEquals(['action1', 'action2', 'action4'], $manager->getActions());

		$manager->before('action4')->register('action3', function () {
			return 'test3';
		});

		$this->assertEquals(['action1', 'action2', 'action3', 'action4'], $manager->getActions());

		$this->assertSame('test3', call_user_func([$manager, 'action3']));

		$manager->register('action5', function () {
			return 'test5';
		});

		$this->assertEquals(['action1', 'action2', 'action3', 'action4', 'action5'], $manager->getActions());

		$this->assertSame('test5', call_user_func([$manager, 'action5']));
	}

	/**
	 * @test
	 */
	public function can_register_new_methods_and_invoke_them()
	{
		$manager = new ReleaseManager($this->shellMock, $this->vscManagerMock);

		$manager->register('action1', function () {
			return 'test1';
		});

		$manager->register('action2', function () {
			return 'test2';
		});

		foreach ($manager->getActions() as $key => $action) {
			$this->assertEquals('test' . ($key + 1), $manager->$action());
		}
	}

	/**
	 * @test
	 */
	public function can_register_new_methods_and_invoke_them_with_parameters()
	{
		$manager = new ReleaseManager($this->shellMock, $this->vscManagerMock);

		$manager->register('action1', function ($one, $two) {
			return 'param1 - ' . $one  . ' param2 - ' . $two;
		});

		$manager->register('action2', function ($one, $two, $three) {
			return 'param1 - ' . $one  . ' param2 - ' . $two . ' param3 - ' . $three;
		});

		$this->assertEquals(['action1', 'action2'], $manager->getActions());

		foreach ($manager->getActions() as $key => $action) {
			if ($action === 'action1') {
				$this->assertEquals('param1 - one param2 - two', $manager->$action('one', 'two'));
			}
			if ($action === 'action2') {
				$this->assertEquals('param1 - one param2 - two param3 - surprise', $manager->$action('one', 'two', 'surprise'));
			}
		}
	}

	/**
	 * @test
	 */
	public function can_register_new_methods_with_messages_and_invoke_them()
	{
		$manager = new ReleaseManager($this->shellMock, $this->vscManagerMock);

		$manager->register('action1', function () {
			return 'test1';
		}, 'This is method 1');

		$manager->register('action2', function () {
			return 'test2';
		}, 'This is method 2');

		$manager->register('action3', function () {
			return 'test3';
		});

		foreach ($manager->getActions() as $key => $action) {
			if ($action !== 'action3') {
				$this->assertEquals('This is method ' . ($key + 1), $manager->getActionMessage($action));
			} else {
				$this->assertEquals('', $manager->getActionMessage($action));
			}
			$this->assertEquals('test' . ($key + 1), $manager->$action());
		}
	}

	/**
	 * @test
	 * @expectedException \BadFunctionCallException
	 * @expectedExceptionMessage Method 'action2' does not exist
	 */
	public function can_register_new_methods_and_after_delete_them()
	{
		$manager = new ReleaseManager($this->shellMock, $this->vscManagerMock);

		$manager->register('action1', function () {
			return null;
		});

		$manager->register('action2', function () {
			return null;
		});

		$this->assertEquals(['action1', 'action2'], $manager->getActions());

		$manager->delete('action1');

		$this->assertEquals(['action2'], $manager->getActions());

		$manager->delete('action2');

		$this->assertEquals([], $manager->getActions());

		call_user_func([$manager, 'action2']);
	}

	/**
	 * @test
	 * @expectedException \BadMethodCallException
	 * @expectedExceptionMessage Method 'action2' does not exist
	 */
	public function can_register_new_methods_with_messages_and_after_delete_them()
	{
		$manager = new ReleaseManager($this->shellMock, $this->vscManagerMock);

		$manager->register('action1', function () {
			return null;
		}, 'Message one');

		$manager->register('action2', function () {
			return null;
		}, 'Message two');

		$this->assertEquals('Message one', $manager->getActionMessage('action1'));

		$manager->delete('action1');

		$this->assertEquals('', $manager->getActionMessage('action1'));

		$this->assertEquals('Message two', $manager->getActionMessage('action2'));

		$manager->delete('action2');

		$this->assertEquals('', $manager->getActionMessage('action2'));

		call_user_func([$manager, 'action2']);
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

		$manager = new ReleaseManager($this->shellMock, $this->vscManagerMock);

		$manager->register('action1', function () {
			return $this->shell->execCommand('ls -l');
		});

		$manager->register('action2', function () {
			return $this->shell->execCommand('cat file1');
		});

		$this->assertEquals($expected1, call_user_func([$manager, 'action1']));

		$this->assertEquals($expected2, call_user_func([$manager, 'action2']));
	}

	/**
	 * @test
	 * @expectedException \AndrewLrrr\LaravelProjectBuilder\Exceptions\ReleaseManagerException
	 * @expectedExceptionMessage Action can't be empty
	 */
	public function throws_exception_if_action_name_is_empty()
	{
		$manager = new ReleaseManager($this->shellMock, $this->vscManagerMock);

		$manager->register('', function () {
			return null;
		}, 'Message one');
	}

	/**
	 * @test
	 * @expectedException \AndrewLrrr\LaravelProjectBuilder\Exceptions\ReleaseManagerException
	 * @expectedExceptionMessage Action 'action' already exists
	 */
	public function throws_exception_if_action_already_exists()
	{
		$manager = new ReleaseManager($this->shellMock, $this->vscManagerMock);

		$manager->register('action', function () {
			return null;
		}, 'Message one');

		$manager->register('action', function () {
			return null;
		}, 'Message two');
	}
}
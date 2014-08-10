<?php

/**
 * This file is part of ListenersDebugCommandBundle
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Egulias\ListenersDebugCommandBundle\Test\Command;

use PHPUnit_Framework_TestCase;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

use Egulias\ListenersDebugCommandBundle\Command\ListenersCommand;

/**
 * ListenersCommand test
 *
 * @author Eduardo Gulias <me@egulias.com>
 */
class ListenersCommandTest extends PHPUnit_Framework_TestCase
{
    protected $application;

    public function setUp()
    {
        $params = new ParameterBag(array('debug.container.dump' => __DIR__ . '/../appDevDebugProjectContainer.xml'));
        $container = new Container($params);
        $kernel = $this->getMockForAbstractClass('Symfony\Component\HttpKernel\KernelInterface');
        $kernel->expects($this->any())->method('isDebug')->will($this->returnValue(true));
        $kernel->expects($this->any())->method('getContainer')->will($this->returnValue($container));
        $this->application = new Application($kernel);
        $this->application->add(new ListenersCommand());
    }

    public function testBaseCommand()
    {
        $display = $this->executeCommand(array());

        $this->assertRegExp('/Name/', $display);
        $this->assertRegExp('/Event/', $display);
        $this->assertRegExp('/Method/', $display);
        $this->assertRegExp('/Priority/', $display);
        $this->assertRegExp('/Type/', $display);
        $this->assertRegExp('/Class Name/', $display);
    }

    public function testEventNameFilterForListener()
    {
        $display = $this->executeCommand(array('name' => 'dummy_listener'));

        $this->assertRegExp('/Class/', $display);
        $this->assertRegExp('/DummyListener/', $display);
        $this->assertRegExp('/Event/', $display);
        $this->assertRegExp('/Method/', $display);
        $this->assertRegExp('/Type/', $display);
        $this->assertRegExp('/Priority/', $display);
        $this->assertRegExp('/listener/', $display);
        $this->assertRegExp('/listen/', $display);
        $this->assertRegExp('/8/', $display);
    }

    public function testEventNameFilterForSubscriber()
    {
        $display = $this->executeCommand(array('name' => 'dummy_listener_subscriber'));

        $this->assertRegExp('/Class/', $display);
        $this->assertRegExp('/DummySubscriber/', $display);
        $this->assertRegExp('/Event/', $display);
        $this->assertRegExp('/Method/', $display);
        $this->assertRegExp('/Priority/', $display);
        $this->assertRegExp('/Type/', $display);
        $this->assertRegExp('/subscriber/', $display);
        $this->assertRegExp('/listen/', $display);
        $this->assertRegExp('/8/', $display);
    }

    public function testEventNameFilterForAlias()
    {
        $display = $this->executeCommand(array('name' => 'dummy_listener_subscriber_alias'));

        $this->assertRegExp('/alias for the service dummy_listener_subscriber/', $display);
    }

    public function testFilterByEventName()
    {
        $display = $this->executeCommand(array('--event' => 'kernel.request'));

        $this->assertNotRegExp('/kernel.response/', $display);
    }

    public function testShowOnlyListeners()
    {
        $display = $this->executeCommand(array('--listeners' => null));

        $this->assertNotRegExp('/\|subscriber\|/', $display);
    }

    public function testShowOnlySubscribers()
    {
        $display = $this->executeCommand(array('--subscribers' => null));

        $this->assertNotRegExp('/\|listener\|/', $display);
    }

    public function testShowPrivate()
    {
        $display = $this->executeCommand(array('--show-private' => null));

        $this->assertNotRegExp('/\|private\|/', $display);
    }

    public function testShowOnlyOneListener()
    {

    }

    private function executeCommand(array $options)
    {
        $command = $this->application->find('container:debug:listeners');
        $commandTester = new CommandTester($command);
        $default = array('command' => $command->getName());
        $options = array_merge($default, $options);
        $commandTester->execute($options);

        return $commandTester->getDisplay();
    }
}

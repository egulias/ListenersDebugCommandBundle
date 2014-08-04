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

    public function testEventNameFilter()
    {
        $display = $this->executeCommand(array('name' => 'acme.demo.listener'));

        $this->assertRegExp('/Class/', $display);
        $this->assertRegExp('/ControllerListener/', $display);
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

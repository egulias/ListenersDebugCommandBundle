<?php

namespace Egulias\ListenersDebugCommandBundle\Tests\Listener;

use Egulias\ListenersDebugCommandBundle\Listener\ListenerFetcher;

/**
 * ListenerFetcherTest
 *
 * @author Eduardo Gulias Davis <me@egulias.com>
 */
class ListenerFetcherTest extends \PHPUnit_Framework_TestCase
{
    public function testFetchListeners()
    {
        $defMock = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->disableOriginalConstructor()->getMock();
        $defMock->expects($this->at(0))->method('getTags')->will(
            $this->returnValue(array('test0.event_listener' => array('event' => 'test.event')))
        );
        $defMock->expects($this->at(1))->method('getTags')->will(
            $this->returnValue(array('test1.event_listener' => array('event' => 'test.event')))
        );
        $defMock->expects($this->at(2))->method('getTags')->will(
            $this->returnValue(array('test2.event_listener' => array('event' => 'test.event')))
        );
        $defMock->expects($this->exactly(3))->method('isPublic')->will($this->returnValue(true));
        $defMock->expects($this->any())->method('getClass')->will(
            $this->returnValue('Egulias\ListenersDebugCommandBundle\Tests\Listener\Listener')
        );

        $containerMock = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()->getMock();

        $containerMock->expects($this->once())->method('getDefinitions')
            ->will($this->returnValue(array($defMock, $defMock, $defMock)));

        $containerMock->expects($this->exactly(3))->method('findTaggedServiceIds')->will(
            $this->returnValue(
                array(
                    'test0.event_listener' => array(
                        array(
                            'event' => 'test.event', 'method' => 'onTestEvent', 'priority' => 4
                        ),
                    ),
                    'test1.event_listener' => array(
                        array(
                            'event' => 'test.event', 'method' => 'onTestEvent', 'priority' => 2
                        ),
                    ),
                    'test2.event_listener' => array( array())
                )
            )
        );

        $containerMock->expects($this->any())->method('hasDefinition')->will($this->returnValue(true));
        $containerMock->expects($this->exactly(3))->method('getDefinition')->will($this->returnValue($defMock));
        $fetcher = new ListenerFetcher($containerMock);
        $listeners = $fetcher->fetchListeners();

        $this->assertCount(3, $listeners);
        $this->assertEquals('test.event', $listeners[0][1]);
        $this->assertEquals('listener', $listeners[0][4]);
        $this->assertEquals('subscriber', $listeners[2][4]);

        foreach ($listeners as $listener) {
            $this->assertCount(6, $listener);
            $this->assertEquals('test.event', $listener[1]);
            $this->assertEquals('onTestEvent', $listener[2]);
            $this->assertNotEquals(0, $listener[3]);
        }
    }
}

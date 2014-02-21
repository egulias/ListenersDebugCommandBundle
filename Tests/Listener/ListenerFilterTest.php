<?php

namespace Egulias\ListenersDebugCommandBundle\Tests\Listener;

use Egulias\ListenersDebugCommandBundle\Listener\ListenerFilter;

/**
 * ListenerFilterTest
 *
 * @author Eduardo Gulias Davis <me@egulias.com>
 */
class ListenerFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testFilterEvent()
    {
        $listeners = array(array(1 => 'event', 3 => 3), array(1 => 'event', 3 => 2), array(1 => 'other-event'));
        $filter = new ListenerFilter();

        $filtered = $filter->filterByEvent('event', $listeners);

        $this->assertCount(2, $filtered);
        $this->assertCount(3, $listeners);
        $this->assertEquals(3, $filtered[0][3]);
    }

    public function testFilterEventAsc()
    {
        $listeners = array(array(1 => 'event', 3 => 1), array(1 => 'event', 3 => 2), array(1 => 'other-event'));
        $filter = new ListenerFilter();

        $filtered = $filter->filterByEvent('event', $listeners, true);

        $this->assertEquals(1, $filtered[0][3]);
    }

    public function testGetOnlyListeners()
    {
        $listeners = array(
            array(1 => 'event', 4 => 'subscriber'), array(1 => 'event', 4 => 'listener'), array(4 => 'listener')
        );
        $filter = new ListenerFilter();

        $filtered = $filter->getListeners($listeners);

        $this->assertCount(2, $filtered);
        foreach ($filtered as $listener) {
            $this->assertEquals($listener[4], 'listener');
        }
    }

    public function testGetOnlySubscribers()
    {
        $listeners = array(
            array(1 => 'event', 4 => 'subscriber'), array(1 => 'event', 4 => 'listener'), array(4 => 'listener')
        );
        $filter = new ListenerFilter();

        $filtered = $filter->getSubscribers($listeners);

        $this->assertCount(1, $filtered);
        $this->assertEquals('subscriber', $filtered[0][4]);
    }
}

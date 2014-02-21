<?php

namespace Egulias\ListenersDebugCommandBundle\Tests\Listener;

use Egulias\ListenersDebugCommandBundle\Listener\ListenerFilter;

class ListenerFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testFilterEvent()
    {
        $listeners = array(array(1 => 'event'), array(1 => 'event'), array(1 => 'other-event'));
        $filter = new ListenerFilter();

        $filtered = $filter->filterByEvent('event', $listeners);

        $this->assertCount(2, $filtered);
        $this->assertEquals($filtered[0][3], 1);
        $this->assertCount(3, $listeners);
    }

    public function testFilterEventAsc()
    {
        $listeners = array(array(1 => 'event', 3 => 1), array(1 => 'event', 3 => 2), array(1 => 'other-event'));
        $filter = new ListenerFilter();

        $filtered = $filter->filterByEvent('event', $listeners, true);

        $this->assertEquals($filtered[0][3], 2);
    }

    public function testGetOnlyListeners()
    {
        $listeners = array(array(1 => 'event', 4 => 'subscriber'), array(1 => 'event', 4 => 'listener'), array(4 => 'listener'));
        $filter = new ListenerFilter();

        $filtered = $filter->getListeners($listeners);

        $this->asssertCount($filtered, 2);
        $this->assertEquals($filtered[0][4], 'listener');
    }

    public function testGetOnlySubscribers()
    {
        $listeners = array(array(1 => 'event', 4 => 'subscriber'), array(1 => 'event', 4 => 'listener'), array(4 => 'listener'));
        $filter = new ListenerFilter();

        $filtered = $filter->getListeners($listeners);

        $this->asssertCount($filtered, 1);
        $this->assertEquals($filtered[0][4], 'subscriber');
    }
}
 
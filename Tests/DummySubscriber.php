<?php

namespace Egulias\ListenersDebugCommandBundle\Tests;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DummySubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array (
            'kernel.terminate',
            'kernel.view' => array('listen', 8),
            'rare.condition' => array(array('listen', 8))
        );
    }

    public function listen()
    {

    }
}

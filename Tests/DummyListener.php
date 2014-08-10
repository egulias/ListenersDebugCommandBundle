<?php

namespace Egulias\ListenersDebugCommandBundle\Test;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DummyListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array ('kernel.terminate' => 'listen');
    }

    public function listen()
    {

    }
}

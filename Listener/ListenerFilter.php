<?php

namespace Egulias\ListenersDebugCommandBundle\Listener;

/**
 * ListenerFilter
 *
 * @author Eduardo Gulias Davis <me@egulias.com>
 */
class ListenerFilter
{
    public function filterByEvent($event, $listeners, $order = false)
    {
        $listenersList = array_filter(
            $listeners,
            function ($listener) use ($event) {
                return $listener[1] === $event;
            }
        );

        if ($order) {
            usort(
                $listenersList,
                function ($a, $b) use ($order) {
                    if ($order) {
                        return ($a[3] >= $b[3]) ? 1 : -1;
                    }
                    return ($a[3] <= $b[3]) ? 1 : -1;
                }
            );
        }

        return $listenersList;
    }

    public function getListeners($listeners)
    {
        return array_filter(
            $listeners,
            function ($listener) {
                return $listener[4] === 'listener';
            }
        );
    }

    public function getSubscribers($listeners)
    {
        return array_filter(
            $listeners,
            function ($listener) {
                return $listener[4] === 'subscriber';
            }
        );
    }
}

<?php

namespace Egulias\ListenersDebugCommandBundle\Listener;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Definition;

class ListenerFetcher
{
    const LISTENER_PATTERN = '/.+\.event_listener/';
    const SUBSCRIBER_PATTERN = '/.+\.event_subscriber/';

    protected $listeners = array();
    protected $builder;

    public function __construct(ContainerBuilder $builder)
    {
        $this->builder = $builder;
    }

    public function fetchListeners($showPrivate = false)
    {
        $listenersIds = $this->getIds();

        $listenersList = array();
        foreach ($listenersIds as $serviceId) {
            $definition = $this->resolveServiceDef($serviceId);
            if (!$showPrivate && !$definition->isPublic()) {
                continue;
            }

            if ($definition instanceof Definition) {
                foreach ($this->listeners[$serviceId]['tag'] as $listener) {
                    //this is probably an EventSubscriber
                    if (!isset($listener['event'])) {
                        $events = $this->getEventSubscriberInformation($definition->getClass());
                        foreach ($events as $name => $event) {
                            $priority = 0;
                            if (is_array($event) && is_array($event[0])) {
                                foreach ($event as $property) {
                                    $priority = 0;
                                    $method = $property[0];
                                    if (is_array($property) && isset($property[1]) && is_int($property[1])) {
                                        $priority = $property[1];
                                    }

                                    $listenersList[] = array(
                                        $serviceId,
                                        $name,
                                        $method,
                                        $priority,
                                        'subscriber',
                                        $definition->getClass()
                                    );
                                }
                                continue;
                            }

                            if (is_array($event) && isset($event[1]) && is_int($event[1])) {
                                $priority = $event[1];
                            }

                            if (!is_array($event)) {
                                $event = array($event);
                            }
                            $listenersList[] = array(
                                $serviceId,
                                $name,
                                $event[0],
                                $priority,
                                'subscriber',
                                $definition->getClass()
                            );
                        }
                        continue;
                    }
                    $listenersList[] = array(
                        $serviceId,
                        $listener['event'],
                        $listener['method'],
                        (isset($listener['priority'])) ? $listener['priority'] : 0,
                        'listener',
                        $definition->getClass()
                    );
                }
            } elseif ($definition instanceof Alias) {
                $listenersList[] = array(
                    $serviceId,
                    'n/a',
                    0,
                    sprintf('<comment>alias for</comment> <info>%s</info>', (string) $definition),
                    $definition->getClass()
                );
            }
        }
        return $listenersList;

    }

    public function fetchListener($serviceId)
    {
        return $this->resolveServiceDef($serviceId);
    }

    public function isSubscriber(Definition $definition)
    {
        return ($this->classIsEventSubscriber($definition->getClass())) ? true  : false;
    }

    /**
     * Obtains the information available from class if it is defined as an EventSubscriber
     *
     * @param string $class Fully qualified class name
     *
     * @return array array('event.name' => array(array('method','priority')))
     */
    public function getEventSubscriberInformation($class)
    {
        $events = array();
        $reflectionClass = new \ReflectionClass($class);
        $interfaces = $reflectionClass->getInterfaceNames();
        foreach ($interfaces as $interface) {
            if ($interface == 'Symfony\\Component\\EventDispatcher\\EventSubscriberInterface') {
                return $class::getSubscribedEvents();
            }
        }

        return $events;
    }

    protected function getIds()
    {
        $listenersIds = array();
        if (!$this->hasEventDispatcher()) {
            return $listenersIds;
        }

        $dfs = $this->builder->getDefinitions();

        foreach ($dfs as $v) {
            $tags = $v->getTags();
            if (empty($tags)) {
                continue;
            }
            $keys = array_keys($tags);
            if (preg_match(self::LISTENER_PATTERN, $keys[0]) || preg_match(self::SUBSCRIBER_PATTERN, $keys[0])) {
                $fullTags[$keys[0]] = $keys[0];
            }
        }
        foreach ($fullTags as $tag) {
            $services = $this->builder->findTaggedServiceIds($tag);
            foreach ($services as $id => $events) {
                $this->listeners[$id]['tag'] = $events;
                $listenersIds[$id] = $id;
            }
        }
        asort($listenersIds);

        return $listenersIds;
    }

    /**
     * @return bool
     */
    protected function hasEventDispatcher()
    {
        return (
            $this->builder->hasDefinition('debug.event_dispatcher') ||
            $this->builder->hasDefinition('event_dispatcher')
        );
    }

    /**
     * @param string           $serviceId
     * @return mixed
     */
    protected function resolveServiceDef($serviceId)
    {
        if ($this->builder->hasDefinition($serviceId)) {
            return $this->builder->getDefinition($serviceId);
        }

        if ($this->builder->hasAlias($serviceId)) {
            return $this->builder->getAlias($serviceId);
        }

        return $this->builder->get($serviceId);
    }


    /**
     * Tell if a $class is an EventSubscriber
     *
     * @param string $class Fully qualified class name
     *
     * @return boolean
     */
    protected function classIsEventSubscriber($class)
    {
        $reflectionClass = new \ReflectionClass($class);
        $interfaces = $reflectionClass->getInterfaceNames();
        foreach ($interfaces as $interface) {
            if ($interface == 'Symfony\\Component\\EventDispatcher\\EventSubscriberInterface') {
                return true;
            }
        }

        return false;
    }
}

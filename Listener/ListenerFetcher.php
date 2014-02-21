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

    public function __construct(ContainerBuilder $builder)
    {
        $this->builder = $builder;
    }

    public function fetchListeners($showPrivate = false)
    {
        $listenersIds = $this->getIds();

        $listenersList = array();
        foreach ($listenersIds as $serviceId) {
            $definition = $this->resolveServiceDef($this->builder, $serviceId);
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
     * @param ContainerBuilder $builder
     * @param string           $serviceId
     *
     * @return mixed
     */
    protected function resolveServiceDef(ContainerBuilder $builder, $serviceId)
    {
        if ($builder->hasDefinition($serviceId)) {
            return $builder->getDefinition($serviceId);
        }

        // Some service IDs don't have a Definition, they're simply an Alias
        if ($builder->hasAlias($serviceId)) {
            return $builder->getAlias($serviceId);
        }

        // the service has been injected in some special way, just return the service
        return $builder->get($serviceId);
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
}

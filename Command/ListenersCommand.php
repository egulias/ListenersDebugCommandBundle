<?php

namespace Egulias\ListenersDebugCommandBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Bundle\FrameworkBundle\Command\ContainerDebugCommand;

/**
 * ListenersCommand
 *
 * @author Eduardo Gulias <me@egulias.com>
 */
class ListenersCommand extends ContainerDebugCommand
{

    const LISTENER_PATTERN = '/.+\.event_listener/';


    const SUBSCRIBER_PATTERN = '/.+\.event_subscriber/';

    /**
     * listeners
     *
     * @var array
     */
    protected $listeners = array();

    /**
     * {@inherit}
     */
    protected function configure()
    {
        $this->setDefinition(
            array(
                new InputArgument('name', InputArgument::OPTIONAL, 'A (service) listener name (foo) or search (foo*)'),
                new InputOption(
                    'event',
                    null,
                    InputOption::VALUE_REQUIRED,
                    'Provide an event name (foo.bar) to filter'
                ),
                new InputOption(
                    'order-desc',
                    null,
                    InputOption::VALUE_NONE,
                    'Order listeners by descending priority (default\'s ascending) - ' .
                    '(Only applies when used with --event option)'
                ),
                new InputOption('subscribers', null, InputOption::VALUE_NONE, 'Use to show *only* event subscribers'),
                new InputOption('listeners', null, InputOption::VALUE_NONE, 'Use to show *only* event listeners'),
                new InputOption(
                    'show-private',
                    null,
                    InputOption::VALUE_NONE,
                    'Use to show public *and* private services listeners'
                ),
            )
        )
        ->setName('container:debug:listeners')
        ->setDescription('Displays current services defined as listeners for an application')
        ->setHelp(
            <<<EOF
The <info>container:debug:listeners</info> command displays all configured <comment>public</comment>
services defined as listeners:

  <info>container:debug:listeners</info>

EOF
        );
    }

    /**
     * {@inherit}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');

        $this->containerBuilder = $this->getContainerBuilder();
        $listenersIds = $this->getListenersIds();

        $options = array(
            'show-private' => $input->getOption('show-private'),
            'event'        => $input->getOption('event'),
            'order-desc'        => $input->getOption('order-desc'),
            'show-listeners' => $input->getOption('listeners'),
            'show-subscribers' => $input->getOption('subscribers'),
        );

        // sort so that it reads like an index of services
        asort($listenersIds);

        if ($name) {
            $this->outputListener($output, $name, $options);
        } else {
            $this->outputListeners($output, $listenersIds, $options);
        }
    }

    /**
     * getListenersIds
     *
     * Searches for any number of defined listeners under the tag "*.event_listener" or "*.event_subscriber"
     *
     * @return array listeners ids
     */
    protected function getListenersIds()
    {
        $listenersIds = array();
        if (!$this->containerBuilder->hasDefinition('event_dispatcher')) {
            return $listenersIds;
        }

        //$definition = $this->containerBuilder->getDefinition('event_dispatcher');
        $dfs = $this->containerBuilder->getDefinitions();

        foreach ($dfs as $k => $v) {
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
            $services = $this->containerBuilder->findTaggedServiceIds($tag);
            foreach ($services as $id => $events) {
                $this->listeners[$id]['tag'] = $events;
                $listenersIds[$id] = $id;
            }
        }
        return $listenersIds;
    }

    /**
     * outputListeners
     *
     * @param OutputInterface $output       Output
     * @param array           $listenersIds array of listeners ids
     * @param array           $options      array of options from the console
     *
     */
    protected function outputListeners(OutputInterface $output, $listenersIds, $options = array())
    {
        $showPrivate = $options['show-private'];
        $filterEvent = $options['event'];
        $showListeners = $options['show-listeners'];
        $showSubscribers = $options['show-subscribers'];

        // set the label to specify public or public+private
        $label = '<comment>Public</comment> (services) listeners';
        if ($showPrivate) {
            $label = '<comment>Public</comment> and <comment>private</comment> (services) listeners';
        }

        $output->writeln($this->getHelper('formatter')->formatSection('container', $label));

        $listenersList = array();
        $table = $this->getHelperSet()->get('table');

        foreach ($listenersIds as $serviceId) {
            $definition = $this->resolveServiceDefinition($serviceId);
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
                            if (is_array($event) && isset($event[1]) && is_int($event[1])) {
                                $priority = $event[1];
                            }
                            $listenersList[] = array(
                                $serviceId,
                                $name,
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
                        (isset($listener['priority'])) ? $listener['priority'] : 0,
                        'listener',
                        $definition->getClass()
                    );
                }
            } elseif ($definition instanceof Alias) {
                $listenersList[] = array(
                    $serviceId,
                    'n/a',
                    sprintf('<comment>alias for</comment> <info>%s</info>', (string) $definition),
                    $definition->getClass()
                );
            }
        }

        if ($filterEvent) {
            $listenersList = array_filter(
                $listenersList,
                function ($listener) use ($filterEvent) {
                    return $listener[1] === $filterEvent;
                }
            );
            $order = $options['order-desc'];
            usort(
                $listenersList,
                function ($a, $b) use ($order) {
                    if ($order) {
                        return ($a[2] >= $b[2]) ? 1 : -1;
                    }
                    return ($a[2] <= $b[2]) ? 1 : -1;
                }
            );
        }

        if ($showListeners) {
            $listenersList = array_filter(
                $listenersList,
                function ($listener) {
                    return $listener[3] === 'listener';
                }
            );
        }

        if ($showSubscribers) {
            $listenersList = array_filter(
                $listenersList,
                function ($listener) {
                    return $listener[3] === 'subscriber';
                }
            );
        }

        $table->setHeaders(array('Name', 'Event', 'Priority', 'Type', 'Class Name'));
        $table->setCellRowFormat('<fg=white>%s</fg=white>');
        $table->setRows($listenersList);
        $table->render($output);
    }

    /**
     * Renders detailed service information about one listener
     */
    protected function outputListener(OutputInterface $output, $serviceId)
    {
        $definition = $this->resolveServiceDefinition($serviceId);

        $label = sprintf('Information for listener <info>%s</info>', $serviceId);
        $output->writeln($this->getHelper('formatter')->formatSection('container', $label));
        $output->writeln('');

        if ($definition instanceof Definition) {
            $type = ($this->classIsEventSubscriber($definition->getClass())) ? 'subscriber' : 'listener';
            $output->writeln(sprintf('<comment>Listener Id</comment>   %s', $serviceId));
            $output->writeln(sprintf('<comment>Type:</comment>         %s', $type));
            $output->writeln(sprintf('<comment>Class</comment>         %s', $definition->getClass()));
            $output->writeln(sprintf('<comment>Listens to:</comment>', ''));

            $tags = $definition->getTags();
            foreach ($tags as $tag => $details) {
                if (preg_match(self::SUBSCRIBER_PATTERN, $tag)) {
                    $events = $this->getEventSubscriberInformation($definition->getClass());
                    foreach ($events as $name => $current) {
                        //Exception when event only has the method name
                        if (!is_array($current)) {
                            $current = array($current);
                        } elseif (is_array($current[0])) {
                            $current = $current[0];
                        }

                        $output->writeln(sprintf('<comment>  -Event</comment>         %s', $name));
                        $output->writeln(sprintf('<comment>  -Method</comment>        %s', $current[0]));
                        $priority = (isset($current[1])) ? $current[1] : 0;
                        $output->writeln(sprintf('<comment>  -Priority</comment>      %s', $priority));
                        $output->writeln(
                            sprintf(
                                '<comment>  -----------------------------------------</comment>',
                                $priority
                            )
                        );
                    }
                } elseif (preg_match(self::LISTENER_PATTERN, $tag)) {
                    foreach ($details as $current) {
                        $method = (isset($current['method'])) ? $current['method'] : $current['event'];
                        $output->writeln(sprintf('<comment>  -Event</comment>         %s', $current['event']));
                        $output->writeln(sprintf('<comment>  -Method</comment>        %s', $method));
                        $priority = isset($current['priority']) ? $current['priority'] : 0;
                        $output->writeln(sprintf('<comment>  -Priority</comment>      %s', $priority));
                    }

                }
            }

            $tags = $tags ? implode(', ', array_keys($tags)) : '-';
            $output->writeln(sprintf('<comment>Tags</comment>         %s', $tags));
            $public = $definition->isPublic() ? 'yes' : 'no';
            $output->writeln(sprintf('<comment>Public</comment>       %s', $public));
        } elseif ($definition instanceof Alias) {
            $alias = $definition;
            $output->writeln(sprintf('This service is an alias for the service <info>%s</info>', (string) $alias));
        } else {
            // edge case (but true for "service_container", all we have is the service itself
            $service = $definition;
            $output->writeln(sprintf('<comment>Service Id</comment>   %s', $serviceId));
            $output->writeln(sprintf('<comment>Class</comment>        %s', get_class($service)));
        }
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

    /**
     * Tell if a $class is an EventSubscriber
     *
     * @param string $class Fully qualified class name
     *
     * @return boolean
     */
    public function classIsEventSubscriber($class)
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

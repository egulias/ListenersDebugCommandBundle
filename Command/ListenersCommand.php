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


    /**
     * listeners
     *
     * @var array
     */
    protected $listeners = array();

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setDefinition(
                array(
                  new InputArgument('name', InputArgument::OPTIONAL, 'A (service) listener name (foo)  or search (foo*)'),
                  new InputOption('event', null,  InputOption::VALUE_REQUIRED, 'Provide an event name (foo.bar) to filter'),
                  new InputOption('show-private', null, InputOption::VALUE_NONE, 'Use to show public *and* private services listeners'),
                )
            )
            ->setName('container:debug:listeners')
            ->setDescription('Displays current services defined as listeners for an application')
            ->setHelp(<<<EOF
The <info>container:debug:listeners</info> command displays all configured <comment>public</comment> services definded as listeners:

  <info>container:debug:listeners</info>

EOF
            );
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');

        $this->containerBuilder = $this->getContainerBuilder();
        //$serviceIds = $this->containerBuilder->getServiceIds();
        $listenersIds = $this->getListenersIds();

        $options = array(
            'show-private' => $input->getOption('show-private'),
            'event'        => $input->getOption('event')
        );

        // sort so that it reads like an index of services
        asort($listenersIds);

        if ($name) {
            $this->outputListener($output, $name, $options);
        } else {
            $this->outputListeners($output, $listenersIds, $options );
        }
    }

    /**
     * getListenersIds
     *
     * Searches for any number of defined listeners under the tag "*.event_listener"
     *
     * @return array listeners ids
     */
    protected function getListenersIds()
    {
        $listenersIds = array();
        if (!$this->containerBuilder->hasDefinition('event_dispatcher')) {
            return $listenersIds;
        }

        $definition = $this->containerBuilder->getDefinition('event_dispatcher');
        $dfs = $this->containerBuilder->getDefinitions();

        foreach ($dfs as $k => $v) {
            $tags = $v->getTags();
            if (count($tags) <= 0) {
                continue;
            }
            $keys = array_keys($tags);
            if (preg_match('/.+\.event_listener/', $keys[0])) {
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
     * @return void
     */
    protected function outputListeners(OutputInterface $output, $listenersIds, $options = array())
    {
        $showPrivate = $options['show-private'];
        $filterEvent = $options['event'];
        // set the label to specify public or public+private
        if ($showPrivate) {
            $label = '<comment>Public</comment> and <comment>private</comment> (services) listeners';
        } else {
            $label = '<comment>Public</comment> (services) listeners';
        }

        $output->writeln($this->getHelper('formatter')->formatSection('container', $label));

        // loop through to get space needed and filter private services
        $maxName = 4;
        $maxScope = 30;
        foreach ($listenersIds as $key => $serviceId) {
            $definition = $this->resolveServiceDefinition($serviceId);

            if ($definition instanceof Definition) {
                // filter out private services unless shown explicitly
                if (!$showPrivate && !$definition->isPublic()) {
                    unset($listenersIds[$key]);
                    continue;
                }

                if (strlen($definition->getScope()) > $maxScope) {
                    $maxScope = strlen($definition->getScope());
                }
            }

            if (strlen($serviceId) > $maxName) {
                $maxName = strlen($serviceId);
            }
        }
        $format  = '%-'.$maxName.'s %-'.$maxScope.'s %s';

        // the title field needs extra space to make up for comment tags
        $format1  = '%-'.($maxName + 19).'s %-'.($maxScope + 19).'s %s';
        $output->writeln(sprintf($format1, '<comment>Name</comment>', '<comment>Event</comment>', '<comment>Class Name</comment>'));

        foreach ($listenersIds as $serviceId) {
            $definition = $this->resolveServiceDefinition($serviceId);

            if ($definition instanceof Definition) {
                foreach ($this->listeners[$serviceId]['tag'] as $listener) {
                    if ($listener['event'] == $filterEvent || !$filterEvent) {
                        $output->writeln(
                            sprintf($format, $serviceId, $listener['event'], $definition->getClass())
                        );
                    }
                }
            } elseif ($definition instanceof Alias) {
                $alias = $definition;
                $output->writeln(
                    sprintf($format, $serviceId, 'n/a', sprintf('<comment>alias for</comment> <info>%s</info>', (string) $alias))
                );
            } else {
                // we have no information (happens with "service_container")
                $service = $definition;
                $output->writeln(sprintf($format, $serviceId, '', get_class($service)));
            }
        }
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
            $output->writeln(sprintf('<comment>Listener Id</comment>   %s', $serviceId));
            $output->writeln(sprintf('<comment>Class</comment>         %s', $definition->getClass()));
            $output->writeln(sprintf('<comment>Listens to:</comment>', ''));

            $tags = $definition->getTags();
            foreach ($tags as $tag => $details) {
                foreach ($details as $current) {
                    if (preg_match('/.+\.event_listener/', $tag)) {
                        $output->writeln(sprintf('<comment>  -Event</comment>         %s', $current['event']));
                        $output->writeln(sprintf('<comment>  -Method</comment>        %s', $current['method']));
                        $priority = (isset($current['priority'])) ? $current['priority'] : 0;
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

}

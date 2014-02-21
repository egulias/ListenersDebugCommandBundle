<?php

namespace Egulias\ListenersDebugCommandBundle\Container;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class ListenerFetcher
{

    public function __construct(ContainerBuilder $builder)
    {
        $this->builder = $builder;
    }

    public function fetchListeners()
    {

    }

} 
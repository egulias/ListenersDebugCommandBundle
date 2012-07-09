# Listeners debug Command for Symfony2 console

This bundle provides a simple command `container:debug:listeners` to allow to easyly debug listeners by
provinding useful information about those defined in the app. It will fetch information about all the listeners 
tagged with .event_listener

## DISCLAIMER

Part of this code has been borrowed from FrameworkBundle\Command\ContainerDebugCommand. From this command, 
method resolveServiceDefinition($serviceId) is setted as private, avoiding any posibility of re use. A PR has been
issued [here](https://github.com/symfony/symfony/pull/4658) (currently merged) to allow this extension, 
probably available for the next stable realese of Symfony. I'll update it then. 
The output methods had been changed to allow space for listeners related information.

As soon as the next Symfony 2.1.x stable reales appears, the command will be updated.

# Usage

As for any command you should use: `app/console` from your proyect root.
The command would be:
`app/console container:debug:listeners`

## Available options

There are 2 available options:

* --show-private :    if issued will show also private services
* --event=event.name: if issued will filter to show only the listeners listening to the given name 

## Sample output

* Output for `container:debug:listeners`                        [Without options](https://gist.github.com/3077494)
* Output for `container:debug:listeners --event=kernel.request` [Here](https://gist.github.com/3077506)
* Output for `container:debug:listeners event.name`             [Here](https://gist.github.com/3077521)


# Installation and configuration

## Get the bundle

Add to your `/deps` file :

```
[EguliasListnersDebugCommandBundle]
    git=git@github.com:egulias/ListenersDebugCommandBundle.git
    target=/bundles/Egulias/ListenersDebugCommandBundle
```
  * Side note: remember to add the `version=` tag if you need a particular version
    
And make a `php bin/vendors install`.

## Register the namespace

``` php
<?php

  // app/autoload.php
  $loader->registerNamespaces(array(
      'Egulias' => __DIR__.'/../vendor/bundles',
      // your other namespaces
      ));
```

## Add ListenersDebugCommandBundle to your application kernel

``` php
<?php

  // app/AppKernel.php
  public function registerBundles()
  {
    return array(
      // ...
      new Egulias\ListenersDebugCommandBundle\EguliasListenersDebugCommandBundle(),
      // ...
      );
  }
```

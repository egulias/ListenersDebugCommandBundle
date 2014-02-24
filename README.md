# Listeners debug Command for Symfony2 console [![Build Status](https://travis-ci.org/egulias/ListenersDebugCommandBundle.png?branch=master)](https://travis-ci.org/egulias/ListenersDebugCommandBundle)

This bundle provides a simple command `container:debug:listeners` to allow to easily debug listeners by
providing useful information about those defined in the app. It will fetch information about all the listeners
tagged with .event_listener

## IMPORTANT

### Symfony 2.2.X

If you are using this from Symfony <= 2.2, please update your composer.json file to use version=1.4.1 which is the current
stable branch for Symfony 2.2.x

### Symfony 2.0.X

If you are using this from Symfony 2.0.x, please update your deps file to use version=symfony2.0.x which is the current
stable branch for Symfony 2.0.x

# Usage

As for any command you should use: `app/console` from your project root.
The command is:
`app/console container:debug:listeners`

## Available options

There are 4 available options:

* --show-private :                   if issued will show also private services
* --event=event.name:                if issued will filter to show only the listeners listening to the given name
  (ordered by descending priority)
** --event=event.name --order-asc:  in combination with --event orders them by ascending priority
* --listeners:                       if issued will filter to show only the listeners (only available since Symfony 2.1.x)
* --subscribers:                     if issued will filter to show only the subscribers (only available since Symfony 2.1.x)

## Sample output (Symfony >= 2.3)
* Output for `container:debug:listeners`                          [Here](https://gist.github.com/egulias/5862768)
* Output for `container:debug:listeners --event=kernel.response`  [Here](https://gist.github.com/egulias/5862796)
* Output for `container:debug:listeners listener.id`              [Here](https://gist.github.com/egulias/3132499)
* Output for `container:debug:listeners --listeners`              [Here](https://gist.github.com/egulias/5862815)
* Output for `container:debug:listeners --subscribers`            [Here](https://gist.github.com/egulias/5862829)

## Sample output (Symfony >= 2.1.x <= 2.2.x)

* Output for `container:debug:listeners`                          [Here](https://gist.github.com/3132219)
* Output for `container:debug:listeners --event=kernel.response`  [Here](https://gist.github.com/3132227)
* Output for `container:debug:listeners listener.id`              [Here](https://gist.github.com/3132499)
* Output for `container:debug:listeners --listeners`              [Here](https://gist.github.com/3160841)
* Output for `container:debug:listeners --subscribers`            [Here](https://gist.github.com/3160836)


## Sample output (Symfony 2.0.x)

* Output for `container:debug:listeners`                        [Here](https://gist.github.com/3077494)
* Output for `container:debug:listeners --event=kernel.request` [Here](https://gist.github.com/3077506)
* Output for `container:debug:listeners listener.id`             [Here](https://gist.github.com/3077521)


# Installation and configuration

## Get the bundle
Add to your composer.json

##Symfony >= 2.3


```
{
    "require": {
        "egulias/listeners-debug-command-bundle": "1.6.0"
    }
}
```

##Symfony >= 2.2

```
{
    "require": {
        "egulias/listeners-debug-command-bundle": "symfony2.2"
    }
}
```

Use composer to download the new requirement
``` 
$ php composer.phar update egulias/listeners-debug-command-bundle
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

##Symfony 2.0.x
Add to your `/deps` file :

```
[EguliasListenersDebugCommandBundle]
    git=git@github.com:egulias/ListenersDebugCommandBundle.git
    target=/bundles/Egulias/ListenersDebugCommandBundle
    version=symfony2.0.x
```
    
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

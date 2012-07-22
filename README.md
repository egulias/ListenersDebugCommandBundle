# Listeners debug Command for Symfony2 console

This bundle provides a simple command `container:debug:listeners` to allow to easyly debug listeners by
provinding useful information about those defined in the app. It will fetch information about all the listeners 
tagged with .event_listener

## IMPORTANT

If you are using this from Symfony 2.0.x, please update your deps file to use version=symfony2.0.x which is the current
stable branch for Symfony 2.0.x

# Usage

As for any command you should use: `app/console` from your proyect root.
The command is:
`app/console container:debug:listeners`

## Available options

There are 4 available options:

* --show-private :    if issued will show also private services
* --event=event.name: if issued will filter to show only the listeners listening to the given name 
* --listeners:        if issued will filter to show only the listeners (only available for Symfony 2.1.x)
* --subscribers:      if issued will filter to show only the subscribers (only available for Symfony 2.1.x)

## Sample output (Symfony 2.1.x)

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

##Symfony 2.1.x

Add to your composer.json

```
{
    "require": {
        "egulias/listeners-debug-command-bundle": "*"
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
[EguliasListnersDebugCommandBundle]
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

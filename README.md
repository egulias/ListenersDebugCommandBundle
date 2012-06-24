# Listeners debug Command for Symfony2 console

This bundle provides a simple command `container:debug:listeners` to allow to easyly debug listeners by
provinding useful information about those defined in the app. It will fetch information about all the listeners 
tagged with .event_listener

# Installation and configuration

## Get the bundle

Add to your `/deps` file :

```
[EguliasQuizBundle]
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

## Add EguliasQuizBundle to your application kernel

``` php
<?php

  // app/AppKernel.php
  public function registerBundles()
  {
    return array(
      // ...
      new Egulias\QuizBundle\ListenersDebugCommandBundle(),
      // ...
      );
  }
```

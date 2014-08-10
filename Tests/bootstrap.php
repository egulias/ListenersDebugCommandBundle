<?php

/**
 * This file is part of ListenersDebugCommandBundle
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (! is_file($autoloadFile = __DIR__.'/../vendor/autoload.php')) {
    echo 'Could not find "vendor/autoload.php". Did you forget to run "composer install --dev"?'.PHP_EOL;
    exit(1);
}

require_once $autoloadFile;

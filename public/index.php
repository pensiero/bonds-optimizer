<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Debug\Debug;

/** @var \Composer\Autoload\ClassLoader $loader */
$loader = require __DIR__.'/../app/autoload.php';

if (getenv('ENV') === 'development') {
    Debug::enable();
    $kernel = new AppKernel('dev', getenv('DEBUG'));
}
else {
    include_once __DIR__.'/../var/bootstrap.php.cache';
    $kernel = new AppKernel('prod', getenv('DEBUG'));
}

$kernel->loadClassCache();
//$kernel = new AppCache($kernel);

// When using the HttpCache, you need to call the method in your front controller instead of relying on the configuration parameter
//Request::enableHttpMethodParameterOverride();
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
<?php
require_once 'vendor/autoload.php';
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

$paths = array( __DIR__ . DIRECTORY_SEPARATOR . "models");
$proxyDir = __DIR__ . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'proxies';
$isDevMode = ENVIRONMENT == 'development';


$doctrineClassLoader = new \Doctrine\Common\ClassLoader('models', __DIR__ );
$doctrineClassLoader->register();

$proxiesClassLoader = new \Doctrine\Common\ClassLoader('proxies', __DIR__ . '/models/proxies');
$proxiesClassLoader->register();

if (ENVIRONMENT == 'development') {
	$cache = new \Doctrine\Common\Cache\ArrayCache;
} else {
	$cache = new \Doctrine\Common\Cache\ApcCache;
}

$config = Setup::createAnnotationMetadataConfiguration($paths, $isDevMode, $proxyDir, $cache, true);
$config->setProxyNamespace('Proxies');

if (ENVIRONMENT == 'development') {
    $config->setAutoGenerateProxyClasses(TRUE);
} else {
    $config->setAutoGenerateProxyClasses(FALSE);
}

/* the connection configuration
	./dbParams.php
		$dbParams = array(
		    'driver'   => 'pdo_mysql',
		    'user'     => 'root',
		    'password' => '',
		    'dbname'   => 'parser'
		);
*/
require_once 'dbParams.php';

$em = EntityManager::create($dbParams, $config);

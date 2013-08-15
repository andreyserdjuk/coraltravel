<?php
namespace parser;

if(ENVIRONMENT=='development') {
	error_reporting(E_ALL);
} else error_reporting(0);

// start entity manager
require _R . 'lib'.DIRECTORY_SEPARATOR.'em.php';
$doctrineClassLoader = new \Doctrine\Common\ClassLoader('parser', _R );
$doctrineClassLoader->register();


$coral = new CoralTravel();

if (THREAD == 'updateDictionaries') {
	$coral->updateDictionaries();
}

if (THREAD == 'coralParseXml') {
	$coral->parseXml();
}

if (THREAD == 'updateCtHotelRank') {
	$coral->updateCtHotelRank();
}

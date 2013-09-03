<?php
namespace parser;

if(ENVIRONMENT=='development') {
	error_reporting(E_ALL);
} else error_reporting(0);

// start entity manager
require _R . 'lib'.DIRECTORY_SEPARATOR.'em.php';
$doctrineClassLoader = new \Doctrine\Common\ClassLoader('parser', _R );
$doctrineClassLoader->register();


if (THREAD == 'updateDictionaries') {
	$coral = new CoralTravelDict;
	$coral->updateDictionaries();
}

if (THREAD == 'coralParseXmlDynamic') {
	$coral = new CoralTravelDynamic;
	$coral->parseXml();
}

if (THREAD == 'updateCtHotelRank') {
	$coral->updateCtHotelRank();
}

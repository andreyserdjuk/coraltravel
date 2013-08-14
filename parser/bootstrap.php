<?php
namespace parser;
use	doctrine,
	models;

if(ENVIRONMENT=='development') {
	error_reporting(E_ALL);
} else error_reporting(0);

// start up doctrine
require _R . "doctrine/doctrine.php";
$doctrine = new doctrine\Doctrine;

echo '<pre>';

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

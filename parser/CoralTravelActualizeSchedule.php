<?php

namespace parser;
use	doctrine,
	models;

// lock for direct script calling
define('LOCK_START', 1);

// setup environment
define('ENVIRONMENT', 'development');
define('DEBUGGING', false);

if(ENVIRONMENT=='development') {
	error_reporting(E_ALL);
} else error_reporting(0);

$dir = str_replace('parser', '', __DIR__);
$dir = preg_replace('/parser$/', '', __DIR__, 1);
define('_R', $dir . '\\');
chdir(_R);

// start up doctrine
require _R . 'doctrine\\doctrine.php';
$doctrine = new doctrine\Doctrine;
$em = $doctrine->em;


// найти все варианты перелетов, обработать
// если начало тура меньше сегодняшнего, деактивировать удалить?
// 
// будущие варианты перелетов могут быть неактивны?
$q = $em->createQuery(
	'SELECT ts.departureFlightId, ts.returnFlightId 
		FROM models\TourSchedule ts 
			INNER JOIN ts.tour t
			WHERE t.operator = 1
			GROUP BY ts.departureFlightId, ts.returnFlightId');

$flights = $q->getResult();

Actualize::actual($flights);


class Actualize {


	public static function actual($flights) {

		global $em;

		$startElement = function($parser, $currentNodeName, $currentAttrs) use ($em) {

			if ($currentNodeName == 'RESULT') {
				
				$arrivalAllotmentStatusID = $currentAttrs['ARRIVALALLOTMENTSTATUSID'];
				$departureFlightId = $currentAttrs['DEPARTUREFLIGHTID'];
				$returnFlightId = $currentAttrs['ARRIVALFLIGHTID'];

				$q = $em->createQuery(
					'UPDATE models\TourSchedule ts
						SET ts.active = :active
						WHERE ts.departureFlightId = :departureFlightId 
							AND ts.returnFlightId = :returnFlightId
					');
				$q->setParameter('departureFlightId', $departureFlightId);
				$q->setParameter('returnFlightId', $returnFlightId);
				
				if ($arrivalAllotmentStatusID == 1) {
					$q->setParameter('active', TRUE);
				} else {
					$q->setParameter('active', FALSE);
				}
				$q->execute();
			}
		};

		foreach ($flights as $flight) {
			$departureFlightId = $flight['departureFlightId'];
			$returnFlightId = $flight['returnFlightId'];
			$xmlString = file_get_contents("http://service.coraltravel.ua/Transport.asmx/FlightStatusCheck?arrivalFlightID=$returnFlightId&departureFlightID=$departureFlightId");
		    $xml_parser = xml_parser_create();
		    xml_set_element_handler($xml_parser, $startElement, null);
		    xml_parse($xml_parser, $xmlString, false);
		    xml_parser_free($xml_parser);
		}
	}
}

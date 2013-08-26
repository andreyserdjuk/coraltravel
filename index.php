<?php
define('ENVIRONMENT', 'development');
define('_R', __DIR__ . DIRECTORY_SEPARATOR);
require_once "lib/em.php";

// $productRepository = $em->getRepository('models\RoomCategory');
// $products = $productRepository->findAll();

// $flight = $em->getReference("models\CtFlight", 1);

// $ct = new models\CtTourSchedule;
// $ct2 = new models\CtTourSchedule;
// $ct->setCtAgeGroup(1);
// $ct->setCtAgeGroup(2);
// $ct->setCtAgeGroup(3);
// $ct->setPrice(1200);
// $ct->setCtFlight($flight);
// $ct2->setCtAgeGroup(34);
// $ct2->setCtAgeGroup(25);
// $ct2->setCtAgeGroup(35);
// $ct2->setPrice(2015);
// $ct2->setCtFlight($flight);
// $em->persist($ct);
// $em->persist($ct2);

// $uof = $em->getUnitOfWork();
// $ins = $uof->getScheduledEntityInsertions();
// // var_dump($ins);
// foreach ($ins as $i) {
// 	$fl = $i->getCtFlights();
// 	echo $fl[0]->getId();
// 	// var_dump($fl);
// 	exit;
// }

$ags = $em->getRepository('models\CtAgeGroup')->findAll();
echo "\r\n";
echo !isset($ags[0]);
echo "\r\n";
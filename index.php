<?php
define('ENVIRONMENT', 'development');
require_once "lib/em.php";

// $productRepository = $em->getRepository('models\RoomCategory');
// $products = $productRepository->findAll();

$flight = $em->getReference("models\CtFlight", 1);

$ct = new models\CtTourSchedule;
$ct2 = new models\CtTourSchedule;
$ct->setCtAgeGroup(1);
$ct->setCtAgeGroup(2);
$ct->setCtAgeGroup(3);
$ct->setPrice(1200);
$ct->setCtFlight($flight);
$ct2->setCtAgeGroup(34);
$ct2->setCtAgeGroup(25);
$ct2->setCtAgeGroup(35);
$ct2->setPrice(2015);
$ct2->setCtFlight($flight);
$em->persist($ct);
$em->persist($ct2);
$em->flush();
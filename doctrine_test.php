<?php
define('_R', __DIR__ . DIRECTORY_SEPARATOR);
define('ENVIRONMENT', 'development');
require_once "lib/em.php";

$fb = $em->getRepository("models\CtFlightBundle")->find(354);
$flights = $fb->getCtFlights()->toArray();
var_dump($flights);
<?php
define('ENVIRONMENT', 'development');
require_once "lib/em.php";

$productRepository = $em->getRepository('models\RoomCategory');
// $products = $productRepository->findAll();
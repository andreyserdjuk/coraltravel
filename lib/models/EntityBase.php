<?php
namespace models;
use parser;

abstract class EntityBase {

    public function save()
    {
        global $doctrine;
        $doctrine->em->persist($this);
        $doctrine->em->flush();
    }
    
    public function remove()
    {
        global $doctrine;
        $doctrine->em->remove($this);
        $doctrine->em->flush();
    }
}
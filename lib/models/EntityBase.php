<?php
namespace models;
use parser;

abstract class EntityBase {

    public function save()
    {
        global $em;
        $em->persist($this);
        $em->flush();
    }
    
    public function remove()
    {
        global $em;
        $em->remove($this);
        $em->flush();
    }
}
<?php

namespace models;



/**
 * CtPlace
 *
 * @Table(name="ct_place")
 * @Entity
 */
class CtPlace extends EntityBase 
{
    /**
     * @var integer
     *
     * @Column(name="id", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     *
     * @Column(name="ct_place_id", type="integer", nullable=true)
     */
    private $ctPlaceId;

    /**
     * @var \Place
     *
     * @ManyToOne(targetEntity="Place")
     * @JoinColumns({
     *   @JoinColumn(name="place_id", referencedColumnName="id")
     * })
     */
    private $place;


    public function getId() {
        return $this->id;
    }

    public function setCtPlaceId($ctPlaceId) {
        $this->ctPlaceId = $ctPlaceId;
    
        return $this;
    }

    public function getCtPlaceId() {
        return $this->ctPlaceId;
    }

    public function setPlace(\models\Place $place = null) {
        $this->place = $place;
    
        return $this;
    }

    public function getPlace() {
        return $this->place;
    }
}
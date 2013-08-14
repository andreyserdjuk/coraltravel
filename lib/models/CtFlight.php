<?php
namespace models;

/**
 * @Table(name="ct_flight")
 * @Entity
 */
class CtFlight extends EntityBase 
{
    /**
     * @Column(name="id", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @Column(name="tour_begin", type="date", nullable=false)
     */
    private $tourBegin;

    /**
     * @Column(name="nights", type="integer", nullable=false)
     */
    private $nights;

    /**
     * @Column(name="departure_flight_id", type="integer", nullable=false)
     */
    private $departureFlightId;

    /**
     * @Column(name="return_flight_id", type="integer", nullable=false)
     */
    private $returnFlightId;

    /**
     * @Column(name="active", type="boolean", nullable=true)
     */
    private $active;

    /**
     * @ManyToMany(targetEntity="CtTourSchedule", mappedBy="ctFlights")
     */
    private $ctTourSchedules;


    public function __construct() {
        $this->ctTourSchedules = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId() {
        return $this->id;
    }

    public function setTourBegin($tourBegin) {
        $this->tourBegin = $tourBegin;
    }

    public function getTourBegin() {
        return $this->tourBegin;
    }

    public function setNights($nights) {
        $this->nights = $nights;
    }

    public function getNights() {
        return $this->nights;
    }

    public function setDepartureFlightId($departureFlightId) {
        $this->departureFlightId = $departureFlightId;
    }

    public function getDepartureFlightId() {
        return $this->departureFlightId;
    }

    public function setReturnFlightId($returnFlightId) {
        $this->returnFlightId = $returnFlightId;
    }

    public function getReturnFlightId() {
        return $this->returnFlightId;
    }

    public function setActive($active) {
        $this->active = $active;
    }

    public function getActive() {
        return $this->active;
    }
}
<?php

namespace models;

/**
 * CtFlightBundle
 *
 * @Table(name="ct_flight_bundle")
 * @Entity
 */
class CtFlightBundle extends EntityBase 
{
    /**
     * @Column(name="id", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ManyToMany(targetEntity="CtFlight")
     * @JoinTable(name="ct_flights_bundles",
     *      joinColumns={@JoinColumn(name="ct_flight_bundle_id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="ct_flight_id", referencedColumnName="id")}
     *      )
     **/
    private $ctFlights;
    

    public function __construct() {
        $this->ctFlights = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId() {
        return $this->id;
    }

    public function setCtFlight($ctFlight) {
        $this->ctFlights[] = $ctFlight;
    }

    public function getCtFlights() {
        return $this->ctFlights;
    }
}
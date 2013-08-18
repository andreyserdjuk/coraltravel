<?php
namespace models;

/**
 * @Table(name="ct_tour_schedule")
 * @Entity
 */
class CtTourSchedule extends EntityBase 
{
    /**
     * @Column(name="id", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @Column(name="age_groups", type="string", nullable=true)
     */
    private $ctAgeGroups;

    /**
     * @Column(type="decimal", name="price", nullable=false, scale=2, precision=10)
     * @Index
     */
    private $price;

    /**
     * @ManyToMany(targetEntity="CtFlight", inversedBy="ctTourSchedules")
     * @JoinTable(name="ct_tour_schedules__ct_flights",
     *   joinColumns={@JoinColumn(name="ct_tour_schedule_id", referencedColumnName="id")},
     *   inverseJoinColumns={@JoinColumn(name="ct_flight_id", referencedColumnName="id")}
     * )
     */
    private $ctFlights;

    /**
     * @ManyToMany(targetEntity="Tour", inversedBy="ctTourSchedules")
     * @JoinTable(name="ct_tour_schedules__tours",
     *   joinColumns={@JoinColumn(name="ct_tour_schedule_id", referencedColumnName="id")},
     *   inverseJoinColumns={@JoinColumn(name="tour_id", referencedColumnName="id")}
     * )
     */
    private $tours;


    public function __construct() {
        $this->ctFlights = new \Doctrine\Common\Collections\ArrayCollection();
        $this->tours = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId() {
        return $this->id;
    }

    public function setCtAgeGroup($ctAgeGroup) {
        $ctAgeGroups = json_decode($this->ctAgeGroups, true);
        if (gettype($ctAgeGroups) == 'array') {
            if (!array_search($ctAgeGroup, $ctAgeGroups)) {
                $ctAgeGroups[] = $ctAgeGroup;
            }
        } else {
            $ctAgeGroups = array($ctAgeGroup);
        }
        $this->ctAgeGroups = json_encode($ctAgeGroups);
    }

    public function getCtAgeGroups() {
        return json_decode($this->ctAgeGroups, true);
    }

    public function setCtAgeGroupJson($ctAgeGroupJson) {
        $this->ctAgeGroups = $ctAgeGroupJson;
    }

    public function getCtAgeGroupsJson() {
        return $this->ctAgeGroups;
    }

    public function setPrice($price) {
        $this->price = $price;
    }

    public function getPrice() {
        return $this->price;
    }

    public function setTour($tour = null) {
        $this->tours[] = $tour;
    }

    public function getTours() {
        return $this->tours;
    }

    public function setCtFlight(\models\CtFlight $ctFlight) {
        $this->ctFlights[] = $ctFlight;
    }

    public function getCtFlights() {
        return $this->ctFlights;
    }
}
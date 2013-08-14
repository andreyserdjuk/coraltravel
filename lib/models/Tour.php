<?php
namespace models;

/**
 * @Table(name="tour")
 * @Entity
 */
class Tour extends EntityBase 
{
    /**
     * @Column(name="id", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @Column(name="age_group", type="integer", nullable=true)
     */
    private $ageGroup;

    /**
     * @ManyToOne(targetEntity="Place")
     * @JoinColumns({
     *   @JoinColumn(name="departure_place_id", referencedColumnName="id")
     * })
     */
    private $departureCity;

    /**
     * @ManyToOne(targetEntity="Hotel")
     * @JoinColumns({
     *   @JoinColumn(name="hotel_id", referencedColumnName="id")
     * })
     */
    private $hotel;

    /**
     * @ManyToOne(targetEntity="Meal")
     * @JoinColumns({
     *   @JoinColumn(name="meal_id", referencedColumnName="id")
     * })
     */
    private $meal;

    /**
     * @ManyToOne(targetEntity="Room")
     * @JoinColumns({
     *   @JoinColumn(name="room_id", referencedColumnName="id")
     * })
     */
    private $room;

    /**
     * @ManyToOne(targetEntity="Operator")
     * @JoinColumns({
     *   @JoinColumn(name="operator_id", referencedColumnName="id")
     * })
     */
    private $operator;

    /**
     * @ManyToOne(targetEntity="Currency")
     * @JoinColumns({
     *   @JoinColumn(name="currency_id", referencedColumnName="id")
     * })
     */
    private $currency;

    /**
     * @ManyToMany(targetEntity="CtTourSchedule", mappedBy="tours")
     */
    private $ctTourSchedules;


    public function __construct() {
        $this->ctTourSchedules = new \Doctrine\Common\Collections\ArrayCollection();
    }


    public function getId() {
        return $this->id;
    }

    public function setDepartureCity(Place $departureCity = null)
    {
        $this->departureCity = $departureCity;
    }

    public function getDepartureCity() {
        return $this->departureCity;
    }

    public function setHotel(Hotel $hotel = null) {
        $this->hotel = $hotel;
    }

    public function getHotel(){
        return $this->hotel;
    }

    public function setMeal(Meal $meal = null) {
        $this->meal = $meal;
    }

    public function getMeal() {
        return $this->meal;
    }

    public function setOperator(Operator $operator = null) {
        $this->operator = $operator;
    }

    public function getOperator() {
        return $this->operator;
    }

    public function setRoom(Room $room = null) {
        $this->room = $room;
    }

    public function getRoom() {
        return $this->room;
    }

    public function setCurrency(Currency $currency = null) {
        $this->currency = $currency;
    }

    public function getCurrency() {
        return $this->currency;
    }
}
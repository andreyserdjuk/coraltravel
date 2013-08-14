<?php

namespace models;



/**
 * CtHotelRank
 *
 * @Table(name="ct_hotel_rank")
 * @Entity
 */
class CtHotelRank extends EntityBase 
{
    /**
     * @Column(name="id", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Hotel
     *
     * @ManyToOne(targetEntity="Hotel")
     * @JoinColumns({
     *   @JoinColumn(name="hotel_id", referencedColumnName="id")
     * })
     */
    private $hotel;

    public function getId() {
        return $this->id;
    }

    /**
     * Set hotel
     *
     * @param \models\Hotel $hotel
     * @return CtHotel
     */
    public function setHotel(\models\Hotel $hotel = null)
    {
        $this->hotel = $hotel;
    
        return $this;
    }

    /**
     * Get hotel
     *
     * @return \models\Hotel 
     */
    public function getHotel()
    {
        return $this->hotel;
    }
}
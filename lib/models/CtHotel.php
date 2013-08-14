<?php

namespace models;



/**
 * CtHotel
 *
 * @Table(name="ct_hotel")
 * @Entity
 */
class CtHotel extends EntityBase 
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
     * @Column(name="ct_hotel_id", type="integer", nullable=false)
     */
    private $ctHotelId;

    /**
     * @var \Hotel
     *
     * @ManyToOne(targetEntity="Hotel")
     * @JoinColumns({
     *   @JoinColumn(name="hotel_id", referencedColumnName="id")
     * })
     */
    private $hotel;



    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set ctHotelId
     *
     * @param integer $ctHotelId
     * @return CtHotel
     */
    public function setCtHotelId($ctHotelId)
    {
        $this->ctHotelId = $ctHotelId;
    
        return $this;
    }

    /**
     * Get ctHotelId
     *
     * @return integer 
     */
    public function getCtHotelId()
    {
        return $this->ctHotelId;
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
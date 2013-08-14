<?php

namespace models;



/**
 * CtHotelCategoryGroup
 *
 * @Table(name="ct_hotel_category_group")
 * @Entity
 */
class CtHotelCategoryGroup extends EntityBase 
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
     * @Column(name="ct_hotel_category_group_id", type="integer", nullable=true)
     */
    private $ctHotelCategoryGroupId;

    /**
     * @var \HotelCategoryGroup
     *
     * @ManyToOne(targetEntity="HotelCategoryGroup")
     * @JoinColumns({
     *   @JoinColumn(name="hotel_category_group_id", referencedColumnName="id")
     * })
     */
    private $hotelCategoryGroup;



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
     * Set ctHotelCategoryGroupId
     *
     * @param integer $ctHotelCategoryGroupId
     * @return CtHotelCategoryGroup
     */
    public function setCtHotelCategoryGroupId($ctHotelCategoryGroupId)
    {
        $this->ctHotelCategoryGroupId = $ctHotelCategoryGroupId;
    
        return $this;
    }

    /**
     * Get ctHotelCategoryGroupId
     *
     * @return integer 
     */
    public function getCtHotelCategoryGroupId()
    {
        return $this->ctHotelCategoryGroupId;
    }

    /**
     * Set hotelCategoryGroup
     *
     * @param \models\HotelCategoryGroup $hotelCategoryGroup
     * @return CtHotelCategoryGroup
     */
    public function setHotelCategoryGroup(\models\HotelCategoryGroup $hotelCategoryGroup = null)
    {
        $this->hotelCategoryGroup = $hotelCategoryGroup;
    
        return $this;
    }

    /**
     * Get hotelCategoryGroup
     *
     * @return \models\HotelCategoryGroup 
     */
    public function getHotelCategoryGroup()
    {
        return $this->hotelCategoryGroup;
    }
}
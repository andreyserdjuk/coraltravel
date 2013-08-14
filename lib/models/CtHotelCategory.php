<?php

namespace models;



/**
 * CtHotelCategory
 *
 * @Table(name="ct_hotel_category")
 * @Entity
 */
class CtHotelCategory extends EntityBase 
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
     * @Column(name="ct_otel_category_id", type="integer", nullable=true)
     */
    private $ctHotelCategoryId;

    /**
     * @var \HotelCategory
     *
     * @ManyToOne(targetEntity="HotelCategory")
     * @JoinColumns({
     *   @JoinColumn(name="hotel_category_id", referencedColumnName="id")
     * })
     */
    private $hotelCategory;



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
     * Set ctOtelCategoryId
     *
     * @param integer $ctOtelCategoryId
     * @return CtHotelCategory
     */
    public function setCtHotelCategoryId($ctHotelCategoryId)
    {
        $this->ctHotelCategoryId = $ctHotelCategoryId;
    
        return $this;
    }

    /**
     * Get ctOtelCategoryId
     *
     * @return integer 
     */
    public function getCtHotelCategoryId()
    {
        return $this->ctHotelCategoryId;
    }

    /**
     * Set hotelCategory
     *
     * @param \models\HotelCategory $hotelCategory
     * @return CtHotelCategory
     */
    public function setHotelCategory(\models\HotelCategory $hotelCategory = null)
    {
        $this->hotelCategory = $hotelCategory;
    
        return $this;
    }

    /**
     * Get hotelCategory
     *
     * @return \models\HotelCategory 
     */
    public function getHotelCategory()
    {
        return $this->hotelCategory;
    }
}
<?php

namespace models;



/**
 * HotelCategory
 *
 * @Table(name="hotel_category")
 * @Entity
 */
class HotelCategory extends EntityBase 
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
     * @Column(name="name_en", type="string", length=256, nullable=false)
     */
    private $nameEn;

    /**
     * @Column(name="name_ru", type="string", length=256, nullable=true)
     */
    private $nameRu;

    /**
     * @ManyToOne(targetEntity="HotelCategoryGroup")
     * @JoinColumns({
     *   @JoinColumn(name="hotel_category_group_id", referencedColumnName="id")
     * })
     */
    private $hotelCategoryGroup;

    /**
     * @Column(name="description", type="string", length=512, nullable=true)
     */
    private $description;

    

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    public function setNameEn($nameEn) {
        $this->nameEn = $nameEn;
        return $this;
    }

    public function getNameEn() {
        return $this->nameEn;
    }

    public function setNameRu($nameRu) {
        $this->nameRu = $nameRu;
        return $this;
    }

    public function getNameRu() {
        return $this->nameRu;
    }

    /**
     * Set hotelCategoryGroup
     *
     * @param \models\HotelCategoryGroup $hotelCategoryGroup
     * @return HotelCategory
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


    public function setDescription($description) {
        $this->description = $description;
    
        return $this;
    }

    public function getDescription() {
        return $this->description;
    }
}
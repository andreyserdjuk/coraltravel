<?php

namespace models;



/**
 * HotelCategoryGroup
 *
 * @Table(name="hotel_category_group")
 * @Entity
 */
class HotelCategoryGroup extends EntityBase 
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

    /**
     * Set nameEn
     *
     * @param string $nameEn
     * @return HotelCategoryGroup
     */
    public function setNameEn($nameEn)
    {
        $this->nameEn = $nameEn;
    
        return $this;
    }

    /**
     * Get nameEn
     *
     * @return string 
     */
    public function getNameEn()
    {
        return $this->nameEn;
    }

    /**
     * Set nameRu
     *
     * @param string $nameRu
     * @return HotelCategoryGroup
     */
    public function setNameRu($nameRu)
    {
        $this->nameRu = $nameRu;
    
        return $this;
    }

    /**
     * Get nameRu
     *
     * @return string 
     */
    public function getNameRu()
    {
        return $this->nameRu;
    }

    public function setDescription($description) {
        $this->description = $description;
    
        return $this;
    }

    public function getDescription() {
        return $this->description;
    }
}
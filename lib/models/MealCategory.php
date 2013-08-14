<?php

namespace models;



/**
 * MealCategory
 *
 * @Table(name="meal_category")
 * @Entity
 */
class MealCategory extends EntityBase 
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
     * @var string
     *
     * @Column(name="name_en", type="string", length=256, nullable=true)
     */
    private $nameEn;

    /**
     * @var string
     *
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
     * @return MealCategory
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
        return $this->nameEn . "1";
    }

    public function setNameRu($nameRu) {
        $this->nameRu = $nameRu;
    
        return $this;
    }

    public function getNameRu() {
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
<?php

namespace models;



/**
 * Meal
 *
 * @Table(name="meal")
 * @Entity
 */
class Meal extends EntityBase 
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
     * @ManyToOne(targetEntity="MealCategory")
     * @JoinColumns({
     *   @JoinColumn(name="meal_category_id", referencedColumnName="id")
     * })
     */
    private $category;



    public function getId() {
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

    public function setCategory(\models\MealCategory $category = null) {
        $this->category = $category;
    
        return $this;
    }

    public function getCategory() {
        return $this->category;
    }
}
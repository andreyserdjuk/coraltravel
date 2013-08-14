<?php

namespace models;



/**
 * CtMealCategory
 *
 * @Table(name="ct_meal_category")
 * @Entity
 */
class CtMealCategory extends EntityBase 
{
    /**
     * @Column(name="id", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @Column(name="ct_meal_category_id", type="integer", nullable=true)
     */
    private $ctMealCategoryId;

    /**
     * @ManyToOne(targetEntity="MealCategory")
     * @JoinColumns({
     *   @JoinColumn(name="meal_category_id", referencedColumnName="id")
     * })
     */
    private $mealCategory;


    public function getId() {
        return $this->id;
    }

    public function setCtMealCategoryId($ctMealCategoryId) {
        $this->ctMealCategoryId = $ctMealCategoryId;
    
        return $this;
    }

    public function getCtMealCategoryId() {
        return $this->ctMealCategoryId;
    }

    public function setMealCategory(\models\MealCategory $mealCategory = null) {
        $this->mealCategory = $mealCategory;
    
        return $this;
    }

    public function getMealCategory() {
        return $this->mealCategory;
    }
}
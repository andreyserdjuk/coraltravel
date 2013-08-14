<?php

namespace models;



/**
 * @Table(name="ct_meal")
 * @Entity
 */
class CtMeal extends EntityBase 
{
    /**
     * @Column(name="id", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @Column(name="ct_meal_id", type="integer", nullable=false)
     */
    private $ctMealId;

    /**
     * @ManyToOne(targetEntity="Meal")
     * @JoinColumns({
     *   @JoinColumn(name="meal_id", referencedColumnName="id")
     * })
     */
    private $meal;


    public function getId() {
        return $this->id;
    }

    public function setCtMealId($ctMealId) {
        $this->ctMealId = $ctMealId;
    
        return $this;
    }

    public function getCtMealId() {
        return $this->ctMealId;
    }

    public function setMeal(\models\Meal $meal = null) {
        $this->meal = $meal;
    
        return $this;
    }

    public function getMeal() {
        return $this->meal;
    }
}
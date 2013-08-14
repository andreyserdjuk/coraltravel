<?php

namespace models;



/**
 * AgeGroup
 *
 * @Table(name="age_group")
 * @Entity
 */
class AgeGroup extends EntityBase 
{
    /**
     * @Column(name="id", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @Column(name="adult_count", type="integer", nullable=true)
     */
    private $adultCount;

    /**
     * @Column(name="child_count", type="integer", nullable=true)
     */
    private $childCount;

    /**
     * @Column(name="infant_count", type="integer", nullable=true)
     */
    private $infantCount;

    /**
     * @Column(name="baby_count", type="integer", nullable=true)
     */
    private $babyCount;

    /**
     * @Column(name="child_min_age", type="decimal", precision=4, scale=2, nullable=true)
     */
    private $childMinAge;

    /**
     * @Column(name="child_max_age", type="decimal", precision=4, scale=2, nullable=true)
     */
    private $childMaxAge;

    /**
     * @Column(name="infant_min_age", type="decimal", precision=4, scale=2, nullable=true)
     */
    private $infantMinAge;

    /**
     * @Column(name="infant_max_age", type="decimal", precision=4, scale=2, nullable=true)
     */
    private $infantMaxAge;

    /**
     * @Column(name="baby_min_age", type="decimal", precision=4, scale=2, nullable=true)
     */
    private $babyMinAge;

    /**
     * @Column(name="baby_max_age", type="decimal", precision=4, scale=2, nullable=true)
     */
    private $babyMaxAge;



    public function getId() {
        return $this->id;
    }

    public function setAdultCount($adultCount) {
        $this->adultCount = $adultCount;
    
        return $this;
    }

    public function getAdultCount() {
        return $this->adultCount;
    }

    public function setChildCount($childCount) {
        $this->childCount = $childCount;
    
        return $this;
    }

    public function getChildCount() {
        return $this->childCount;
    }

    public function setInfantCount($infantCount) {
        $this->infantCount = $infantCount;
    
        return $this;
    }

    public function getInfantCount() {
        return $this->infantCount;
    }

    public function setBabyCount($babyCount) {
        $this->babyCount = $babyCount;
    
        return $this;
    }

    public function getBabyCount() {
        return $this->babyCount;
    }

    public function setChildMinAge($childMinAge) {
        $this->childMinAge = $childMinAge;
    
        return $this;
    }

    public function getChildMinAge() {
        return $this->childMinAge;
    }

    public function setChildMaxAge($childMaxAge) {
        $this->childMaxAge = $childMaxAge;
    
        return $this;
    }

    public function getChildMaxAge() {
        return $this->childMaxAge;
    }


    public function setInfantMinAge($infantMinAge) {
        $this->infantMinAge = $infantMinAge;
    
        return $this;
    }

    public function getInfantMinAge() {
        return $this->infantMinAge;
    }

    public function setInfantMaxAge($infantMaxAge) {
        $this->infantMaxAge = $infantMaxAge;
    
        return $this;
    }

    public function getInfantMaxAge() {
        return $this->infantMaxAge;
    }

    public function setBabyMaxAge($babyMaxAge) {
        $this->babyMaxAge = $babyMaxAge;
    
        return $this;
    }

    public function getBabyMaxAge() {
        return $this->babyMaxAge;
    }
}
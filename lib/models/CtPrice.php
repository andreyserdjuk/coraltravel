<?php
namespace models;

/**
 * @Table(name="ct_price")
 * @Entity
 */
class CtPrice extends EntityBase 
{
    /**
     * @ManyToOne(targetEntity="Tour")
     * @JoinColumns({
     *   @JoinColumn(name="ct_tour", referencedColumnName="id")
     * })
     */
    private $tour;

    /**
     * @ManyToOne(targetEntity="CtAgeGroupBundle")
     * @JoinColumns({
     *   @JoinColumn(name="ct_age_group_bundle", referencedColumnName="id")
     * })
     */
    private $ctAgeGroupBundle;

    /**
     * @Column(type="smallint", name="price", nullable=false)
     * @Index
     */
    private $price;


    public function setCtAgeGroupBundle($ctAgeGroupBundle) {
        $this->ctAgeGroupBundle = $ctAgeGroupBundle;
    }

    public function getCtAgeGroupBundle() {
        return $ctAgeGroupBundle;
    }

    public function setPrice($price) {
        $this->price = $price;
    }

    public function getPrice() {
        return $this->price;
    }

    public function setTour($tour) {
        $this->tour = $tour;
    }

    public function getTour() {
        return $this->tour;
    }
}
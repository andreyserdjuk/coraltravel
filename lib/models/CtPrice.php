<?php
namespace models;

/**
 * @Table(name="ct_price")
 * @Entity
 */
class CtPrice extends EntityBase 
{
    /**
     * @Column(name="id", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ManyToOne(targetEntity="Accomodation")
     * @JoinColumns({
     *   @JoinColumn(name="accomodation", referencedColumnName="id")
     * })
     */
    private $accomodation;

    /**
     * @ManyToOne(targetEntity="CtAgeGroupBundle")
     * @JoinColumns({
     *   @JoinColumn(name="ct_age_group_bundle", referencedColumnName="id")
     * })
     */
    private $ctAgeGroupBundle;

    /**
     * @ManyToOne(targetEntity="CtFlightBundle")
     * @JoinColumns({
     *   @JoinColumn(name="ct_flight_bundle_id", referencedColumnName="id")
     * })
     */
    private $ctFlightBundle;

    /**
     * @Column(type="smallint", name="price", nullable=false)
     */
    private $price;


    public function setCtAgeGroupBundle($ctAgeGroupBundle) {
        $this->ctAgeGroupBundle = $ctAgeGroupBundle;
    }

    public function getCtAgeGroupBundle() {
        return $this->ctAgeGroupBundle;
    }

    public function setPrice($price) {
        $this->price = $price;
    }

    public function getPrice() {
        return $this->price;
    }

    public function setAccomodation($accomodation) {
        $this->accomodation = $accomodation;
    }

    public function getAccomodation() {
        return $this->accomodation;
    }

    public function getCtFlightBundle() {
        return $this->ctFlightBundle;
    }

    public function setCtFlightBundle($ctFlightBundle) {
        $this->ctFlightBundle = $ctFlightBundle;
    }
}
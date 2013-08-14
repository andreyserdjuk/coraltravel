<?php

namespace models;



/**
 * CtCountry
 *
 * @Table(name="ct_country")
 * @Entity
 */
class CtCountry extends EntityBase 
{
    /**
     * @Column(name="id", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @Column(name="ct_country_id", type="integer", nullable=false)
     */
    private $ctCountryId;

    /**
     * @ManyToOne(targetEntity="Country")
     * @JoinColumns({
     *   @JoinColumn(name="country_id", referencedColumnName="id")
     * })
     */
    private $country;



    public function getId()
    {
        return $this->id;
    }

    public function setCtCountryId($ctCountryId)
    {
        $this->ctCountryId = $ctCountryId;
    
        return $this;
    }

    public function getCtCountryId()
    {
        return $this->ctCountryId;
    }

    public function setCountry(\models\Country $country = null)
    {
        $this->country = $country;
    
        return $this;
    }

    public function getCountry()
    {
        return $this->country;
    }
}
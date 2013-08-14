<?php

namespace models;



/**
 * CtRegion
 *
 * @Table(name="ct_region")
 * @Entity
 */
class CtRegion extends EntityBase 
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
     * @var integer
     *
     * @Column(name="ct_region_id", type="integer", nullable=true)
     */
    private $ctRegionId;

    /**
     * @var \Region
     *
     * @ManyToOne(targetEntity="Region")
     * @JoinColumns({
     *   @JoinColumn(name="region_id", referencedColumnName="id")
     * })
     */
    private $region;

    /**
     * @var \CtCountry
     *
     * @ManyToOne(targetEntity="CtCountry")
     * @JoinColumns({
     *   @JoinColumn(name="ct_country_id", referencedColumnName="id")
     * })
     */
    private $ctCountry;



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
     * Set ctRegionId
     *
     * @param integer $ctRegionId
     * @return CtRegion
     */
    public function setCtRegionId($ctRegionId)
    {
        $this->ctRegionId = $ctRegionId;
    
        return $this;
    }

    /**
     * Get ctRegionId
     *
     * @return integer 
     */
    public function getCtRegionId()
    {
        return $this->ctRegionId;
    }


    public function setRegion(\models\Region $region = null)
    {
        $this->region = $region;
    
        return $this;
    }


    public function getRegion()
    {
        return $this->region;
    }

    public function setCtCountry(\models\CtCountry $ctCountry = null)
    {
        $this->ctCountry = $ctCountry;
    }


    public function getCtCountry()
    {
        return $this->ctCountry;
    }
}
<?php

namespace models;



/**
 * CtArea
 *
 * @Table(name="ct_area")
 * @Entity
 */
class CtArea extends EntityBase 
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
     * @Column(name="ct_area_id", type="integer", nullable=true)
     */
    private $ctAreaId;

    /**
     * @var \Area
     *
     * @ManyToOne(targetEntity="Area")
     * @JoinColumns({
     *   @JoinColumn(name="area_id", referencedColumnName="id")
     * })
     */
    private $area;

    /**
     * @var \CtRegion
     *
     * @ManyToOne(targetEntity="CtRegion")
     * @JoinColumns({
     *   @JoinColumn(name="ct_region_id", referencedColumnName="id")
     * })
     */
    private $ctRegion;



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
     * Set ctAreaId
     *
     * @param integer $ctAreaId
     * @return CtArea
     */
    public function setCtAreaId($ctAreaId)
    {
        $this->ctAreaId = $ctAreaId;
    
        return $this;
    }

    /**
     * Get ctAreaId
     *
     * @return integer 
     */
    public function getCtAreaId()
    {
        return $this->ctAreaId;
    }

    public function setArea(\models\Area $area = null)
    {
        $this->area = $area;
    
        return $this;
    }

    public function getArea()
    {
        return $this->area;
    }

    public function setCtRegion(\models\CtRegion $ctRegion = null)
    {
        $this->ctRegion = $ctRegion;
    }

    public function getCtRegion()
    {
        return $this->ctRegion;
    }
}
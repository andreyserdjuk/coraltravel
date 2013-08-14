<?php

namespace models;



/**
 * CtAgeGroup
 *
 * @Table(name="ct_age_group")
 * @Entity
 */
class CtAgeGroup extends EntityBase 
{
    /**
     * @Column(name="id", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * adult count
     *
     * @Column(name="ad", type="integer", nullable=true)
     */
    private $ad;

    /**
     * child count
     *
     * @Column(name="cd", type="integer", nullable=true)
     */
    private $cd;

    /**
     * first child min age
     *
     * @Column(name="fmn", type="decimal", precision=4, scale=2, nullable=true)
     */
    private $fmn;

    /**
     * first child max age
     *
     * @Column(name="fmx", type="decimal", precision=4, scale=2, nullable=true)
     */
    private $fmx;

    /**
     * second child min age
     *
     * @Column(name="smn", type="decimal", precision=4, scale=2, nullable=true)
     */
    private $smn;

    /**
     * second child max age
     *
     * @Column(name="smx", type="decimal", precision=4, scale=2, nullable=true)
     */
    private $smx;

    /**
     * third child min age
     *
     * @Column(name="tmn", type="decimal", precision=4, scale=2, nullable=true)
     */
    private $tmn;

    /**
     * third child max age
     *
     * @Column(name="tmx", type="decimal", precision=4, scale=2, nullable=true)
     */
    private $tmx;



    public function getId() {
        return $this->id;
    }

    public function setAdultCount($ad) {
        $this->ad = $ad;
    
        return $this;
    }

    public function getAdultCount() {
        return $this->ad;
    }

    public function setChildCount($cd) {
        $this->cd = $cd;
    
        return $this;
    }

    public function getChildCount() {
        return $this->cd;
    }

    public function setFirstChildMinAge($fmn) {
        $this->fmn = $fmn;
    
        return $this;
    }

    public function getFirstChildMinAge() {
        return $this->fmn;
    }

    public function setFirstChildMaxAge($fmx) {
        $this->fmx = $fmx;
    
        return $this;
    }

    public function getFirstChildMaxAge() {
        return $this->fmx;
    }

    public function setSecondChildMinAge($smn) {
        $this->smn = $smn;
    
        return $this;
    }

    public function getSecondChildMinAge() {
        return $this->smn;
    }

    public function setSecondChildMaxAge($smx) {
        $this->smx = $smx;
    
        return $this;
    }

    public function getSecondChildMaxAge() {
        return $this->smx;
    }

    public function setThirdChildMinAge($tmn) {
        $this->tmn = $tmn;
    
        return $this;
    }

    public function getThirdChildMinAge() {
        return $this->tmn;
    }

    public function setThirdChildMaxAge($tmx) {
        $this->tmx = $tmx;
    
        return $this;
    }

    public function getThirdChildMaxAge() {
        return $this->tmx;
    }
}
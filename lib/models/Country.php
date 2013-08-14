<?php

namespace models;



/**
 * Country
 *
 * @Table(name="country")
 * @Entity
 */
class Country extends EntityBase 
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
     * @var string
     *
     * @Column(name="alpha2_code", type="string", length=2, nullable=true)
     */
    private $alpha2Code;

    /* @ManyToOne(targetEntity="WorldPart")
     * @JoinColumns({
     *   @JoinColumn(name="world_part_id", referencedColumnName="id", nullable=true)
     * })
     */    
    private $worldpart;



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
     * Set nameEn
     *
     * @param string $nameEn
     * @return Country
     */
    public function setNameEn($nameEn)
    {
        $this->nameEn = $nameEn;
    
        return $this;
    }

    /**
     * Get nameEn
     *
     * @return string 
     */
    public function getNameEn()
    {
        return $this->nameEn;
    }

    /**
     * Set nameRu
     *
     * @param string $nameRu
     * @return Country
     */
    public function setNameRu($nameRu)
    {
        $this->nameRu = $nameRu;
    
        return $this;
    }

    /**
     * Get nameRu
     *
     * @return string 
     */
    public function getNameRu()
    {
        return $this->nameRu;
    }

    /**
     * Set alpha2Code
     *
     * @param string $alpha2Code
     * @return Country
     */
    public function setAlpha2Code($alpha2Code)
    {
        $this->alpha2Code = $alpha2Code;
    
        return $this;
    }

    /**
     * Get alpha2Code
     *
     * @return string 
     */
    public function getAlpha2Code()
    {
        return $this->alpha2Code;
    }
}
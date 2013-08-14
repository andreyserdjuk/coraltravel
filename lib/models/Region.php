<?php

namespace models;



/**
 * Region
 *
 * @Table(name="region")
 * @Entity
 */
class Region extends EntityBase 
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
     * @Column(name="name_ru", type="string", length=45, nullable=true)
     */
    private $nameRu;

    /**
     * @var string
     *
     * @Column(name="name_en", type="string", length=45, nullable=true)
     */
    private $nameEn;

    /**
     * @var \Country
     *
     * @ManyToOne(targetEntity="Country")
     * @JoinColumns({
     *   @JoinColumn(name="country_id", referencedColumnName="id")
     * })
     */
    private $country;



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
     * Set nameRu
     *
     * @param string $nameRu
     * @return Region
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
     * Set nameEn
     *
     * @param string $nameEn
     * @return Region
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
     * Set country
     *
     * @param \models\Country $country
     * @return Region
     */
    public function setCountry(\models\Country $country = null)
    {
        $this->country = $country;
    
        return $this;
    }

    /**
     * Get country
     *
     * @return \models\Country 
     */
    public function getCountry()
    {
        return $this->country;
    }
}
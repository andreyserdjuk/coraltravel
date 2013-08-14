<?php
namespace models;

/**
 * @Table(name="place")
 * @Entity
 */
class Place extends EntityBase 
{
    /**
     * @Column(name="id", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @Column(name="name_en", type="string", length=256, nullable=true)
     */
    private $nameEn;

    /**
     * @Column(name="name_ru", type="string", length=256, nullable=true)
     */
    private $nameRu;

    /**
     * @ManyToOne(targetEntity="Country")
     * @JoinColumns({
     *   @JoinColumn(name="country_id", referencedColumnName="id")
     * })
     */
    private $country;

    /**
     * @ManyToOne(targetEntity="Region")
     * @JoinColumns({
     *   @JoinColumn(name="region_id", referencedColumnName="id")
     * })
     */
    private $region;

    /**
     * @ManyToOne(targetEntity="Area")
     * @JoinColumns({
     *   @JoinColumn(name="area_id", referencedColumnName="id")
     * })
     */
    private $area;


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
     * @return City
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
     * @return City
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
     * Set country
     *
     * @param \models\Country $country
     * @return City
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

    /**
     * Set region
     *
     * @param \models\Region $region
     * @return City
     */
    public function setRegion(\models\Region $region = null)
    {
        $this->region = $region;
    
        return $this;
    }

    /**
     * Get region
     *
     * @return \models\Region 
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * Set area
     *
     * @param \models\Area $area
     * @return City
     */
    public function setArea(\models\Area $area = null)
    {
        $this->area = $area;
    
        return $this;
    }

    /**
     * Get area
     *
     * @return \models\Area 
     */
    public function getArea()
    {
        return $this->area;
    }
}
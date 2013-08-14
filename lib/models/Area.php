<?php

namespace models;



/**
 * Area
 *
 * @Table(name="area")
 * @Entity
 */
class Area extends EntityBase 
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
     * @var \Region
     *
     * @ManyToOne(targetEntity="Region")
     * @JoinColumns({
     *   @JoinColumn(name="region_id", referencedColumnName="id")
     * })
     */
    private $region;

    /**
     * @OneToMany(targetEntity="Place", mappedBy="area")
     **/
    private $places;

    public function __construct() {
        $this->places = new \Doctrine\Common\Collections\ArrayCollection();
    }

    function getPlaces() {
        return $this->places;
    }

    function setPlace($place) {
        return $this->places[] = $place;
    }

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
     * @return Area
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
     * @return Area
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
     * @param integer $country
     * @return Area
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * Get country
     *
     * @return integer 
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set region
     *
     * @param \models\Region $region
     * @return Area
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
}
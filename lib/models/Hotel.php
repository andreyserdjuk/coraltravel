<?php

namespace models;



/**
 * Hotel
 *
 * @Table(name="hotel")
 * @Entity
 */
class Hotel extends EntityBase 
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
     * @Column(name="name_en", type="string", length=256, nullable=false)
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
     * @Column(name="url", type="string", length=256, nullable=true)
     */
    private $url;

    /**
     * @var float
     *
     * @Column(name="latitude", type="float", nullable=true)
     */
    private $latitude;

    /**
     * @var float
     *
     * @Column(name="longitude", type="float", nullable=true)
     */
    private $longitude;

    /**
     * @var \HotelCategory
     *
     * @ManyToOne(targetEntity="HotelCategory")
     * @JoinColumns({
     *   @JoinColumn(name="category_id", referencedColumnName="id")
     * })
     */
    private $category;

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
     * @ManyToOne(targetEntity="Area")
     * @JoinColumns({
     *   @JoinColumn(name="area_id", referencedColumnName="id")
     * })
     */
    private $area;

    /**
     * @ManyToOne(targetEntity="Place")
     * @JoinColumns({
     *   @JoinColumn(name="place_id", referencedColumnName="id")
     * })
     */
    private $place;



    public function getId() {
        return $this->id;
    }

    public function setNameEn($nameEn) {
        $this->nameEn = $nameEn;
    
        return $this;
    }

    public function getNameEn() {
        return $this->nameEn;
    }

    public function setNameRu($nameRu) {
        $this->nameRu = $nameRu;
    
        return $this;
    }

    public function getNameRu() {
        return $this->nameRu;
    }

    public function setPlace($place) {
        $this->place = $place;
    
        return $this;
    }

    public function getPlace() {
        return $this->place;
    }

    public function setUrl($url) {
        $this->url = $url;
    
        return $this;
    }

    public function getUrl() {
        return $this->url;
    }

    public function setLatitude($latitude) {
        $this->latitude = $latitude;
    
        return $this;
    }

    public function getLatitude() {
        return $this->latitude;
    }

    public function setLongitude($longitude) {
        $this->longitude = $longitude;
    
        return $this;
    }

    public function getLongitude() {
        return $this->longitude;
    }

    public function setCategory(\models\HotelCategory $category = null) {
        $this->category = $category;
    
        return $this;
    }

    public function getCategory() {
        return $this->category;
    }

    public function setCountry(\models\Country $country = null) {
        $this->country = $country;
    
        return $this;
    }

    public function getCountry() {
        return $this->country;
    }

    public function setRegion(\models\Region $region = null) {
        $this->region = $region;
    
        return $this;
    }

    public function getRegion() {
        return $this->region;
    }

    public function setArea(\models\Area $area = null) {
        $this->area = $area;
    
        return $this;
    }

    public function getArea() {
        return $this->area;
    }
}
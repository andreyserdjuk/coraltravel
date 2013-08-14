<?php

namespace models;



/**
 * Room
 *
 * @Table(name="room")
 * @Entity
 */
class Room extends EntityBase 
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
     * @var \RoomCategory
     *
     * @ManyToOne(targetEntity="RoomCategory")
     * @JoinColumns({
     *   @JoinColumn(name="room_category_id", referencedColumnName="id")
     * })
     */
    private $roomCategory;



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
     * @return Room
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
     * @return Room
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
     * Set roomCategory
     *
     * @param \models\RoomCategory $roomCategory
     * @return Room
     */
    public function setCategory(\models\RoomCategory $roomCategory = null)
    {
        $this->roomCategory = $roomCategory;
    
        return $this;
    }

    /**
     * Get roomCategory
     *
     * @return \models\RoomCategory 
     */
    public function getCategory()
    {
        return $this->roomCategory;
    }
}
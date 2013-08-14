<?php

namespace models;



/**
 * Operator
 *
 * @Table(name="operator")
 * @Entity
 */
class Operator extends EntityBase 
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
     * @return Operator
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
     * @return Operator
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
}
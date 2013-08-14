<?php

namespace models;



/**
 * Currency
 *
 * @Table(name="currency")
 * @Entity
 */
class Currency extends EntityBase 
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
     * @Column(name="code", type="string", length=3, nullable=true)
     */
    private $code;

    /**
     * @var string
     *
     * @Column(name="code_ru", type="string", length=45, nullable=true)
     */
    private $codeRu;



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
     * Set code
     *
     * @param string $code
     * @return Currency
     */
    public function setCode($code)
    {
        $this->code = $code;
    
        return $this;
    }

    /**
     * Get code
     *
     * @return string 
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set codeRu
     *
     * @param string $codeRu
     * @return Currency
     */
    public function setCodeRu($codeRu)
    {
        $this->codeRu = $codeRu;
    
        return $this;
    }

    /**
     * Get codeRu
     *
     * @return string 
     */
    public function getCodeRu()
    {
        return $this->codeRu;
    }
}
<?php

namespace models;



/**
 * CtCurrency
 *
 * @Table(name="ct_currency")
 * @Entity
 */
class CtCurrency extends EntityBase 
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
     * @Column(name="ct_currency_id", type="integer", nullable=false)
     */
    private $ctCurrencyId;

    /**
     * @var \Currency
     *
     * @ManyToOne(targetEntity="Currency")
     * @JoinColumns({
     *   @JoinColumn(name="currency_id", referencedColumnName="id")
     * })
     */
    private $currency;



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
     * Set ctCurrencyId
     *
     * @param integer $ctCurrencyId
     * @return CtCurrency
     */
    public function setCtCurrencyId($ctCurrencyId)
    {
        $this->ctCurrencyId = $ctCurrencyId;
    
        return $this;
    }

    /**
     * Get ctCurrencyId
     *
     * @return integer 
     */
    public function getCtCurrencyId()
    {
        return $this->ctCurrencyId;
    }

    /**
     * Set currency
     *
     * @param \models\Currency $currency
     * @return CtCurrency
     */
    public function setCurrency(\models\Currency $currency = null)
    {
        $this->currency = $currency;
    
        return $this;
    }

    /**
     * Get currency
     *
     * @return \models\Currency 
     */
    public function getCurrency()
    {
        return $this->currency;
    }
}
<?php

namespace models;



/**
 * CtParsedZip
 *
 * @Table(name="ct_parsed_zip")
 * @Entity
 */
class CtParsedZip extends EntityBase 
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
     * @Column(name="file_zip", type="string", length=512, nullable=true)
     */
    private $fileZip;

    /**
     * @var \DateTime
     *
     * @Column(name="update_time", type="datetime", nullable=true)
     */
    private $updateTime;



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
     * Set fileZip
     *
     * @param string $fileZip
     * @return CtParsedZip
     */
    public function setFileZip($fileZip)
    {
        $this->fileZip = $fileZip;
    
        return $this;
    }

    /**
     * Get fileZip
     *
     * @return string 
     */
    public function getFileZip()
    {
        return $this->fileZip;
    }

    /**
     * Set updateTime
     *
     * @param \DateTime $updateTime
     * @return CtParsedZip
     */
    public function setUpdateTime($updateTime)
    {
        $this->updateTime = $updateTime;
    
        return $this;
    }

    /**
     * Get updateTime
     *
     * @return \DateTime 
     */
    public function getUpdateTime()
    {
        return $this->updateTime;
    }
}
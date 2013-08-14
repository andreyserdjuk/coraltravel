<?php

namespace models;



/**
 * CtRoomCategory
 *
 * @Table(name="ct_room_category")
 * @Entity
 */
class CtRoomCategory extends EntityBase 
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
     * @Column(name="ct_room_category_id", type="integer", nullable=true)
     */
    private $ctRoomCategoryId;

    /**
     * @var \RoomCategory
     *
     * @ManyToOne(targetEntity="RoomCategory")
     * @JoinColumns({
     *   @JoinColumn(name="room_category_id", referencedColumnName="id")
     * })
     */
    private $roomCategory;



    public function getId()
    {
        return $this->id;
    }

    public function setCtRoomCategoryId($ctRoomCategoryId)
    {
        $this->ctRoomCategoryId = $ctRoomCategoryId;
    
        return $this;
    }

    public function getCtRoomCategoryId()
    {
        return $this->ctRoomCategoryId;
    }

    public function setRoomCategory(\models\RoomCategory $roomCategory = null)
    {
        $this->roomCategory = $roomCategory;
    
        return $this;
    }

    public function getRoomCategory()
    {
        return $this->roomCategory;
    }
}
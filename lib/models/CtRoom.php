<?php

namespace models;



/**
 * CtRoom
 *
 * @Table(name="ct_room")
 * @Entity
 */
class CtRoom extends EntityBase 
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
     * @Column(name="ct_room_id", type="integer", nullable=false)
     */
    private $ctRoomId;

    /**
     * @var \Room
     *
     * @ManyToOne(targetEntity="Room")
     * @JoinColumns({
     *   @JoinColumn(name="room_id", referencedColumnName="id")
     * })
     */
    private $room;



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
     * Set ctRoomId
     *
     * @param integer $ctRoomId
     * @return CtRoom
     */
    public function setCtRoomId($ctRoomId)
    {
        $this->ctRoomId = $ctRoomId;
    
        return $this;
    }

    /**
     * Get ctRoomId
     *
     * @return integer 
     */
    public function getCtRoomId()
    {
        return $this->ctRoomId;
    }

    /**
     * Set room
     *
     * @param \models\Room $room
     * @return CtRoom
     */
    public function setRoom(\models\Room $room = null)
    {
        $this->room = $room;
    
        return $this;
    }

    /**
     * Get room
     *
     * @return \models\Room 
     */
    public function getRoom()
    {
        return $this->room;
    }
}
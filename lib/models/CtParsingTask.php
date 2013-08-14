<?php
namespace models;

/**
 * Place
 *
 * @Table(name="ct_parsing_task")
 * @Entity
 */
class CtParsingTask extends EntityBase 
{
    const ID_UPDATE_COUNTRY = 1;
    const ID_UPDATE_REGION = 2;
    const ID_UPDATE_AREA = 3;
    const ID_UPDATE_PLACE = 4;
    const ID_UPDATE_HOTEL_CATEGORY_GROUP = 5;
    const ID_UPDATE_HOTEL_CATEGORY = 6;
    const ID_UPDATE_HOTEL = 7;
    const ID_UPDATE_ROOM_CATEGORY = 8;
    const ID_UPDATE_ROOM = 9;
    const ID_UPDATE_MEAL_CATEGORY = 10;
    const ID_UPDATE_MEAL = 11;
    const ID_UPDATE_CURRENCY = 12;
    
    /**
     * @Column(name="id", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @Column(name="name", type="string", length=256, nullable=true)
     */
    private $name;

    /**
     * @Column(name="enabled", type="boolean")
     */
    private $enabled;


    public function getId() {
        return $this->id;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getName() {
        return $this->name;
    }

    public function setEnabled() {
        $this->enabled = TRUE;
    }

    public function getEnabled() {
        return $this->enabled;
    }

    public function setDisabled() {
        return $this->enabled = FALSE;
    }
}
<?php

namespace models;

/**
 * CtAgeGroupBundle
 *
 * @Table(name="ct_age_group_bundle")
 * @Entity
 */
class CtAgeGroupBundle extends EntityBase 
{
    /**
     * @Column(name="id", type="integer", nullable=false)
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @ManyToMany(targetEntity="CtAgeGroup")
     * @JoinTable(name="ct_age_groups_bundles",
     *      joinColumns={@JoinColumn(name="ct_age_group_bundle_id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="ct_age_group_id", referencedColumnName="id")}
     *      )
     **/
    private $ctAgeGroups;
    

    public function __construct() {
        $this->ctAgeGroups = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId() {
        return $this->id;
    }

    public function setCtAgeGroup($ctAgeGroup) {
        $this->ctAgeGroups[] = $ctAgeGroup;
    }

    public function getCtAgeGroups() {
        return $this->ctAgeGroups;
    }
}
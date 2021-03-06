<?php

/*
 * This file is part of PHP CS Fixer.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="track")
 */
class Track
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="name", type="string", length=100, nullable=true)
     * @Assert\Length(
     *      min = 1,
     *      max = 100,
     *      maxMessage = "Le nom ne doit pas dépasser {{ limit }} caractères",
     * )
     */
    protected $name;

    /**
     * @ORM\Column(name="track_points", type="text", nullable=true)
     */
    protected $trackPoints;

    /**
     * @ORM\Column(name="color", type="string", length=9)
     * @Assert\Length(
     *      min = 1,
     *      max = 9,
     *      maxMessage = "La couleur ne doit pas dépasser {{ limit }} caractères",
     *      groups={"editProfile", "Profile"}
     * )
     */
    protected $color;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Raid")
     * @ORM\JoinColumn(nullable=false, onDelete="cascade")
     */
    protected $raid;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\SportType")
     * @ORM\JoinColumn(nullable=true, onDelete="cascade")
     */
    protected $sportType;

    /**
     * @ORM\Column(name="isVisible", type="boolean", nullable=false)
     */
    protected $isVisible;

    /**
     * @ORM\Column(name="isCalibration", type="boolean", nullable=true)
     */
    protected $isCalibration = false;

    /**
     * Track constructor.
     */
    public function __construct()
    {
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param mixed $color
     */
    public function setColor($color)
    {
        $this->color = $color;
    }

    /**
     * @return mixed
     */
    public function getTrackPoints()
    {
        return $this->trackPoints;
    }

    /**
     * @param mixed $trackPoints
     */
    public function setTrackPoints($trackPoints)
    {
        $this->trackPoints = $trackPoints;
    }

    /**
     * @return Raid
     */
    public function getRaid()
    {
        return $this->raid;
    }

    /**
     * @param Raid $raid
     */
    public function setRaid($raid)
    {
        $this->raid = $raid;
    }

    /**
     * @return SportType
     */
    public function getSportType()
    {
        return $this->sportType;
    }

    /**
     * @param SportType $sportType
     */
    public function setSportType($sportType)
    {
        $this->sportType = $sportType;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getisVisible()
    {
        return $this->isVisible;
    }

    /**
     * @param mixed $isVisible
     */
    public function setIsVisible($isVisible)
    {
        $this->isVisible = $isVisible;
    }

    /**
     * @return mixed
     */
    public function getisCalibration()
    {
        return $this->isCalibration;
    }

    /**
     * @param mixed $isCalibration
     */
    public function setIsCalibration($isCalibration)
    {
        $this->isCalibration = $isCalibration;
    }
}

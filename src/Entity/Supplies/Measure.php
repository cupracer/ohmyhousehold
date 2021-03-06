<?php

namespace App\Entity\Supplies;

use App\Repository\Supplies\MeasureRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=MeasureRepository::class)
 * @ORM\Table(name="supplies_measure")
 */
class Measure
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @var string
     */
    private $unit;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $physicalQuantity;

    public function __toString(): string
    {
        return $this->name;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getUnit(): string
    {
        return $this->unit;
    }

    /**
     * @param string $unit
     * @return Measure
     */
    public function setUnit(string $unit): Measure
    {
        $this->unit = $unit;
        return $this;
    }

    public function getPhysicalQuantity(): ?string
    {
        return $this->physicalQuantity;
    }

    public function setPhysicalQuantity(string $physicalQuantity): self
    {
        $this->physicalQuantity = $physicalQuantity;

        return $this;
    }
}

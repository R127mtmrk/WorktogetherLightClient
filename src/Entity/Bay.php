<?php

namespace App\Entity;

use App\Repository\BayRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BayRepository::class)]
class Bay
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name_bay = null;

    /**
     * @var Collection<int, Unit>
     */
    #[ORM\OneToMany(targetEntity: Unit::class, mappedBy: 'bay')]
    private Collection $units;

    public function __construct()
    {
        $this->units = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNameBay(): ?string
    {
        return $this->name_bay;
    }

    public function setNameBay(string $name_bay): static
    {
        $this->name_bay = $name_bay;

        return $this;
    }

    /**
     * @return Collection<int, Unit>
     */
    public function getUnits(): Collection
    {
        return $this->units;
    }

    public function addUnit(Unit $unit): static
    {
        if (!$this->units->contains($unit)) {
            $this->units->add($unit);
            $unit->setBay($this);
        }

        return $this;
    }

    public function removeUnit(Unit $unit): static
    {
        if ($this->units->removeElement($unit)) {
            // set the owning side to null (unless already changed)
            if ($unit->getBay() === $this) {
                $unit->setBay(null);
            }
        }

        return $this;
    }
}

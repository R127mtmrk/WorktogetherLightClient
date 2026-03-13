<?php

namespace App\Entity;

use App\Repository\StateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StateRepository::class)]
class State
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<int, Unit>
     */
    #[ORM\OneToMany(targetEntity: Unit::class, mappedBy: 'state')]
    private Collection $Units;

    #[ORM\Column(length: 255)]
    private ?string $libelle_state = null;

    public function __construct()
    {
        $this->Units = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Unit>
     */
    public function getUnits(): Collection
    {
        return $this->Units;
    }

    public function addUnit(Unit $unit): static
    {
        if (!$this->Units->contains($unit)) {
            $this->Units->add($unit);
            $unit->setState($this);
        }

        return $this;
    }

    public function removeUnit(Unit $unit): static
    {
        if ($this->Units->removeElement($unit)) {
            // set the owning side to null (unless already changed)
            if ($unit->getState() === $this) {
                $unit->setState(null);
            }
        }

        return $this;
    }

    public function getLibelleState(): ?string
    {
        return $this->libelle_state;
    }

    public function setLibelleState(string $libelle_state): static
    {
        $this->libelle_state = $libelle_state;

        return $this;
    }

    public function __toString(): string
    {
        return $this->libelle_state;
    }
}

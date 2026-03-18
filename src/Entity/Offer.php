<?php

namespace App\Entity;

use App\Repository\OfferRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OfferRepository::class)]
class Offer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name_offer = null;


    /**
     * @var Collection<int, Order>
     */
    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'offer')]
    private Collection $orders;

    // discountPercent: pourcentage de réduction (ex: '10.00' pour 10%)
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $discountPercent = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $minUnits = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $maxUnits = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    public function __construct()
    {
        $this->orders = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNameOffer(): ?string
    {
        return $this->name_offer;
    }

    public function setNameOffer(string $name_offer): static
    {
        $this->name_offer = $name_offer;

        return $this;
    }

    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setOffer($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getOffer() === $this) {
                $order->setOffer(null);
            }
        }

        return $this;
    }

    public function getDiscountPercent(): ?string
    {
        return $this->discountPercent;
    }

    public function setDiscountPercent(?string $discountPercent): static
    {
        $this->discountPercent = $discountPercent;

        return $this;
    }

    public function getMinUnits(): ?int
    {
        return $this->minUnits;
    }

    public function setMinUnits(?int $minUnits): static
    {
        $this->minUnits = $minUnits;

        return $this;
    }

    public function getMaxUnits(): ?int
    {
        return $this->maxUnits;
    }

    public function setMaxUnits(?int $maxUnits): static
    {
        $this->maxUnits = $maxUnits;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $active): static
    {
        $this->isActive = $active;

        return $this;
    }
}

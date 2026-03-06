<?php

namespace App\Entity;

use App\Repository\UnitRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UnitRepository::class)]
class Unit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'units')]
    private ?Order $order = null;

    #[ORM\ManyToOne(inversedBy: 'units')]
    private ?Ticket $ticket = null;

    #[ORM\Column]
    private ?bool $is_free = null;

    #[ORM\ManyToOne(inversedBy: 'units')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Bay $bay = null;

    #[ORM\ManyToOne(inversedBy: 'Units')]
    #[ORM\JoinColumn(nullable: false)]
    private ?State $state = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrders(): ?Order
    {
        return $this->order;
    }

    public function setOrders(?Order $orders): static
    {
        $this->order = $orders;

        return $this;
    }

    public function getTicket(): ?Ticket
    {
        return $this->ticket;
    }

    public function setTicket(?Ticket $ticket): static
    {
        $this->ticket = $ticket;

        return $this;
    }

    public function isFree(): ?bool
    {
        return $this->is_free;
    }

    public function setIsFree(bool $is_free): static
    {
        $this->is_free = $is_free;

        return $this;
    }

    public function getBay(): ?Bay
    {
        return $this->bay;
    }

    public function setBay(?Bay $bay): static
    {
        $this->bay = $bay;

        return $this;
    }

    public function getState(): ?State
    {
        return $this->state;
    }

    public function setState(?State $state): static
    {
        $this->state = $state;

        return $this;
    }
}

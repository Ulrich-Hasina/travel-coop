<?php

namespace App\Entity;

use App\Enum\BookingStatus;
use App\Repository\BookingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $numberOfPersons = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $totalPrice = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(enumType: BookingStatus::class)]
    private ?BookingStatus $status = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $customer = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Departure $departure = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumberOfPersons(): ?int
    {
        return $this->numberOfPersons;
    }

    public function setNumberOfPersons(int $numberOfPersons): static
    {
        $this->numberOfPersons = $numberOfPersons;

        return $this;
    }

    public function getTotalPrice(): ?string
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(string $totalPrice): static
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getStatus(): ?BookingStatus
    {
        return $this->status;
    }

    public function setStatus(BookingStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCustomer(): ?User
    {
        return $this->customer;
    }

    public function setCustomer(?User $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getDeparture(): ?Departure
    {
        return $this->departure;
    }

    public function setDeparture(?Departure $departure): static
    {
        $this->departure = $departure;

        return $this;
    }
}

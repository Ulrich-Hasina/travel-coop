<?php

namespace App\Entity;

use App\Enum\DepartureStatus;
use App\Repository\DepartureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;


#[ORM\Entity(repositoryClass: DepartureRepository::class)]
class Departure
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $departureAt = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $price = null;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: DepartureStatus::class)]
    private DepartureStatus $status = DepartureStatus::Scheduled;

    #[ORM\ManyToOne(inversedBy: 'departures')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Line $line = null;

    /**
     * @var Collection<int, Booking>
     */
    #[ORM\OneToMany(targetEntity: Booking::class, mappedBy: 'departure')]
    private Collection $bookings;

    #[ORM\Column]
    private ?int $seats = null;

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDepartureAt(): ?\DateTimeImmutable
    {
        return $this->departureAt;
    }

    public function setDepartureAt(\DateTimeImmutable $departureAt): static
    {
        $this->departureAt = $departureAt;

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(?int $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getStatus(): ?DepartureStatus
    {
        return $this->status;
    }

    public function setStatus(DepartureStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getLine(): ?Line
    {
        return $this->line;
    }

    public function setLine(?Line $line): static
    {
        $this->line = $line;

        return $this;
    }

    /**
     * @return Collection<int, Booking>
     */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function addBooking(Booking $booking): static
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->setDeparture($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): static
    {
        if ($this->bookings->removeElement($booking)) {
            // set the owning side to null (unless already changed)
            if ($booking->getDeparture() === $this) {
                $booking->setDeparture(null);
            }
        }

        return $this;
    }

    public function getSeats(): ?int
    {
        return $this->seats;
    }

    public function setSeats(int $seats): static
    {
        $this->seats = $seats;

        return $this;
    }
}

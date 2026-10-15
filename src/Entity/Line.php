<?php

namespace App\Entity;

use App\Repository\LineRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: LineRepository::class)]
class Line
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['departure:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['departure:read'])]
    private string $origin;

    #[ORM\Column(length: 255)]
    #[Groups(['departure:read'])]
    private string $destination;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['departure:read'])]
    private string $basePrice;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups(['departure:read'])]
    private ?int $normalDuration = null;

    /**
     * @var Collection<int, Departure>
     */
    #[ORM\OneToMany(targetEntity: Departure::class, mappedBy: 'line')]
    private Collection $departures;

    public function __construct()
    {
        $this->departures = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrigin(): ?string
    {
        return $this->origin;
    }

    public function setOrigin(string $origin): static
    {
        $this->origin = $origin;

        return $this;
    }

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function setDestination(string $destination): static
    {
        $this->destination = $destination;

        return $this;
    }

    public function getBasePrice(): ?string
    {
        return $this->basePrice;
    }

    public function setBasePrice(string $basePrice): static
    {
        $this->basePrice = $basePrice;

        return $this;
    }

    public function getNormalDuration(): ?int
    {
        return $this->normalDuration;
    }

    public function setNormalDuration(?int $normalDuration): static
    {
        $this->normalDuration = $normalDuration;

        return $this;
    }

    /**
     * @return Collection<int, Departure>
     */
    public function getDepartures(): Collection
    {
        return $this->departures;
    }

    public function addDeparture(Departure $departure): static
    {
        if (!$this->departures->contains($departure)) {
            $this->departures->add($departure);
            $departure->setLine($this);
        }

        return $this;
    }

    public function removeDeparture(Departure $departure): static
    {
        if ($this->departures->removeElement($departure)) {
            // set the owning side to null (unless already changed)
            if ($departure->getLine() === $this) {
                $departure->setLine(null);
            }
        }

        return $this;
    }
}

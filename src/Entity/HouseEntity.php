<?php

namespace App\Entity;
use App\Infrastructure\Persistence\HouseRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: HouseRepository::class)]
#[ORM\Table(name: "house")]
class HouseEntity
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type:"integer")]
    private int $id;

    #[ORM\Column(type:"string", length:255)]
    private string $name;

    // можно заменить на enum-backed column
    #[ORM\Column(type:"string", length:50)]
    private string $type;

    #[ORM\Column(type:"smallint")]
    private int $beds;

    #[ORM\Column(type:"integer")]
    private int $rowFromSea;

    #[ORM\Column(type:"integer")]
    private int $pricePerNightRub;

    #[ORM\OneToMany(mappedBy: "house", targetEntity: BookingEntity::class, cascade:["persist","remove"])]
    private Collection $bookings;

    #[ORM\Column(type:"datetime_immutable")]
    private DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();
    }
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getBeds(): int
    {
        return $this->beds;
    }

    public function setBeds(int $beds): self
    {
        $this->beds = $beds;
        return $this;
    }

    public function getRowFromSea(): int
    {
        return $this->rowFromSea;
    }

    public function setRowFromSea(int $rowFromSea): self
    {
        $this->rowFromSea = $rowFromSea;
        return $this;
    }

    public function getPricePerNightRub(): int
    {
        return $this->pricePerNightRub;
    }

    public function setPricePerNightRub(int $pricePerNightRub): self
    {
        $this->pricePerNightRub = $pricePerNightRub;
        return $this;
    }
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function addBooking(BookingEntity $booking): self
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->setHouse($this);
        }
        return $this;
    }

    public function removeBooking(BookingEntity $booking): self
    {
        if ($this->bookings->contains($booking)) {
            $this->bookings->removeElement($booking);
        }
        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}

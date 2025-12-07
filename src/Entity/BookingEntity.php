<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\BookingRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[ORM\Table(name: 'booking')]
class BookingEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type:'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: HouseEntity::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable:false, onDelete:'CASCADE')]
    private ?HouseEntity $house = null;

    #[ORM\ManyToOne(targetEntity: UserEntity::class)]
    #[ORM\JoinColumn(name: 'phone', referencedColumnName: 'phone', nullable: false, onDelete: 'RESTRICT')]
    private ?UserEntity $user = null;

    #[ORM\Column(type:'text', nullable:true)]
    private ?string $comment = null;

    #[ORM\Column(type:'date', nullable:true)]
    private ?DateTimeImmutable $dateFrom = null;

    #[ORM\Column(type:'date', nullable:true)]
    private ?DateTimeImmutable $dateTo = null;

    #[ORM\Column(type:'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHouse(): ?HouseEntity
    {
        return $this->house;
    }
    public function setHouse(HouseEntity $house): self
    {
        $this->house = $house;
        return $this;
    }

    public function getUser(): ?UserEntity
    {
        return $this->user;
    }
    public function setUser(UserEntity $user): self
    {
        $this->user = $user;
        return $this;
    }

    // удобный геттер номера телефона — вытаскиваем из связанного UserEntity
    public function getPhone(): ?string
    {
        return $this->user?->getPhone();
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }
    public function setComment(?string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    public function getDateFrom(): ?DateTimeImmutable
    {
        return $this->dateFrom;
    }
    public function setDateFrom(?DateTimeImmutable $dateFrom): self
    {
        $this->dateFrom = $dateFrom;
        return $this;
    }
    public function getDateTo(): ?DateTimeImmutable
    {
        return $this->dateTo;
    }
    public function setDateTo(?DateTimeImmutable $dateTo): self
    {
        $this->dateTo = $dateTo;
        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}

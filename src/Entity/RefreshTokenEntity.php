<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'refresh_tokens')]
#[ORM\HasLifecycleCallbacks]
class RefreshTokenEntity
{
    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: UserEntity::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private UserEntity $user;

    #[ORM\Column(type: 'string', length: 512)]
    private string $token;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $expiryDate;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct(UserEntity $user, string $token, DateTimeImmutable $expiryDate)
    {
        $this->user = $user;
        $this->token = $token;
        $this->expiryDate = $expiryDate;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getUser(): UserEntity
    {
        return $this->user;
    }

    public function getId(): ?int
    {
        return $this->user->getId();
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;
        return $this;
    }

    public function getExpiryDate(): DateTimeImmutable
    {
        return $this->expiryDate;
    }

    public function setExpiryDate(DateTimeImmutable $expiryDate): self
    {
        $this->expiryDate = $expiryDate;
        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}

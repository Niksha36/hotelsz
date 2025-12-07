<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class UserEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 15)]
    private string $phone;

    #[ORM\Column(type: 'string', length: 100)]
    private string $firstName;

    #[ORM\Column(type: 'string', length: 100)]
    private string $lastName;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email;

    public function __construct(string $phone, string $firstName, string $lastName, string $email)
    {
        $this->phone = $phone;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }
    public function setFirstName(string $v): self
    {
        $this->firstName = $v;
        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }
    public function setLastName(string $v): self
    {
        $this->lastName = $v;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
    public function setEmail(string $v): self
    {
        $this->email = $v;
        return $this;
    }
}

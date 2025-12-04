<?php

declare(strict_types=1);

namespace App\Entity;

use App\Security\UserRole;
use Doctrine\ORM\Mapping as ORM;
use Override;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class UserEntity implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 15, unique: true)]
    private string $phone;

    #[ORM\Column(type: 'string', length: 100)]
    private string $firstName;

    #[ORM\Column(type: 'string', length: 100)]
    private string $lastName;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string', length: 255)]
    private string $password;

    #[ORM\Column(type: 'json')]
    private array $roles;

    /**
     * Constructor kept backward-compatible: password and roles are optional.
     *
     * @param string $phone
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     * @param string $password hashed password (optional, default empty)
     * @param array  $roles    roles array (optional, default empty => ROLE_USER)
     */
    public function __construct(
        string $phone,
        string $firstName,
        string $lastName,
        string $email,
        string $password = '',
        array $roles = [UserRole::ROLE_USER->value],
    ) {
        $this->phone = $phone;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->password = $password;
        $this->roles = $roles;
    }

    /**
     * Get auto-increment id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
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

    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    #[Override]
    public function getRoles(): array
    {
        $roles = $this->roles;
        if (empty($roles)) {
            $roles[] = 'ROLE_USER';
        }

        return array_values(array_unique($roles));
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    #[Override]
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $hashed): self
    {
        $this->password = $hashed;
        return $this;
    }

    #[Override]
    public function getUserIdentifier(): string
    {
        return $this->phone;
    }

    #[Override]
    public function eraseCredentials(): void
    {
    }
}

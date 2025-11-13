<?php

declare(strict_types=1);

namespace App\Tests\services;

use App\dto\UserDto;
use App\Repository\UserRepository;
use App\Services\UserService;
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
    public function testCreateUserSavesAndReturnsDto(): void
    {
        $repo = $this->createMock(UserRepository::class);
        $repo->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($entity) {
                return $entity->getPhone() === '+79995554433'
                    && $entity->getFirstName() === 'Ivan'
                    && $entity->getLastName() === 'Petrov'
                    && $entity->getEmail() === 'ivan@example.com';
            }));

        $service = new UserService($repo);
        $dto = new UserDto(
            phone: '+79995554433',
            firstName: 'Ivan',
            lastName: 'Petrov',
            email: 'ivan@example.com'
        );

        $result = $service->createUser($dto);
        $this->assertInstanceOf(UserDto::class, $result);
        $this->assertSame('+79995554433', $result->phone);
        $this->assertSame('Ivan', $result->firstName);
        $this->assertSame('Petrov', $result->lastName);
        $this->assertSame('ivan@example.com', $result->email);
    }
}

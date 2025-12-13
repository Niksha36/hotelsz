<?php

declare(strict_types=1);

namespace App\Services;

use App\dto\UserDto;
use App\Entity\UserEntity;
use App\Repository\UserRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use RuntimeException;

class UserService
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @return UserEntity[]
     */
    public function getUsers()
    {
        return $this->userRepository->findAll();
    }

    public function updateUser(int $id, UserDto $data): UserDto
    {
        $userEntity = $this->userRepository->find($id);

        if (!$userEntity instanceof UserEntity) {
            throw new RuntimeException('User not found');
        }

        $userEntity->setFirstName($data->firstName);
        $userEntity->setLastName($data->lastName);
        $userEntity->setEmail($data->email);
        $userEntity->setPhone($data->phone);

        $this->userRepository->save($userEntity);

        return $data;
    }

    /**
     * @psalm-suppress UndefinedMagicMethod
     */
    public function deleteUser(int $id)
    {
        $userEntity = $this->userRepository->find($id);

        if (!$userEntity instanceof UserEntity) {
            throw new RuntimeException('User not found');
        }

        $this->userRepository->remove($userEntity);
    }
}

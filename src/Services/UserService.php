<?php

declare(strict_types=1);

namespace App\Services;

use App\dto\UserDto;
use App\mappers\UserMappers;
use App\Repository\UserRepository;

class UserService
{
    private UserRepository $userRepository;
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    public function createUser(UserDto $userDto): UserDto
    {
        $entity = UserMappers::fromUserDtoToEntity($userDto);
        $this->userRepository->save($entity);
        return UserMappers::fromUserEntityToDto($entity);
    }
}

<?php

namespace App\Services;

use App\dto\UserDto;
use App\Infrastructure\Persistence\BookingRepository;
use App\Infrastructure\Persistence\UserRepository;
use App\mappers\UserMappers;

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

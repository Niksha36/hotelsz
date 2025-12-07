<?php

namespace App\mappers;

use App\dto\UserDto;
use App\Entity\UserEntity;

final class UserMappers
{
    static public function fromUserDtoToEntity(UserDto $userDto): UserEntity
    {
        return new UserEntity(
            $userDto->phone,
            $userDto->firstName,
            $userDto->lastName,
            $userDto->email
        );
    }

    static public function fromUserEntityToDto(UserEntity $user): UserDto
    {
        return new UserDto(
            $user->getPhone(),
            $user->getFirstName(),
            $user->getLastName(),
            $user->getEmail()
        );
    }
}

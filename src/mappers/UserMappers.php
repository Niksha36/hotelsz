<?php

declare(strict_types=1);

namespace App\mappers;

use App\dto\UserDto;
use App\Entity\UserEntity;

final class UserMappers
{
    public static function fromUserEntityToDto(UserEntity $user): UserDto
    {
        return new UserDto(
            $user->getPhone(),
            $user->getFirstName(),
            $user->getLastName(),
            $user->getEmail()
        );
    }
}

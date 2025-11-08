<?php

declare(strict_types=1);

namespace App\mappers;

use App\dto\BookingRequestDto;
use App\Entity\BookingEntity;
use App\Entity\HouseEntity;
use App\Entity\UserEntity;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;

final class BookingMappers
{
    public static function fromDtoToEntity(BookingRequestDto $dto, UserEntity $user, HouseEntity $house): BookingEntity
    {
        $entity = new BookingEntity();
        $entity->setUser($user);
        $entity->setHouse($house);
        $entity->setComment($dto->comment);
        $entity->setDateFrom($dto->dateFrom);
        $entity->setDateTo($dto->dateTo);
        return $entity;
    }

    public static function fromEntityToDto(BookingEntity $entity): BookingRequestDto
    {
        $dto = new BookingRequestDto(
            id: $entity->getId(),
            houseId: $entity->getHouse()?->getId(),
            phone: $entity->getPhone(),
            comment: $entity->getComment(),
            dateFrom: $entity->getDateFrom(),
            dateTo: $entity->getDateTo()
        );
        return $dto;
    }
}

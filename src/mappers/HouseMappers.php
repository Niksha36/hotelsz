<?php

declare(strict_types=1);

namespace App\mappers;

use App\dto\HouseDto;
use App\Entity\HouseEntity;

final class HouseMappers
{
    public static function fromEntityToDto($entity): HouseDto
    {
        return new HouseDto(
            id: $entity->getId(),
            name: $entity->getName(),
            type: $entity->getType(),
            beds: $entity->getBeds(),
            rowFromSea: $entity->getRowFromSea(),
            pricePerNightRub: $entity->getPricePerNightRub(),
        );
    }

    public static function fromDtoToEntity(HouseDto $dto): HouseEntity
    {
        $entity = new HouseEntity();
        $entity->setName($dto->name);
        $entity->setType($dto->type);
        $entity->setBeds($dto->beds);
        $entity->setRowFromSea($dto->rowFromSea);
        $entity->setPricePerNightRub($dto->pricePerNightRub);
        return $entity;
    }
}

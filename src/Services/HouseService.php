<?php

declare(strict_types=1);

namespace App\Services;

use App\dto\HouseDto;
use App\Enum\HouseFilters;
use App\Enum\SortDirection;
use App\mappers\HouseMappers;
use App\Repository\HouseRepository;
use InvalidArgumentException;

class HouseService
{
    private HouseRepository $houseRepository;
    public function __construct(HouseRepository $houseRepository)
    {
        $this->houseRepository = $houseRepository;
    }
    /**
     * @return HouseDto[]
     */
    public function getHouses(): array
    {
        return array_map(fn ($entity) => HouseMappers::fromEntityToDto($entity), $this->houseRepository->findAll());
    }
    public function saveHouse(HouseDto $houseDto): HouseDto
    {
        $entity = HouseMappers::fromDtoToEntity($houseDto);
        $this->houseRepository->save($entity);
        return HouseMappers::fromEntityToDto($entity);
    }

    public function filterHousesBy(?HouseFilters $fieldEnum, ?SortDirection $directionEnum): array
    {
        if ($fieldEnum === null) {
            $allowed = implode(', ', array_map(fn ($case) => $case->value, HouseFilters::cases()));
            throw new InvalidArgumentException(sprintf('Не указано поле для фильтрации. Допустимые значения: %s', $allowed));
        }

        if ($directionEnum === null) {
            $allowed = implode(', ', array_map(fn ($case) => $case->value, SortDirection::cases()));
            throw new InvalidArgumentException(sprintf('Не указано направление сортировки. Допустимые значения: %s', $allowed));
        }

        $orderBy = [
            $fieldEnum->getEntityFieldName() => $directionEnum->name
        ];

        $entities = $this->houseRepository->findBy([], $orderBy);
        return array_map(fn ($entity) => HouseMappers::fromEntityToDto($entity), $entities);
    }
}

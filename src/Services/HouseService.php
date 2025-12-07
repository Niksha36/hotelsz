<?php

namespace App\Services;

use App\dto\HouseDto;
use App\Infrastructure\Persistence\HouseRepository;
use App\mappers\BookingMappers;
use App\mappers\HouseMappers;

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
        return array_map(fn($entity) => HouseMappers::fromEntityToDto($entity), $this->houseRepository->findAll());
    }
    public function saveHouse(HouseDto $houseDto): HouseDto
    {
        $entity = HouseMappers::fromDtoToEntity($houseDto);
        $this->houseRepository->save($entity);
        return HouseMappers::fromEntityToDto($entity);
    }
}

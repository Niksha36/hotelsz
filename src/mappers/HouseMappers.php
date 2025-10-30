<?php
namespace  App\mappers;
use App\dto\HouseDto;
final class HouseMappers {
    public static function toHouseDto(array $data): HouseDto
    {
        return new HouseDto(
            id: (int)$data['id'],
            name: $data['name'],
            type: $data['type'],
            beds: (int)$data['beds'],
            amenities: explode(';', $data['amenities']),
            rowFromSea: (int)$data['row_from_sea'],
            pricePerNightRub: (float)$data['price_per_night_rub'],
            isBooked: (bool)$data['is_booked'],
        );
    }

    public static function fromHouseDtoToMap(HouseDto $dto): array
    {
        return [
            'id' => isset($dto->id) ? (string)$dto->id : '',
            'name' => $dto->name ?? '',
            'type' => $dto->type ?? '',
            'beds' => isset($dto->beds) ? (string)$dto->beds : '',
            'amenities' => !empty($dto->amenities) ? implode(';', $dto->amenities) : '',
            'row_from_sea' => isset($dto->rowFromSea) ? (string)$dto->rowFromSea : '',
            'price_per_night_rub' => isset($dto->pricePerNightRub) ? (string)$dto->pricePerNightRub : '',
            'is_booked' => $dto->isBooked ? '1' : '0',
        ];
    }
}


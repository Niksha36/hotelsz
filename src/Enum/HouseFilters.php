<?php

declare(strict_types=1);

namespace App\Enum;

enum HouseFilters: String
{
    case BEDS = 'beds';
    case ROW_FROM_SEA = 'row_from_sea';
    case PRICE = 'price';
    case DATE = 'date';

    public function getEntityFieldName(): string
    {
        return match ($this) {
            HouseFilters::BEDS => 'beds',
            HouseFilters::ROW_FROM_SEA => 'rowFromSea',
            HouseFilters::PRICE => 'pricePerNightRub',
            HouseFilters::DATE => 'createdAt',
        };
    }
}

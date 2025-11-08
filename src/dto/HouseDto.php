<?php

declare(strict_types=1);

namespace App\dto;

readonly class HouseDto
{
    public function __construct(
        public ?int $id = null,
        public string $name,
        public string $type,
        public int $beds,
        public int $rowFromSea,
        public float $pricePerNightRub,
    ) {
    }
}

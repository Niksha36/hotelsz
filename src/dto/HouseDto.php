<?php
namespace App\dto;
readonly class HouseDto
{
    public function __construct(
        public int    $id,
        public string $name,
        public string $type,
        public int    $beds,
        public array  $amenities,
        public int    $rowFromSea,
        public float  $pricePerNightRub,
        public bool   $isBooked,
    ){}
}
